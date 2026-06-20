<?php
/**
 * Advertiser → My Tracker Postback Receiver
 *
 * Advertisers fire this URL when a conversion is confirmed:
 *
 *   https://mytracker.com/postback?click_id={click_id}&payout={payout}
 *
 * ONLY two parameters are used:
 *   click_id  — the UUID this tracker assigned when the user clicked
 *               (advertiser received it via their offer URL macro {click_id})
 *   payout    — conversion payout amount (falls back to offer default if 0 / missing)
 *
 * On receipt this tracker will:
 *   1. Validate click_id and look up the originating click
 *   2. Record the conversion with payout
 *   3. Credit the affiliate balance
 *   4. Instantly fire the affiliate's Global Postback URL → Affiliate Tracker
 *      (Advertiser → My Tracker → Affiliate Tracker dual-postback flow)
 *   5. Log all postback attempts (success + failure) for admin debugging
 */

// ── Prevent PHP execution timeout from killing the script before ──────────
// postback_sent=1 is written. cURL requests to affiliate trackers can take
// up to 15 s each; without this the UPDATE never runs on slow servers.
set_time_limit(0);
ignore_user_abort(true);

// ── Release the PHP session lock immediately ──────────────────────────────
// index.php calls session_start() before routing. The open session file lock
// prevents fastcgi_finish_request() / flush() from properly pushing the HTTP
// response to the network, causing the web server to kill the PHP process
// before PostbackFirer::fireAll() can run. Closing the session here releases
// the lock so the response flushes cleanly and background execution continues.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require_once BASE_PATH . '/core/PostbackFirer.php';
require_once BASE_PATH . '/core/ManagerCommissionService.php';
require_once BASE_PATH . '/core/RiskEngine.php';

header('Content-Type: application/json');

// ── Parse canonical parameters ────────────────────────────────────────────
$clickId = trim($_GET['click_id'] ?? '');
// Accept ?payout= (standard) or ?amount= (some advertiser tracker aliases)
$payout  = (float)($_GET['payout'] ?? ($_GET['amount'] ?? 0));
// Optional: goal name (e.g. &goal=Registration or &goal=Deposit)
$goalName = trim($_GET['goal'] ?? ($_GET['goal_name'] ?? ''));
$goalName = $goalName !== '' ? substr($goalName, 0, 100) : null;
// Optional: transaction/order ID from advertiser (e.g. &txn_id=ORD123 or &transaction_id=ORD123)
$txnId = trim($_GET['txn_id'] ?? ($_GET['transaction_id'] ?? ($_GET['order_id'] ?? '')));
$txnId = $txnId !== '' ? substr($txnId, 0, 255) : null;

// Log the incoming advertiser postback for debugging
PostbackFirer::log("[ADV→TRACKER] Received postback: click_id={$clickId} payout={$payout} goal=" . ($goalName ?? '') . " txn_id=" . ($txnId ?? '') . " ip=" . ($_SERVER['REMOTE_ADDR'] ?? ''));

// ── Advertiser Postback Log: capture state, write on shutdown ─────────────
// Logs every incoming postback from advertisers — accepted, duplicate, invalid,
// or error — so advertisers can review their postback firing history.
$_advPbLog = [
    'advertiser_id' => null,
    'offer_id'      => null,
    'click_id'      => $clickId ?: null,
    'conversion_id' => null,
    'payout'        => $payout,
    'status'        => 'error',
    'reject_reason' => null,
    'request_ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
    'request_url'   => ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://'
                     . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''),
    'response_body' => null,
];
register_shutdown_function(function() use (&$_advPbLog) {
    // Skip logging if click_id is empty (bot/scanner noise with no params)
    if (empty($_advPbLog['click_id'])) return;
    try {
        // Auto-create table if needed (runs once then becomes a fast no-op)
        Database::query("CREATE TABLE IF NOT EXISTS `advertiser_postback_logs` (
            `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `advertiser_id` INT UNSIGNED DEFAULT NULL,
            `offer_id`      INT UNSIGNED DEFAULT NULL,
            `click_id`      VARCHAR(255) DEFAULT NULL,
            `conversion_id` CHAR(36) DEFAULT NULL,
            `payout`        DECIMAL(10,4) DEFAULT 0.0000,
            `status`        VARCHAR(30) NOT NULL DEFAULT 'error',
            `reject_reason` VARCHAR(500) DEFAULT NULL,
            `request_ip`    VARCHAR(45) DEFAULT NULL,
            `request_url`   TEXT DEFAULT NULL,
            `response_body` TEXT DEFAULT NULL,
            `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_adv_id`   (`advertiser_id`),
            INDEX `idx_click_id` (`click_id`),
            INDEX `idx_created`  (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (\Throwable $e) {}
    try {
        Database::insert('advertiser_postback_logs', array_filter([
            'advertiser_id' => $_advPbLog['advertiser_id'],
            'offer_id'      => $_advPbLog['offer_id'],
            'click_id'      => $_advPbLog['click_id'],
            'conversion_id' => $_advPbLog['conversion_id'],
            'payout'        => (float)$_advPbLog['payout'],
            'status'        => $_advPbLog['status'],
            'reject_reason' => $_advPbLog['reject_reason'],
            'request_ip'    => $_advPbLog['request_ip'],
            'request_url'   => $_advPbLog['request_url'],
            'response_body' => $_advPbLog['response_body'],
        ], fn($v) => $v !== null));
    } catch (\Throwable $e) {
        PostbackFirer::log('[postback.php] AdvPbLog write failed: ' . $e->getMessage());
    }
    
    // Auto-Sync Points Inline
    try {
        if (!class_exists('PointsService')) require_once BASE_PATH . '/core/PointsService.php';
        if (class_exists('PointsService')) {
            $cfg = PointsService::config();
            if ($cfg['enabled']) {
                $usdPerPt = max(0.01, (float)$cfg['usd_per_point']);
                $missingConvs = Database::fetchAll(
                    "SELECT c.id, c.affiliate_id, c.payout 
                     FROM conversions c 
                     WHERE c.status = 'approved' AND COALESCE(c.is_hidden, 0) = 0 
                       AND c.payout > 0 AND c.converted_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                       AND NOT EXISTS (SELECT 1 FROM points_transactions pt WHERE pt.ref_type = 'conversion' AND pt.ref_id = CAST(c.id AS CHAR))"
                );
                foreach ($missingConvs as $mc) {
                    $pts = (int)floor((float)$mc['payout'] / $usdPerPt);
                    if ($pts > 0) PointsService::credit((int)$mc['affiliate_id'], $pts, sprintf('Earnings $%.2f × rule', $mc['payout']), 'conversion', (string)$mc['id']);
                }
            }
        }
    } catch (\Throwable $e) {}
});

// ── Validate click_id (must be a UUID v4) ─────────────────────────────────
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $clickId)) {
    $_advPbLog['status'] = 'invalid_click'; $_advPbLog['reject_reason'] = 'Invalid click_id format';
    $_advPbLog['response_body'] = json_encode(['status'=>'error','message'=>'Invalid click_id format']);
    http_response_code(400);
    echo $_advPbLog['response_body'];
    exit;
}

// ── Look up the original click ────────────────────────────────────────────
$click = Database::fetchOne(
    "SELECT c.*, o.payout_amount, o.revenue_amount, o.payout_type,
            o.advertiser_id, COALESCE(o.is_inhouse, 0) as is_inhouse,
            o.landing_pages, o.daily_cap
     FROM `clicks` c
     JOIN `offers` o ON o.id = c.offer_id
     WHERE c.click_id = ?",
    [$clickId]
);

if (!$click) {
    // Fallback: check if this was a Traffic Back click
    $tbClick = Database::fetchOne(
        "SELECT t.*, o.payout_amount, o.revenue_amount, o.payout_type,
                o.advertiser_id, COALESCE(o.is_inhouse, 0) as is_inhouse,
                o.landing_pages, o.daily_cap
         FROM `traffic_back_logs` t
         JOIN `offers` o ON o.id = t.offer_id
         WHERE t.click_id = ?",
        [$clickId]
    );
    
    if ($tbClick) {
        $click = [
            'id'               => 0,
            'click_id'         => $tbClick['click_id'],
            'offer_id'         => $tbClick['offer_id'],
            'affiliate_id'     => $tbClick['affiliate_id'],
            'smartlink_id'     => null,
            'sub1'             => '',
            'sub2'             => '',
            'sub3'             => '',
            'sub4'             => '',
            'sub5'             => '',
            'sub6'             => '',
            'source'           => 'traffic_back',
            'landing_page_idx' => null,
            'ip_address'       => $tbClick['ip_address'],
            'user_agent'       => 'Traffic Back Conversion',
            'referer'          => '',
            'country'          => $tbClick['country'],
            'region'           => '',
            'city'             => '',
            'isp'              => '',
            'device_type'      => 'unknown',
            'os'               => '',
            'browser'          => '',
            'is_unique'        => 0,
            'is_fraud'         => 0,
            'fraud_score'      => 0,
            'clicked_at'       => $tbClick['created_at'],
            'payout_amount'    => $tbClick['payout_amount'],
            'revenue_amount'   => $tbClick['revenue_amount'],
            'payout_type'      => $tbClick['payout_type'],
            'advertiser_id'    => $tbClick['advertiser_id'],
            'is_inhouse'       => $tbClick['is_inhouse'],
            'landing_pages'    => $tbClick['landing_pages'],
            'daily_cap'        => $tbClick['daily_cap'],
        ];
    }
}

if (!$click) {
    $_advPbLog['status'] = 'invalid_click'; $_advPbLog['reject_reason'] = 'Click ID not found';
    $_advPbLog['response_body'] = json_encode(['status'=>'error','message'=>'Click ID not found']);
    http_response_code(404);
    echo $_advPbLog['response_body'];
    exit;
}

// Populate advertiser context in log now that we have the click
$_advPbLog['advertiser_id'] = $click['advertiser_id'] ?? null;
$_advPbLog['offer_id']      = $click['offer_id']      ?? null;

// ── In-House Real-Time Risk Engine ──────────────────────────────────────
$riskEngineResult = RiskEngine::evaluateConversion(
    [
        'ip' => $click['ip_address'] ?? '',
        'affiliate_id' => $click['affiliate_id'] ?? 0,
        'offer_id' => $click['offer_id'] ?? 0,
        'click_id' => $clickId
    ],
    [
        'ip' => $click['ip_address'] ?? '',
        'clicked_at' => $click['clicked_at'] ?? date('Y-m-d H:i:s'),
        'country' => $click['country'] ?? ''
    ]
);

$riskEngineReasons = [];
if (!empty($riskEngineResult['reasons'])) {
    $riskEngineReasons = $riskEngineResult['reasons'];
}

if ($riskEngineResult['action'] === 'block') {
    $_advPbLog['status'] = 'blocked'; $_advPbLog['reject_reason'] = 'Conversion blocked by Risk Engine: ' . ($riskEngineResult['block_reason'] ?? 'Rules matched');
    $_advPbLog['response_body'] = json_encode(['status'=>'ignored','message'=>'Conversion blocked by Risk Engine']);
    http_response_code(200);
    echo $_advPbLog['response_body'];
    exit;
}

// ── Block fraudulent or invalid clicks from converting ────────────────────
if ($click['status'] === 'blocked' || $click['is_fraud']) {
    $_advPbLog['status'] = 'blocked'; $_advPbLog['reject_reason'] = 'Click blocked or flagged as fraud';
    $_advPbLog['response_body'] = json_encode(['status'=>'ignored','message'=>'Click blocked or fraud — conversion not recorded']);
    http_response_code(200);
    echo $_advPbLog['response_body'];
    exit;
}

// ── Fraud Blocklist: reject conversions from blocklisted sources ──────────
// Centralised via Blocklist::checkConversion(). Matches against the visitor IP
// stored at click time AND the affiliate that earned the click — so admin can
// block by IP, CIDR, ASN, user agent (recorded UA), affiliate ID, or affiliate
// code. Any active scope=all or scope=conversions entry with action=block
// triggers an immediate reject (hit_count is incremented inside the helper).
$_blAffCode = null;
try {
    if (!empty($click['affiliate_id'])) {
        $_blAffRow = Database::fetchOne("SELECT affiliate_code FROM affiliates WHERE id=?", [$click['affiliate_id']]);
        $_blAffCode = $_blAffRow['affiliate_code'] ?? null;
    }
} catch (\Throwable $_) {}

$_blMatched = Blocklist::checkConversion([
    'ip'             => trim($click['ip_address'] ?? ''),
    'ua'             => (string)($click['user_agent'] ?? ''),
    'affiliate_id'   => (int)($click['affiliate_id'] ?? 0),
    'affiliate_code' => $_blAffCode,
]);
if ($_blMatched) {
    $_advPbLog['status'] = 'blocked';
    $_advPbLog['reject_reason'] = 'Source on fraud blocklist (' . $_blMatched['type'] . ')';
    $_advPbLog['response_body'] = json_encode(['status'=>'ignored','message'=>'Source blocked by admin — conversion not recorded']);
    http_response_code(200);
    echo $_advPbLog['response_body'];
    exit;
}
// ── End Blocklist Enforcement ─────────────────────────────────────────────


// ── Duplicate conversion check ────────────────────────────────────────────
$existing = Database::fetchOne(
    "SELECT id FROM `conversions` WHERE click_id = ?",
    [$clickId]
);
if ($existing) {
    $_advPbLog['status'] = 'duplicate'; $_advPbLog['reject_reason'] = 'Conversion already recorded for this click_id';
    $_advPbLog['response_body'] = json_encode(['status'=>'duplicate','message'=>'Conversion already recorded for this click_id']);
    echo $_advPbLog['response_body'];
    exit;
}

// ── Offer daily conversion cap check ─────────────────────────────────────
if ((int)($click['daily_cap'] ?? 0) > 0) {
    $todayOfferConvs = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM `conversions` WHERE offer_id=? AND DATE(converted_at)=CURDATE() AND status IN ('pending','approved')",
        [$click['offer_id']]
    );
    if ((int)($todayOfferConvs['cnt'] ?? 0) >= (int)$click['daily_cap']) {
        $_advPbLog['status'] = 'capped'; $_advPbLog['reject_reason'] = 'Offer daily conversion cap reached';
        $_advPbLog['response_body'] = '1';
        http_response_code(200);
        echo '1';
        exit;
    }
}

// ── Advanced Payout Management: affiliate daily conversion cap ────────────
try {
    $affId   = (int)$click['affiliate_id'];
    $offerId = (int)$click['offer_id'];
    // Check offer-specific cap first
    $capOffer = Database::fetchOne(
        "SELECT daily_cap FROM aff_daily_caps WHERE affiliate_id=? AND offer_id=? LIMIT 1",
        [$affId, $offerId]
    );
    if ($capOffer && (int)$capOffer['daily_cap'] > 0) {
        $todayCnt = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM conversions WHERE affiliate_id=? AND offer_id=? AND DATE(converted_at)=CURDATE() AND status IN ('pending','approved')",
            [$affId, $offerId]
        );
        if ((int)($todayCnt['cnt'] ?? 0) >= (int)$capOffer['daily_cap']) {
            $_advPbLog['status'] = 'capped'; $_advPbLog['reject_reason'] = 'Affiliate daily offer cap reached';
            $_advPbLog['response_body'] = '1';
            http_response_code(200);
            echo '1'; // cap reached — silently drop
            exit;
        }
    }
    // Then check affiliate-wide cap (no offer restriction)
    $capAll = Database::fetchOne(
        "SELECT daily_cap FROM aff_daily_caps WHERE affiliate_id=? AND offer_id IS NULL LIMIT 1",
        [$affId]
    );
    if ($capAll && (int)$capAll['daily_cap'] > 0) {
        $todayCntAll = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM conversions WHERE affiliate_id=? AND DATE(converted_at)=CURDATE() AND status IN ('pending','approved')",
            [$affId]
        );
        if ((int)($todayCntAll['cnt'] ?? 0) >= (int)$capAll['daily_cap']) {
            $_advPbLog['status'] = 'capped'; $_advPbLog['reject_reason'] = 'Affiliate global daily cap reached';
            $_advPbLog['response_body'] = '1';
            http_response_code(200);
            echo '1'; // global daily cap reached
            exit;
        }
    }
} catch (\Throwable $e) {} // graceful — table may not exist

// ── Resolve payout and revenue ────────────────────────────────────────────
if (($click['payout_type'] ?? '') === 'RevShare') {
    // RevShare: revenue = 100% of what the advertiser actually sent.
    // payout_amount stored on the offer is a percentage (0–100).
    // Affiliate earns payout_amount% of that revenue.
    if ($payout > 0) {
        // Advertiser sent an amount — that IS the 100% revenue base.
        $revenue = $payout;
    } else {
        // Advertiser sent nothing — fall back to stored revenue_amount as base.
        $revenue = (float)$click['revenue_amount'];
    }
    $payout = round(($click['payout_amount'] / 100) * $revenue, 4);
} else {
    // Non-RevShare (CPA/CPL/etc): affiliate payout is fixed at click time.
    // Revenue is the offer's configured gross rate (stored in clicks.revenue at click time).
    // This ensures net_profit = revenue - payout reflects the actual margin.
    // Fallback chain: clicks.revenue → offers.revenue_amount → advertiser postback signal.
    $payout  = (float)$click['payout'];
    $revenue = (float)($click['revenue'] ?? 0) > 0
        ? (float)$click['revenue']
        : ((float)$click['revenue_amount'] > 0 ? (float)$click['revenue_amount'] : (float)$payout);
}

// Determine approval status based on admin config.
// 'auto'   (default) — approved immediately when advertiser fires postback.
// 'manual'           — stored as pending; admin must approve before payout is credited.
$_approvalMode = Config::get('config', 'conversion.approval_mode') ?: 'auto';
$isPending  = ($_approvalMode === 'manual');
$convStatus = $isPending ? 'pending' : 'approved';
$convId     = Helpers::uuid();

// ── Cross-Affiliate Duplicate IP Check ────────────────────────────────────
// If another conversion already exists for this offer and IP (even from another affiliate),
// automatically mark this new one as a duplicate so it appears in the Duplicate Conversions report.
$rejectionReason = '';
if (!empty($click['ip_address']) && $click['ip_address'] !== '0.0.0.0') {
    // Find all existing approved/pending conversions for this IP/Offer
    $existingConvs = Database::fetchAll(
        "SELECT id, conversion_id, affiliate_id, payout, status FROM conversions WHERE offer_id = ? AND ip_address = ? AND status IN ('approved', 'pending')",
        [(int)$click['offer_id'], $click['ip_address']]
    );
    
    // Also check if there's ANY conversion to trigger the duplicate logic
    $existingIpConv = count($existingConvs) > 0 ? true : Database::fetchOne(
        "SELECT id FROM conversions WHERE offer_id = ? AND ip_address = ? LIMIT 1",
        [(int)$click['offer_id'], $click['ip_address']]
    );

    if ($existingIpConv) {
        $convStatus = 'rejected';
        $rejectionReason = 'Duplicate IP on same offer';
        
        // Auto-reject any prior approved/pending conversions in the cluster
        if ($existingConvs) {
            foreach ($existingConvs as $ec) {
                // Update status to rejected
                Database::update('conversions', [
                    'status'           => 'rejected',
                    'rejection_reason' => 'Removed for duplicate conversion',
                    'rejected_at'      => date('Y-m-d H:i:s')
                ], 'id=?', [$ec['id']]);
                
                // If it was already approved, we must reverse the affiliate balance
                if ($ec['status'] === 'approved') {
                    Database::query(
                        "UPDATE affiliates SET balance = balance - ? WHERE id = ?",
                        [$ec['payout'], $ec['affiliate_id']]
                    );
                }
                
                // Attempt to notify the affiliate of the rejection (if class exists/loaded)
                try {
                    if (class_exists('RejectionNotifier')) {
                        RejectionNotifier::afterReject((string)$ec['conversion_id']);
                    }
                } catch (\Throwable $_rn) {}
            }
        }
    }
}

// ── Auto-hide rule check ──────────────────────────────────────────────────
$isAutoHidden = false;
$hideReason   = '';

// Hard-hide traffic_back conversions from managers and affiliates.
if (($click['source'] ?? '') === 'traffic_back') {
    $isAutoHidden = true;
    $hideReason   = 'traffic_back_url';
}

try {
    $hideRules = Database::fetchAll(
        "SELECT * FROM conversion_autohide_rules WHERE is_active=1
         ORDER BY FIELD(type,'affiliate','offer','global')",
        []
    );
    foreach ($hideRules as $rule) {
        $matches = match($rule['type']) {
            'affiliate' => ((int)$rule['affiliate_id'] === (int)$click['affiliate_id']),
            'offer'     => ((int)$rule['offer_id']     === (int)$click['offer_id']),
            'global'    => true,
            default     => false,
        };
        if ($matches) {
            $hidePct  = (float)$rule['hide_percent'];
            // Use activated_at as the count window start so that conversions
            // that arrived while the rule was inactive are not counted — this
            // prevents a burst of hidden conversions when a rule is re-enabled.
            $ruleDate = $rule['activated_at'] ?? $rule['created_at'] ?? '2000-01-01 00:00:00';
            // Deterministic exact-ratio hiding: count conversions in scope since
            // rule creation, hide this one only if hidden count is below the target.
            // This guarantees the actual hide rate stays at exactly hide_percent%
            // rather than fluctuating with random probability.
            [$cntSql, $cntParams] = match($rule['type']) {
                'affiliate' => [
                    "SELECT COUNT(*) as total, COALESCE(SUM(is_hidden),0) as hidden
                       FROM conversions WHERE affiliate_id=? AND converted_at>=?",
                    [(int)$click['affiliate_id'], $ruleDate],
                ],
                'offer' => [
                    "SELECT COUNT(*) as total, COALESCE(SUM(is_hidden),0) as hidden
                       FROM conversions WHERE offer_id=? AND converted_at>=?",
                    [(int)$click['offer_id'], $ruleDate],
                ],
                default => [
                    "SELECT COUNT(*) as total, COALESCE(SUM(is_hidden),0) as hidden
                       FROM conversions WHERE converted_at>=?",
                    [$ruleDate],
                ],
            };
            $ruleStats     = Database::fetchOne($cntSql, $cntParams);
            $totalInclThis = (int)($ruleStats['total'] ?? 0) + 1; // +1 for this conversion
            $targetHidden  = (int)floor($totalInclThis * $hidePct / 100);
            if ((int)($ruleStats['hidden'] ?? 0) < $targetHidden) {
                $isAutoHidden = true;
                $hideReason   = $rule['reason'];
            }
            break;
        }
    }
} catch (\Throwable $e) {
    // auto-hide table not yet created — skip silently
}

// ── Advanced Payout Management: conversion_optimize_rules ─────────────────
// Most specific rule wins: affiliate+offer first, then offer-only, then global
if (!$isAutoHidden) {
    try {
        $optRule = null;
        // 1. Affiliate + Offer specific
        $optRule = Database::fetchOne(
            "SELECT optimize_value FROM conversion_optimize_rules
             WHERE is_active=1 AND offer_id=? AND affiliate_id=? LIMIT 1",
            [(int)$click['offer_id'], (int)$click['affiliate_id']]
        );
        // 2. Offer-specific (no affiliate filter)
        if (!$optRule) {
            $optRule = Database::fetchOne(
                "SELECT optimize_value FROM conversion_optimize_rules
                 WHERE is_active=1 AND offer_id=? AND affiliate_id IS NULL LIMIT 1",
                [(int)$click['offer_id']]
            );
        }
        if ($optRule && (float)$optRule['optimize_value'] > 0) {
            $roll = mt_rand(1, 10000);
            if ($roll <= (float)$optRule['optimize_value'] * 100) {
                $isAutoHidden = true;
                $hideReason   = 'conversion_optimize';
            }
        }
    } catch (\Throwable $e) {}
}

// ── Record conversion ─────────────────────────────────────────────────────
// Ensure required columns exist BEFORE INSERT (safe repeated execution).
PostbackFirer::ensurePostbackSentColumn();
// Allow NULL advertiser_id so in-house offers (is_inhouse=1, no external advertiser) can record conversions.
// Without this MODIFY, the INSERT below fails with NOT NULL constraint for in-house postbacks.
try {
    Database::query("CREATE TABLE IF NOT EXISTS `offer_conversion_history` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id` INT UNSIGNED NOT NULL,
        `offer_id` INT UNSIGNED NOT NULL,
        `visitor_ip` VARCHAR(45) NOT NULL,
        `conversion_time` DATETIME NOT NULL,
        `conversion_status` VARCHAR(20) DEFAULT 'approved',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (`affiliate_id`, `offer_id`, `visitor_ip`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    Database::query("ALTER TABLE `offer_conversion_history` ADD COLUMN `conversion_status` VARCHAR(20) DEFAULT 'approved'");
} catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` MODIFY COLUMN `advertiser_id` INT UNSIGNED NULL DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `hide_reason` VARCHAR(500) NOT NULL DEFAULT ''"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_score`      TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_checked_at` DATETIME         DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_reasons`    TEXT             DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `conv_ip`            VARCHAR(45)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `user_agent`         VARCHAR(512)     DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_risk_score`  TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_risk_level`  VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_vpn`         TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_proxy`       TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_tor`         TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_datacenter`  TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_mobile`      TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_country`     VARCHAR(60)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_country_code` CHAR(2)         DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_city`        VARCHAR(100)     DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_state`       VARCHAR(100)     DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_isp`         VARCHAR(200)     DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_org`         VARCHAR(200)     DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_asn`         VARCHAR(30)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `scamalytics_score`     TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `scamalytics_status`    VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `scamalytics_mode`      VARCHAR(15)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `proxycheck_score`      TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `proxycheck_is_proxy`   TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `proxycheck_is_vpn`     TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `proxycheck_status`     VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `botscout_is_bot`       TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `botscout_count`        SMALLINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `botscout_status`       VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `frauddefense_score`      TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `frauddefense_status`     VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraudlabspro_score`      TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraudlabspro_status`     VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraudlabspro_flp_status` VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `device_brand`            VARCHAR(60)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `device_model`          VARCHAR(120)     DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `os_version`            VARCHAR(40)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `landing_page`          VARCHAR(2000)    DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `referrer`              VARCHAR(2000)    DEFAULT NULL"); } catch (\Throwable $_e) {}

// ── Extract visit metadata from the originating click ────────────────────
$_ua = substr($click['user_agent'] ?? '', 0, 512);

// Device brand + model
$_devBrand = ucfirst($click['device_type'] ?? '');
$_devModel = '';
if ($_ua !== '') {
    if (preg_match('/Android[^;]*;\s*([^;)]+?)(?:\s+Build\/|\s*[;)])/i', $_ua, $_dm)) {
        $_raw = trim($_dm[1]);
        $_brands = ['Samsung','Xiaomi','Huawei','OnePlus','OPPO','Vivo','Realme','Motorola',
                    'Nokia','Sony','LG','HTC','Asus','Google','Pixel','Lenovo','ZTE','Alcatel','TCL','Honor'];
        $_b = '';
        foreach ($_brands as $_bk) { if (stripos($_raw, $_bk) === 0) { $_b = $_bk; break; } }
        if ($_b) { $_devBrand = $_b; $_devModel = trim(substr($_raw, strlen($_b))) ?: $_raw; }
        else     { $_devBrand = 'Android'; $_devModel = $_raw; }
    } elseif (stripos($_ua, 'iPhone')    !== false) { $_devBrand = 'Apple'; $_devModel = 'iPhone'; }
    elseif (stripos($_ua, 'iPad')        !== false) { $_devBrand = 'Apple'; $_devModel = 'iPad'; }
    elseif (stripos($_ua, 'Macintosh')   !== false) { $_devBrand = 'Apple'; $_devModel = 'Mac'; }
    elseif (stripos($_ua, 'Windows')     !== false) { $_devBrand = 'PC';    $_devModel = 'Windows'; }
}

// OS version
$_osVer = '';
if ($_ua !== '') {
    if      (preg_match('/Android\s+([\d.]+)/i', $_ua, $_ov))         { $_osVer = 'Android ' . $_ov[1]; }
    elseif  (preg_match('/iPhone OS ([\d_]+)/i', $_ua, $_ov))         { $_osVer = 'iOS ' . str_replace('_', '.', $_ov[1]); }
    elseif  (preg_match('/iPad.*?OS ([\d_]+)/i', $_ua, $_ov))         { $_osVer = 'iPadOS ' . str_replace('_', '.', $_ov[1]); }
    elseif  (preg_match('/Windows NT ([\d.]+)/i', $_ua, $_ov)) {
        $_ntMap = ['10.0'=>'10/11','6.3'=>'8.1','6.2'=>'8','6.1'=>'7','6.0'=>'Vista','5.1'=>'XP'];
        $_osVer = 'Windows ' . ($_ntMap[$_ov[1]] ?? $_ov[1]);
    }
    elseif  (preg_match('/Mac OS X ([\d_]+)/i', $_ua, $_ov))          { $_osVer = 'macOS ' . str_replace('_', '.', $_ov[1]); }
}

// Landing page URL (resolved from offer's landing_pages JSON array + click's index)
$_lpUrl = '';
$_lpIdx = isset($click['landing_page_idx']) ? (int)$click['landing_page_idx'] : -1;
if ($_lpIdx >= 0 && !empty($click['landing_pages'])) {
    $_lpArr = json_decode($click['landing_pages'], true);
    if (is_array($_lpArr) && isset($_lpArr[$_lpIdx])) {
        $_lpUrl = $_lpArr[$_lpIdx];
    }
}

// Referrer (the page the visitor came from before clicking)
$_referrer = substr($click['referer'] ?? '', 0, 2000);

$newConvDbId = 0;

// ── Resolve geo data for conversion record ─────────────────────────────
// Priority: click record geo → re-run IP geolocation on conversion IP
$_convCountry = $click['country']  ?? '';
$_convCity    = $click['city']     ?? '';
$_convRegion  = $click['region']   ?? '';

// If click didn't have geo data, re-run the lookup using the conversion IP
if ($_convCountry === '' || $_convCity === '') {
    try {
        $_convGeo = Helpers::getGeoInfo($click['ip_address']);
        if ($_convCountry === '') $_convCountry = $_convGeo['country'] ?? '';
        if ($_convCity    === '') $_convCity    = $_convGeo['city']    ?? '';
        if ($_convRegion  === '') $_convRegion  = $_convGeo['region']  ?? '';
    } catch (\Throwable $_geoEx) {}
}

Database::begin();
try {
    $newConvDbId = Database::insert('conversions', [
        'conversion_id'  => $convId,
        'click_id'       => $clickId,
        'offer_id'       => $click['offer_id'],
        'affiliate_id'   => $click['affiliate_id'],
        'advertiser_id'  => $click['advertiser_id'],
        'payout'         => $payout,
        'revenue'        => $revenue,
        'currency'       => 'USD',
        'status'         => $convStatus,
        'transaction_id' => $txnId,
        'goal_name'      => $goalName,
        'ip_address'     => $click['ip_address'],
        'country'        => $_convCountry ?: null,
        'ipquery_country_code' => $_convCountry ?: null,
        'ipquery_city'         => $_convCity    ?: null,
        'ipquery_state'        => $_convRegion  ?: null,
        'is_hidden'      => $isAutoHidden ? 1 : 0,
        'hide_reason'    => $isAutoHidden ? $hideReason : '',
        'rejection_reason'=> !empty($rejectionReason) ? $rejectionReason : null,
        'postback_sent'  => 0,   // explicitly initialised so UPDATE later can reliably set to 1
        'user_agent'     => $_ua ?: null,
        'device_brand'   => $_devBrand ?: null,
        'device_model'   => $_devModel ?: null,
        'device_type'    => $click['device_type'] ?? null,
        'os_version'     => $_osVer ?: null,
        'landing_page'   => $_lpUrl ?: null,
        'referrer'       => $_referrer ?: null,
        'fraud_reasons'  => !empty($riskEngineReasons) ? json_encode($riskEngineReasons) : null,
    ]);

    // Only credit balance and stats when NOT hidden, NOT pending manual approval, and NOT rejected
    if (!$isAutoHidden && !$isPending && $convStatus === 'approved') {
        // Credit affiliate balance
        Database::query(
            "UPDATE `affiliates` SET `balance` = `balance` + ? WHERE `id` = ?",
            [$payout, $click['affiliate_id']]
        );

        // ── Referral commission ───────────────────────────────────────────
        // If this affiliate was referred by another affiliate, credit them too
        try {
            Referral::creditCommission($convId, (int)$click['affiliate_id'], $payout);
        } catch (\Throwable $e) {}

        // Update daily stats
        Database::upsertStats(date('Y-m-d'), $click['affiliate_id'], $click['offer_id'], [
            'conversions' => 1,
            'approved'    => 1,
            'payout'      => $payout,
            'revenue'     => $revenue,
        ]);

        // ── Advertiser budget deduction (Offer Budget System) ─────────────
        // Deducts the conversion REVENUE (advertiser cost) from offers.budget_spent
        // and advertisers.balance. This ensures the advertiser's balance reflects
        // the true spend (revenue_today), not just the affiliate payout.
        // Auto-pauses the offer at zero budget, and fires the low-balance
        // notification + email when advertiser balance dips below threshold.
        // No-ops cleanly when the budget system is disabled.
        try {
            AdvBudget::applyConversionDeduction((int)$click['offer_id'], (float)$revenue);
        } catch (\Throwable $e) {
            PostbackFirer::log('[postback.php] AdvBudget deduction failed: ' . $e->getMessage());
        }

        // ── IP Conversion Protection System (One per IP) ─────────────────
        try {
            $visitorIp = trim($click['ip_address'] ?? '');
            if ($visitorIp !== '' && $visitorIp !== '0.0.0.0' && $convStatus === 'approved') {
                Database::insert('offer_conversion_history', [
                    'affiliate_id'      => (int)$click['affiliate_id'],
                    'offer_id'          => (int)$click['offer_id'],
                    'visitor_ip'        => $visitorIp,
                    'conversion_time'   => date('Y-m-d H:i:s'),
                    'conversion_status' => 'approved'
                ]);
            }
        } catch (\Throwable $e) {
            PostbackFirer::log('[postback.php] IP Conversion Protection History insertion failed: ' . $e->getMessage());
        }
    }

    Database::commit();
    // Mark log as accepted now that the DB insert succeeded
    $_advPbLog['status']        = $isAutoHidden ? 'hidden' : ($isPending ? 'pending' : 'accepted');
    $_advPbLog['conversion_id'] = $convId;
    $_advPbLog['payout']        = $payout;
    if ($isAutoHidden) $_advPbLog['reject_reason'] = 'Auto-hidden: ' . $hideReason;

    // Send Push Notification
    if (!$isAutoHidden && !$isPending && $convStatus === 'approved') {
        try {
            require_once BASE_PATH . '/core/NotificationHelper.php';
            
            // Notify Affiliate
            $notifTitle = "New Conversion!";
            $notifMsg = "You earned $" . number_format($payout, 2) . " from offer #{$click['offer_id']}.";
            NotificationHelper::notifyUser((int)$click['affiliate_id'], $notifTitle, $notifMsg, 'conversion', '/affiliate/reports', ['offer_id' => (string)$click['offer_id']]);

            // Notify Admin
            $adminMsg = "Affiliate #{$click['affiliate_id']} generated a conversion on offer #{$click['offer_id']} for $" . number_format($revenue, 2) . " revenue.";
            NotificationHelper::notifyRole('admin', "New Conversion", $adminMsg, 'conversion', '/admin/reports/conversions');

            // Notify Manager (if assigned)
            $managerRow = Database::fetchOne("SELECT manager_id FROM affiliates WHERE user_id=?", [(int)$click['affiliate_id']]);
            if ($managerRow && !empty($managerRow['manager_id'])) {
                NotificationHelper::notifyUser((int)$managerRow['manager_id'], "New Conversion", $adminMsg, 'conversion', '/manager/reports/conversions');
            }

        } catch (\Throwable $e) {
            PostbackFirer::log('[postback.php] FCM push failed: ' . $e->getMessage());
        }
    }
} catch (\Exception $e) {
    Database::rollback();
    $_advPbLog['status'] = 'error'; $_advPbLog['reject_reason'] = 'DB error recording conversion';
    $_advPbLog['response_body'] = json_encode(['status'=>'error','message'=>'Internal server error recording conversion']);
    http_response_code(500);
    echo $_advPbLog['response_body'];
    exit;
}

// ── Resolve the real visitor IP for fraud checks ──────────────────────────
// Source: $click['ip_address'] = visitor IP stored at click time by Helpers::getIp().
// $_SERVER['REMOTE_ADDR'] here is the advertiser's postback server IP — never use it.
require_once BASE_PATH . '/core/FraudIQ.php';

// ── Auto-initialize fraud config if fraud.json doesn't exist ─────────────
// Without this file Config::get('fraud') returns null, disabling all providers.
// IPQuery.io is free (no key required) so we enable it by default on first run.
$_fraudCfgPath = BASE_PATH . '/config/fraud.json';
if (!file_exists($_fraudCfgPath)) {
    $dir = dirname($_fraudCfgPath);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents($_fraudCfgPath, json_encode([
        'mode'                    => 'score_only',
        'check_on_conversion'     => true,
        'ipquery_enabled'         => true,
        'ipquery_mode'            => 'score_only',
        'ipquery_block_threshold' => 80,
        'ipquery_flag_threshold'  => 50,
        'ipqs_enabled'            => false,
        'ipqs_api_key'            => '',
        'scamalytics_enabled'     => false,
        'scamalytics_api_key'     => '',
        'proxycheck_enabled'      => false,
        'proxycheck_api_key'      => '',
        'botscout_enabled'        => false,
        'botscout_api_key'        => '',
        'frauddefense_enabled'    => false,
        'frauddefense_api_key'    => '',
        'fraudlabspro_enabled'    => false,
        'fraudlabspro_api_key'    => '',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    PostbackFirer::log('[postback.php] fraud.json not found — created default config with IPQuery.io enabled.');
}

$_convIp = trim($click['ip_address'] ?? '');
if (strpos($_convIp, '/') !== false) {
    $_convIp = explode('/', $_convIp)[0];
}

// Classify the IP so we know whether fraud APIs can actually use it:
//   'public'  — valid, routable, non-private → safe to send to all APIs
//   'private' — valid IP but RFC-1918/loopback (e.g. 192.168.x.x, 127.0.0.1)
//               → only IPQuery (free, tolerant) should attempt; others skip
//   'invalid' — empty, 0.0.0.0, or non-IP string → skip all API calls
$_ipStatus = 'invalid';
if ($_convIp !== '' && $_convIp !== '0.0.0.0') {
    if (filter_var($_convIp, FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
        $_ipStatus = 'public';
    } elseif (filter_var($_convIp, FILTER_VALIDATE_IP) !== false) {
        $_ipStatus = 'private';
    }
}
$_ipIsPublic = ($_ipStatus === 'public');

PostbackFirer::log('[postback.php] IP resolution — conv=' . $convId
    . ' stored_ip=' . ($_convIp ?: 'EMPTY')
    . ' status=' . $_ipStatus);

// ── IPQuery.io: real-time fraud detection for EVERY conversion ────────────
// Runs immediately after the conversion row is committed, before any early
// exits (hidden / pending / auto-approved paths) so every conversion always
// gets a fraud score. Results are stored as separate ipquery_* columns and
// never interfere with the existing IPQS / FraudIQ logic below.
//
// IMPORTANT: The API call and the DB write are separated.
// The DB UPDATE always runs regardless of API success or IP validity so that
// ipquery_risk_score is NEVER left as NULL — every conversion appears in the
// IPQuery report (score=0 for invalid IPs or API failures).
$_ipqResult = ['risk_score' => 0, 'risk_level' => 'low', 'status' => 'allowed', 'mode' => 'score_only',
               'is_vpn' => 0, 'is_proxy' => 0, 'is_tor' => 0, 'is_datacenter' => 0,
               'is_mobile' => 0, 'country' => '', 'country_code' => '', 'city' => '',
               'state' => '', 'timezone' => '', 'isp' => '', 'org' => '', 'asn' => ''];

// Step 1 — Call API only when we have a valid IP (public or private).
// For invalid/empty IPs the default score=0 is used — no wasted API call.
if ($newConvDbId > 0 && $_ipStatus !== 'invalid') {
    try {
        $_ipqResult = FraudIQ::checkIPQuery($_convIp);
        PostbackFirer::log('[postback.php] IPQuery done — conv=' . $convId
            . ' ip=' . $_convIp
            . ' score=' . $_ipqResult['risk_score']
            . ' level=' . $_ipqResult['risk_level']
            . ' vpn='   . $_ipqResult['is_vpn']
            . ' proxy=' . $_ipqResult['is_proxy']
            . ' tor='   . $_ipqResult['is_tor']
            . ' dc='    . $_ipqResult['is_datacenter']);
    } catch (\Throwable $_ipqEx) {
        PostbackFirer::log('[postback.php] IPQuery API call failed — conv=' . $convId
            . ' ip=' . $_convIp . ': ' . $_ipqEx->getMessage() . ' (score=0 saved)');
        // $_ipqResult stays at defaults — score=0 will be written below
    }
} else {
    PostbackFirer::log('[postback.php] IPQuery skipped — conv=' . $convId
        . ' ip_status=' . $_ipStatus . ' (score=0 saved)');
}

// Step 2 — Always write the result to DB (score=0 if API failed or IP was invalid).
// This guarantees ipquery_risk_score is NEVER NULL, so every conversion is visible
// in the IPQuery report regardless of API availability or IP validity.
if ($newConvDbId > 0) {
    try {
        Database::query(
            "UPDATE `conversions`
                SET `ipquery_risk_score`   = ?,
                    `ipquery_risk_level`   = ?,
                    `ipquery_vpn`          = ?,
                    `ipquery_proxy`        = ?,
                    `ipquery_tor`          = ?,
                    `ipquery_datacenter`   = ?,
                    `ipquery_mobile`       = ?,
                    `ipquery_country`      = ?,
                    `ipquery_country_code` = ?,
                    `ipquery_city`         = ?,
                    `ipquery_state`        = ?,
                    `ipquery_isp`          = ?,
                    `ipquery_org`          = ?,
                    `ipquery_asn`          = ?
              WHERE `conversion_id` = ?",
            [
                $_ipqResult['risk_score'],
                $_ipqResult['risk_level'],
                $_ipqResult['is_vpn'],
                $_ipqResult['is_proxy'],
                $_ipqResult['is_tor'],
                $_ipqResult['is_datacenter'],
                $_ipqResult['is_mobile'],
                $_ipqResult['country'],
                $_ipqResult['country_code'],
                $_ipqResult['city'],
                $_ipqResult['state'],
                $_ipqResult['isp'],
                $_ipqResult['org'],
                $_ipqResult['asn'],
                $convId,
            ]
        );
    } catch (\Throwable $_ipqDbEx) {
        PostbackFirer::log('[postback.php] IPQuery DB save failed — conv=' . $convId . ': ' . $_ipqDbEx->getMessage());
    }
}

// ── Scamalytics: score ALL conversions ───────────────────────────────────
// Runs for every conversion (hidden, pending, auto-approved) immediately after
// the IPQuery check. Score + status + mode are always saved to the conversion
// row. Actual blocking (auto_block mode) is applied further below for
// auto-approved conversions only — hidden and pending conversions exit before
// reaching that point so they are scored but never blocked here.
$_scamResult = ['score' => 0, 'status' => 'allowed', 'mode' => 'score_only'];
if ($newConvDbId > 0 && $_ipIsPublic) {
    try {
        $_scamResult = FraudIQ::checkFraud($_convIp);
        PostbackFirer::log('[postback.php] Scamalytics done — conv=' . $convId
            . ' ip=' . $_convIp
            . ' score=' . $_scamResult['score']
            . ' status=' . $_scamResult['status']
            . ' mode=' . $_scamResult['mode']);
    } catch (\Throwable $_scamEx) {
        PostbackFirer::log('[postback.php] Scamalytics API failed — conv=' . $convId . ': ' . $_scamEx->getMessage() . ' (score=0 saved)');
    }
}
if ($newConvDbId > 0) {
    try {
        Database::query(
            "UPDATE `conversions` SET `scamalytics_score`=?,`scamalytics_status`=?,`scamalytics_mode`=? WHERE `conversion_id`=?",
            [$_scamResult['score'], $_scamResult['status'], $_scamResult['mode'], $convId]
        );
    } catch (\Throwable $_scamDbEx) {
        PostbackFirer::log('[postback.php] Scamalytics DB save failed — conv=' . $convId . ': ' . $_scamDbEx->getMessage());
    }
}

// ── ProxyCheck.io: real-time fraud scoring for every conversion ───────────
$_pcResult = ['score' => 0, 'is_proxy' => 0, 'is_vpn' => 0, 'status' => 'allowed', 'mode' => 'score_only'];
if ($newConvDbId > 0 && $_ipIsPublic) {
    try {
        $_pcResult = FraudIQ::checkProxyCheck($_convIp);
        PostbackFirer::log('[postback.php] ProxyCheck done — conv=' . $convId . ' ip=' . $_convIp . ' score=' . $_pcResult['score'] . ' proxy=' . $_pcResult['is_proxy'] . ' vpn=' . $_pcResult['is_vpn']);
    } catch (\Throwable $_pcEx) {
        PostbackFirer::log('[postback.php] ProxyCheck API failed — conv=' . $convId . ': ' . $_pcEx->getMessage() . ' (score=0 saved)');
    }
}
if ($newConvDbId > 0) {
    try {
        Database::query(
            "UPDATE `conversions` SET `proxycheck_score`=?,`proxycheck_is_proxy`=?,`proxycheck_is_vpn`=?,`proxycheck_status`=? WHERE `conversion_id`=?",
            [$_pcResult['score'], $_pcResult['is_proxy'], $_pcResult['is_vpn'], $_pcResult['status'], $convId]
        );
    } catch (\Throwable $_pcDbEx) {
        PostbackFirer::log('[postback.php] ProxyCheck DB save failed — conv=' . $convId . ': ' . $_pcDbEx->getMessage());
    }
}

// ── BotScout: real-time bot detection for every conversion ────────────────
$_bsResult = ['is_bot' => 0, 'count' => 0, 'status' => 'allowed', 'mode' => 'score_only'];
if ($newConvDbId > 0 && $_ipIsPublic) {
    try {
        $_bsResult = FraudIQ::checkBotScout($_convIp);
        PostbackFirer::log('[postback.php] BotScout done — conv=' . $convId . ' ip=' . $_convIp . ' is_bot=' . $_bsResult['is_bot'] . ' count=' . $_bsResult['count']);
    } catch (\Throwable $_bsEx) {
        PostbackFirer::log('[postback.php] BotScout API failed — conv=' . $convId . ': ' . $_bsEx->getMessage() . ' (score=0 saved)');
    }
}
if ($newConvDbId > 0) {
    try {
        Database::query(
            "UPDATE `conversions` SET `botscout_is_bot`=?,`botscout_count`=?,`botscout_status`=? WHERE `conversion_id`=?",
            [$_bsResult['is_bot'], $_bsResult['count'], $_bsResult['status'], $convId]
        );
    } catch (\Throwable $_bsDbEx) {
        PostbackFirer::log('[postback.php] BotScout DB save failed — conv=' . $convId . ': ' . $_bsDbEx->getMessage());
    }
}

// ── FraudDefense.io: real-time fraud scoring for every conversion ─────────
$_fdResult = ['score' => 0, 'status' => 'allowed', 'mode' => 'score_only'];
if ($newConvDbId > 0 && $_ipIsPublic) {
    try {
        $_fdResult = FraudIQ::checkFraudDefense($_convIp);
        PostbackFirer::log('[postback.php] FraudDefense done — conv=' . $convId . ' ip=' . $_convIp . ' score=' . $_fdResult['score'] . ' status=' . $_fdResult['status']);
    } catch (\Throwable $_fdEx) {
        PostbackFirer::log('[postback.php] FraudDefense API failed — conv=' . $convId . ': ' . $_fdEx->getMessage() . ' (score=0 saved)');
    }
}
if ($newConvDbId > 0) {
    try {
        Database::query(
            "UPDATE `conversions` SET `frauddefense_score`=?,`frauddefense_status`=? WHERE `conversion_id`=?",
            [$_fdResult['score'], $_fdResult['status'], $convId]
        );
    } catch (\Throwable $_fdDbEx) {
        PostbackFirer::log('[postback.php] FraudDefense DB save failed — conv=' . $convId . ': ' . $_fdDbEx->getMessage());
    }
}

// ── FraudLabs Pro: real-time fraud scoring for every conversion ───────────
$_flpResult = ['score' => 0, 'status' => 'allowed', 'mode' => 'score_only', 'flp_status' => ''];
if ($newConvDbId > 0 && $_ipIsPublic) {
    try {
        $_flpResult = FraudIQ::checkFraudLabsPro($_convIp);
        PostbackFirer::log('[postback.php] FraudLabsPro done — conv=' . $convId . ' ip=' . $_convIp . ' score=' . $_flpResult['score'] . ' flp_status=' . $_flpResult['flp_status']);
    } catch (\Throwable $_flpEx) {
        PostbackFirer::log('[postback.php] FraudLabsPro API failed — conv=' . $convId . ': ' . $_flpEx->getMessage() . ' (score=0 saved)');
    }
}
if ($newConvDbId > 0) {
    try {
        Database::query(
            "UPDATE `conversions` SET `fraudlabspro_score`=?,`fraudlabspro_status`=?,`fraudlabspro_flp_status`=? WHERE `conversion_id`=?",
            [$_flpResult['score'], $_flpResult['status'], $_flpResult['flp_status'], $convId]
        );
    } catch (\Throwable $_flpDbEx) {
        PostbackFirer::log('[postback.php] FraudLabsPro DB save failed — conv=' . $convId . ': ' . $_flpDbEx->getMessage());
    }
}

// ── Fraud IQ: score ALL conversions (including hidden + pending) ─────────
// NOTE: We do NOT pre-stamp fraud_checked_at here any more.
// Stamping it early caused conversions to appear "already checked" before
// the multi-provider checks (IPQuery, Scamalytics, ProxyCheck, FraudDefense,
// FraudLabsPro) ran, so the Re-check Pending button would skip them forever.
// fraud_checked_at is now only set AFTER the IPQS check (or on confirmed failure).

// Run the fraud API for EVERY conversion so no scores are left as "Pending".
// For hidden and pending conversions we use score-only (never block) and then
// continue to their normal early exit. This ensures admins always see a fraud
// score when reviewing hidden or pending conversions in the fraud report.
// $_convIp and $_ipIsPublic were already resolved above for the provider checks.
// Re-use $_convIp here so IPQS also uses the real visitor IP (never the advertiser's REMOTE_ADDR).
$_fraudCheckIp = $_convIp;
PostbackFirer::log('[postback.php] FraudIQ check starting for conv ' . $convId . ' ip=' . $_fraudCheckIp);

// ── Auto-Block enforcement (runs for EVERY conversion) ────────────────────
// The provider scoring above already ran for hidden + pending + approved
// conversions. Now apply auto_block decisions BEFORE the pending/hidden
// early exits, so Auto Block actually rejects fraud regardless of the
// conversion's approval status. When a provider has status='blocked' the
// dedicated checkXxxConversion() helper marks the conversion as fraud,
// reverses any credited balance, marks the click as blocked, and we exit
// without firing any postback.
$_autoBlockChecks = [
    'Scamalytics'   => ['result' => $_scamResult, 'method' => 'checkScamalyticsConversion'],
    'IPQuery.io'    => ['result' => $_ipqResult,  'method' => 'checkIPQueryConversion',  'score_key' => 'risk_score'],
    'ProxyCheck.io' => ['result' => $_pcResult,   'method' => 'checkProxyCheckConversion'],
    'BotScout'      => ['result' => $_bsResult,   'method' => 'checkBotScoutConversion'],
    'FraudDefense'  => ['result' => $_fdResult,   'method' => 'checkFraudDefenseConversion'],
    'FraudLabsPro'  => ['result' => $_flpResult,  'method' => 'checkFraudLabsProConversion'],
];
foreach ($_autoBlockChecks as $_provName => $_provData) {
    if (($_provData['result']['status'] ?? 'allowed') !== 'blocked') continue;
    $_blocked = false;
    try {
        $_blocked = FraudIQ::{$_provData['method']}(
            $_convIp,
            $convId,
            $payout,
            (int)$click['affiliate_id'],
            $clickId
        );
    } catch (\Throwable $_abEx) {
        PostbackFirer::log('[postback.php] ' . $_provName . ' auto-block failed — conv=' . $convId . ': ' . $_abEx->getMessage());
    }
    if ($_blocked) {
        $_score = (int)($_provData['result'][$_provData['score_key'] ?? 'score'] ?? 0);
        PostbackFirer::log('[postback.php] Conversion ' . $convId . ' AUTO-BLOCKED by ' . $_provName
            . ' (score=' . $_score . ', conv_status=' . $convStatus . ')');
        $_advPbLog['status']        = 'fraud_blocked';
        $_advPbLog['reject_reason'] = 'Auto-blocked by ' . $_provName . ' (score=' . $_score . ')';
        $_advPbLog['response_body'] = json_encode(['status' => 'fraud', 'message' => 'Conversion auto-blocked by ' . $_provName . ' fraud check']);
        http_response_code(200);
        echo $_advPbLog['response_body'];
        exit;
    }
}
// ── End Auto-Block enforcement ────────────────────────────────────────────

// Hidden conversion — record fraud score then stop
if ($isAutoHidden) {
    try {
        $_fraudCfg   = Config::get('fraud') ?? [];
        $_ipqsActive = !empty($_fraudCfg['ipqs_api_key']) && (bool)($_fraudCfg['ipqs_enabled'] ?? false);
        if ($_ipqsActive) {
            $_fraudResult = FraudIQ::checkIPQS($_fraudCheckIp, $clickId);
            $_fraudScore  = (int)($_fraudResult['score'] ?? 0);
            Database::query(
                "UPDATE `conversions` SET `fraud_score`=?, `fraud_checked_at`=NOW() WHERE `conversion_id`=?",
                [$_fraudScore, $convId]
            );
            PostbackFirer::log('[postback.php] IPQS (hidden) conv=' . $convId . ' score=' . $_fraudScore);
        } else {
            Database::query(
                "UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL",
                [$convId]
            );
        }
    } catch (\Throwable $_fraudEx) {
        PostbackFirer::log('[postback.php] IPQS (hidden) error: ' . $_fraudEx->getMessage());
        try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$convId]); } catch (\Throwable $_e) {}
    }
    $_advPbLog['response_body'] = '1';
    echo '1';
    exit;
}

// Pending (manual approval mode) — record fraud score but don't block.
// Admin will review score when approving/rejecting.
if ($isPending) {
    try {
        $_fraudCfg   = Config::get('fraud') ?? [];
        $_ipqsActive = !empty($_fraudCfg['ipqs_api_key']) && (bool)($_fraudCfg['ipqs_enabled'] ?? false);
        if ($_ipqsActive) {
            $_fraudResult = FraudIQ::checkIPQS($_fraudCheckIp, $clickId);
            $_fraudScore  = (int)($_fraudResult['score'] ?? 0);
            Database::query(
                "UPDATE `conversions` SET `fraud_score`=?, `fraud_checked_at`=NOW() WHERE `conversion_id`=?",
                [$_fraudScore, $convId]
            );
            PostbackFirer::log('[postback.php] IPQS (pending) conv=' . $convId . ' score=' . $_fraudScore);
        } else {
            Database::query(
                "UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL",
                [$convId]
            );
        }
    } catch (\Throwable $_fraudEx) {
        PostbackFirer::log('[postback.php] IPQS (pending) error: ' . $_fraudEx->getMessage());
        try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$convId]); } catch (\Throwable $_e) {}
    }
    $_advPbLog['response_body'] = '1';
    echo '1';
    exit;
}

// ── Fraud IQ: synchronous pre-postback check (auto-approved conversions) ─
// Runs BEFORE postbacks fire and BEFORE fastcgi_finish_request so the score
// is guaranteed to be saved on all server types (shared hosting, LiteSpeed,
// mod_php, PHP-FPM). In Score-Only mode: saves score, never blocks.
// In Block mode: fraudulent conversions are flagged before affiliate postbacks
// fire, preventing payouts to affiliates for confirmed fraud.
// (FraudIQ.php already required above; $fraudCheckIp already set)
$fraudBlocked = false;
$fraudCheckIp = $_fraudCheckIp;
try {
    $fraudBlocked = FraudIQ::checkConversion(
        $fraudCheckIp,
        $clickId,
        $convId,
        $payout,
        (int)$click['affiliate_id']
    );
    PostbackFirer::log('[postback.php] FraudIQ check done for conv ' . $convId . ' blocked=' . ($fraudBlocked ? 'yes' : 'no'));
    // Real-time affiliate notification when fraud_score >= High Risk threshold (60).
    try { FraudAutoNotify::afterCheck($convId); } catch (\Throwable $_naEx) {}
} catch (\Throwable $fraudEx) {
    PostbackFirer::log('[postback.php] FraudIQ::checkConversion() error for conv ' . $convId . ': ' . $fraudEx->getMessage());
    // Ensure fraud_checked_at is stamped even on exception so conversion stays out of "Pending"
    try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$convId]); } catch (\Throwable $_e) {}
}
if ($fraudBlocked) {
    PostbackFirer::log('[postback.php] Conversion ' . $convId . ' blocked by Fraud IQ.');
    $_advPbLog['status'] = 'fraud_blocked'; $_advPbLog['reject_reason'] = 'Blocked by Fraud IQ (Block mode)';
    $_advPbLog['response_body'] = json_encode(['status' => 'fraud', 'message' => 'Conversion flagged by fraud check']);
    http_response_code(200);
    echo $_advPbLog['response_body'];
    exit;
}

// Per-provider auto-block enforcement (Scamalytics, IPQuery.io, ProxyCheck.io,
// BotScout, FraudDefense.io, FraudLabs Pro) is handled by the consolidated
// $_autoBlockChecks loop earlier in this file — it runs for EVERY conversion
// (hidden, pending, auto-approved) after each provider's API check has
// completed and its score has been saved. Each provider blocks only when its
// own status == 'blocked' (i.e. admin enabled Auto Block AND the score crossed
// the configured threshold / a fraud signal was detected). Once a provider
// triggers a block, that loop exits — so this code path runs only for
// conversions that no provider asked to block.

// ── Fire all postbacks SYNCHRONOUSLY before sending any response ───────────
// This is the only reliable way to guarantee postback delivery on ALL server
// types (mod_php, PHP-FPM, shared hosting). The advertiser waits for our
// HTTP 200 — we use that window to fire the affiliate postback first.
// Total budget: PostbackFirer uses a 10 s cURL timeout per URL.
$conversionForPostback = [
    'conversion_id' => $convId,
    'click_id'      => $clickId,
    'offer_id'      => $click['offer_id'],
    'affiliate_id'  => $click['affiliate_id'],
    'payout'        => $payout,
    'revenue'       => $revenue,
    'status'        => $convStatus,
    'source'        => $click['source'] ?? '',
    'sub1'          => $click['sub1'] ?? '',
    'sub2'          => $click['sub2'] ?? '',
    'sub3'          => $click['sub3'] ?? '',
    'sub4'          => $click['sub4'] ?? '',
    'sub5'          => $click['sub5'] ?? '',
    'sub6'          => $click['sub6'] ?? '',
];

try {
    PostbackFirer::fireAll($conversionForPostback, $convStatus, true);
    PostbackFirer::log('[postback.php] fireAll() completed synchronously for conv ' . $convId);
} catch (\Throwable $pbEx) {
    PostbackFirer::log('[postback.php] Unexpected PostbackFirer exception: ' . $pbEx->getMessage());
}

// ── Manager commission: record synchronously before response is sent ──────
// Must run here — BEFORE fastcgi_finish_request() — so it executes on every
// server type (mod_php, PHP-FPM, shared hosting, LiteSpeed).
// Placing it after fastcgi_finish_request() relies on the PHP process
// continuing to run after the FastCGI connection closes, which is NOT
// guaranteed on all servers; on many hosts the process terminates there,
// silently dropping the commission entirely.
if ($newConvDbId > 0) {
    try {
        ManagerCommissionService::recordForConversion($newConvDbId);
        PostbackFirer::log('[postback.php] ManagerCommissionService done for conv ' . $convId);
    } catch (\Throwable $e) {
        PostbackFirer::log('[postback.php] ManagerCommissionService error for conv ' . $convId . ': ' . $e->getMessage());
    }
    // ── Points + Rewards (READ-ONLY against conversions) ─────────────────
    // Both services are no-ops if disabled, no-ops for non-approved rows,
    // and idempotent (de-duped by conversion id / rule id). Wrapped in a
    // try/catch so they can NEVER break the postback flow.
    try {
        PointsService::onConversionApproved($newConvDbId);
        $_affRow = Database::fetchOne("SELECT affiliate_id FROM conversions WHERE id=?", [$newConvDbId]);
        if ($_affRow && (int)$_affRow['affiliate_id'] > 0) {
            RewardsService::checkAndGrant((int)$_affRow['affiliate_id']);
        }
    } catch (\Throwable $e) {
        PostbackFirer::log('[postback.php] Points/Rewards hook error for conv ' . $convId . ': ' . $e->getMessage());
    }
}

// ── Send success response to advertiser ───────────────────────────────────
$responseBody = json_encode([
    'status'        => 'success',
    'message'       => 'Conversion recorded',
    'conversion_id' => $convId,
    'payout'        => $payout,
]);
$_advPbLog['status']        = 'accepted';
$_advPbLog['response_body'] = $responseBody;
header('Content-Type: application/json');
header('Content-Length: ' . strlen($responseBody));
header('Connection: close');
echo $responseBody;

// Flush output to network — works for both PHP-FPM and mod_php
if (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

// PHP-FPM: close the FastCGI connection so the client gets the response now
// while the PHP process continues running below for non-critical tasks.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// ── Auto-pause offer if CR drops below threshold ──────────────────────────
try {
    $offerFull = Database::fetchOne(
        "SELECT id, auto_pause_cr, auto_pause_min_clicks, status FROM offers WHERE id=?",
        [$click['offer_id']]
    );
    if ($offerFull && $offerFull['auto_pause_cr'] > 0 && $offerFull['status'] === 'active') {
        $minClicks = max(10, (int)$offerFull['auto_pause_min_clicks']);
        $crStats   = Database::fetchOne(
            "SELECT SUM(clicks) as total_clicks, SUM(conversions) as total_conv
             FROM stats_daily
             WHERE offer_id=? AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            [$click['offer_id']]
        );
        $totalClicks = (int)($crStats['total_clicks'] ?? 0);
        $totalConv   = (int)($crStats['total_conv']   ?? 0);
        if ($totalClicks >= $minClicks) {
            $actualCr = $totalClicks > 0 ? ($totalConv / $totalClicks) * 100 : 0;
            if ($actualCr < (float)$offerFull['auto_pause_cr']) {
                Database::update('offers', ['status' => 'paused'], 'id=?', [$click['offer_id']]);
                Database::insert('notifications', [
                    'user_id'     => null,
                    'target_role' => 'admin',
                    'type'        => 'warning',
                    'title'       => 'Offer Auto-Paused',
                    'message'     => 'Offer #' . $click['offer_id'] . ' auto-paused. CR: ' . round($actualCr, 2) . '% (threshold: ' . $offerFull['auto_pause_cr'] . '%)',
                    'link'        => '/admin/offers/' . $click['offer_id'],
                ]);
            }
        }
    }
} catch (\Throwable $e) {
    PostbackFirer::log('[postback.php] Auto-pause check failed (non-critical): ' . $e->getMessage());
}

// ── Admin notification ────────────────────────────────────────────────────
try {
    Database::insert('notifications', [
        'user_id'     => null,
        'target_role' => 'admin',
        'type'        => 'success',
        'title'       => 'New Conversion',
        'message'     => 'Conversion recorded for click ' . substr($clickId, 0, 8) . '... Payout: $' . number_format($payout, 2),
        'link'        => '/admin/conversions',
    ]);
} catch (\Throwable $e) {
    PostbackFirer::log('[postback.php] Admin notification insert failed (non-critical): ' . $e->getMessage());
}

// ── Trigger Background Tasks (Fraud Scan & Points Sync) ────────────────────
try {
    if (function_exists('exec') && is_callable('exec') && false === stripos(ini_get('disable_functions'), 'exec')) {
        exec('php ' . escapeshellarg(BASE_PATH . '/cron/fraud_scan.php') . ' > /dev/null 2>&1 &');
        exec('php ' . escapeshellarg(BASE_PATH . '/cron/sync_points.php') . ' > /dev/null 2>&1 &');
    } else {
        // Fallback to async HTTP request if exec is disabled
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $urls = [];
        $fraudToken = \Config::get('config', 'app.fraud_scan_cron_token');
        if ($fraudToken) $urls[] = $scheme . '://' . $host . '/cron/fraud-scan?token=' . $fraudToken;
        
        $cronToken = \Config::get('config', 'cron.secret') ?: $fraudToken;
        if ($cronToken) $urls[] = $scheme . '://' . $host . '/cron/sync-points?token=' . $cronToken;
        
        foreach ($urls as $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_exec($ch);
            curl_close($ch);
        }
    }
} catch (\Throwable $e) {}

exit;
