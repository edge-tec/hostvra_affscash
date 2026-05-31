<?php
Auth::check('admin');
$pageTitle = 'Fraud Score Report';

// Ensure required columns exist (auto-migration — safe to run on every page load)
try { PostbackFirer::ensurePostbackSentColumn(); } catch (\Throwable $e) {}
// fraud_checked_at: NULL = never checked, non-NULL = checked (even if score was 0)
try {
    Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_checked_at` DATETIME DEFAULT NULL");
} catch (\Throwable $_e) {
    // Column already exists — ignore. MySQL throws error, MariaDB silently skips.
}
// fraud_score on conversions: ensure it allows NULL so we can distinguish "not checked" from "checked = 0"
try {
    Database::query("ALTER TABLE `conversions` MODIFY COLUMN `fraud_score` TINYINT UNSIGNED DEFAULT NULL");
} catch (\Throwable $_e) {}

// AJAX: live tick — process up to N unchecked conversions on each call and
// return the freshly-computed score deltas. The view loops this in the
// background, so admins see scores appear in-place without a full page
// reload or a manual "Re-check" click. This is the engine behind the
// "Live • streaming" indicator on the report.
//
// Designed to be cheap: small batch (default 5) + bounded execution time,
// safe to fire on every poll. Returns:
//   { processed, remaining, updates: { conversion_id: { fraud_score, level, ... } } }
if (Helpers::isPost() && Helpers::post('action') === 'tick') {
    header('Content-Type: application/json');
    require_once BASE_PATH . '/core/FraudIQ.php';
    @set_time_limit(30);

    $cfg = Config::get('fraud') ?? [];
    $batch = (int)Helpers::post('batch');
    if ($batch < 1)  $batch = 5;
    if ($batch > 25) $batch = 25;

    // Same selection rule as recheck_pending — picks rows missing any
    // provider score within the last 30 days, newest first.
    $pending = Database::fetchAll(
        "SELECT id, conversion_id, click_id, ip_address, affiliate_id, payout
         FROM conversions
         WHERE (fraud_checked_at IS NULL
                OR ipquery_risk_score IS NULL
                OR scamalytics_score IS NULL
                OR proxycheck_score IS NULL
                OR frauddefense_score IS NULL
                OR fraudlabspro_score IS NULL)
           AND COALESCE(is_hidden, 0) = 0
           AND converted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
         ORDER BY converted_at DESC
         LIMIT $batch"
    ) ?: [];

    $updates = [];
    foreach ($pending as $conv) {
        $ip = trim($conv['ip_address'] ?? '');
        $ipValid  = ($ip !== '' && $ip !== '0.0.0.0' && filter_var($ip, FILTER_VALIDATE_IP) !== false);
        $ipPublic = $ipValid && filter_var($ip, FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

        // 1) IPQuery.io — free, no key
        if ($ipValid) {
            try {
                $_ipqR = FraudIQ::checkIPQuery($ip);
                Database::query(
                    "UPDATE `conversions` SET `ipquery_risk_score`=?, `ipquery_risk_level`=?,
                            `ipquery_vpn`=?, `ipquery_proxy`=?, `ipquery_tor`=?, `ipquery_datacenter`=?
                     WHERE `conversion_id`=?",
                    [$_ipqR['risk_score'], $_ipqR['risk_level'],
                     $_ipqR['is_vpn'], $_ipqR['is_proxy'], $_ipqR['is_tor'], $_ipqR['is_datacenter'],
                     $conv['conversion_id']]
                );
            } catch (\Throwable $_) {
                try { Database::query("UPDATE `conversions` SET `ipquery_risk_score`=0,`ipquery_risk_level`='low' WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `ipquery_risk_score`=0,`ipquery_risk_level`='low' WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
        }

        // 2) IPQS — requires key
        if (!empty($cfg['ipqs_api_key']) && (bool)($cfg['ipqs_enabled'] ?? false) && $ipValid) {
            try {
                $_ipqsR = FraudIQ::checkIPQS($ip, $conv['click_id']);
                $_score = (int)($_ipqsR['score'] ?? 0);
                Database::query(
                    "UPDATE `conversions` SET `fraud_score`=?, `fraud_checked_at`=NOW() WHERE `conversion_id`=?",
                    [$_score, $conv['conversion_id']]
                );
            } catch (\Throwable $_) {
                try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
        }

        // 3-6) Optional providers (Scamalytics / ProxyCheck / FraudDefense / FraudLabsPro).
        // Kept identical to recheck_pending but condensed: fail-silent + zero default.
        $extras = [
            ['scamalytics_api_key', 'scamalytics_enabled', 'checkFraud',         ['scamalytics_score','scamalytics_status'], 'scamalytics_score'],
            ['proxycheck_api_key',  'proxycheck_enabled',  'checkProxyCheck',    ['proxycheck_score','proxycheck_status'],   'proxycheck_score'],
            ['frauddefense_api_key','frauddefense_enabled','checkFraudDefense',  ['frauddefense_score','frauddefense_status'],'frauddefense_score'],
            ['fraudlabspro_api_key','fraudlabspro_enabled','checkFraudLabsPro',  ['fraudlabspro_score','fraudlabspro_status'],'fraudlabspro_score'],
        ];
        foreach ($extras as [$kKey, $kEnabled, $fn, $cols, $zeroCol]) {
            if (!empty($cfg[$kKey]) && (!array_key_exists($kEnabled, $cfg) || (bool)$cfg[$kEnabled]) && $ipPublic) {
                try {
                    $r = FraudIQ::$fn($ip);
                    $score  = (int)($r['score']  ?? $r['risk_score'] ?? 0);
                    $status = (string)($r['status'] ?? '');
                    Database::query(
                        "UPDATE `conversions` SET `{$cols[0]}`=?, `{$cols[1]}`=? WHERE `conversion_id`=?",
                        [$score, $status, $conv['conversion_id']]
                    );
                } catch (\Throwable $_) {
                    try { Database::query("UPDATE `conversions` SET `{$zeroCol}`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
                }
            } else {
                try { Database::query("UPDATE `conversions` SET `{$zeroCol}`=0 WHERE `conversion_id`=? AND `{$zeroCol}` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        }

        // Trigger affiliate notification on new high-risk score (idempotent)
        try { FraudAutoNotify::afterCheck($conv['conversion_id']); } catch (\Throwable $_) {}

        // Read back the row so the client can update the DOM in place
        $fresh = Database::fetchOne(
            "SELECT conversion_id, fraud_score, fraud_checked_at,
                    ipquery_risk_score, ipquery_risk_level,
                    ipquery_vpn, ipquery_proxy, ipquery_tor, ipquery_datacenter,
                    scamalytics_score, scamalytics_status,
                    proxycheck_score, proxycheck_is_proxy, proxycheck_is_vpn,
                    frauddefense_score, frauddefense_status,
                    fraudlabspro_score, fraudlabspro_flp_status
             FROM conversions WHERE conversion_id = ? LIMIT 1",
            [$conv['conversion_id']]
        );
        if ($fresh) $updates[$conv['conversion_id']] = $fresh;
    }

    $remaining = (int)(Database::fetchOne(
        "SELECT COUNT(*) AS c FROM conversions
         WHERE (fraud_checked_at IS NULL OR ipquery_risk_score IS NULL OR scamalytics_score IS NULL
                OR proxycheck_score IS NULL OR frauddefense_score IS NULL OR fraudlabspro_score IS NULL)
           AND COALESCE(is_hidden,0) = 0
           AND converted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)"
    )['c'] ?? 0);

    echo json_encode([
        'status'    => 'ok',
        'processed' => count($pending),
        'remaining' => $remaining,
        'updates'   => $updates,
        'ts'        => time(),
    ]);
    exit;
}

// AJAX: Re-check all pending conversions (no CSRF needed — only updates fraud score columns)
if (Helpers::isPost() && Helpers::post('action') === 'recheck_pending') {
    header('Content-Type: application/json');
    require_once BASE_PATH . '/core/FraudIQ.php';
    $cfg = Config::get('fraud') ?? [];

    // Ensure columns exist
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_score`        TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_checked_at`   DATETIME         DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_risk_score`  TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_risk_level`  VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_vpn`         TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_proxy`       TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_tor`         TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_datacenter`  TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `scamalytics_score`   TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `scamalytics_status`  VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `proxycheck_score`    TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `proxycheck_is_proxy` TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `proxycheck_is_vpn`   TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `frauddefense_score`  TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `frauddefense_status` VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraudlabspro_score`      TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraudlabspro_flp_status` VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}

    // Re-check ALL conversions missing ANY provider score (not just fraud_checked_at IS NULL)
    // This ensures existing conversions with fraud_checked_at set but missing provider scores get rescored.
    $pending = Database::fetchAll(
        "SELECT conversion_id, click_id, ip_address, affiliate_id, payout
         FROM conversions
         WHERE (fraud_checked_at IS NULL
                OR ipquery_risk_score IS NULL
                OR scamalytics_score IS NULL
                OR proxycheck_score IS NULL
                OR frauddefense_score IS NULL
                OR fraudlabspro_score IS NULL)
           AND COALESCE(is_hidden, 0) = 0
           AND converted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
         ORDER BY converted_at DESC LIMIT 100"
    );

    $checked = 0;
    foreach ($pending as $conv) {
        $ip = trim($conv['ip_address'] ?? '');

        // Classify IP
        $ipValid  = ($ip !== '' && $ip !== '0.0.0.0' && filter_var($ip, FILTER_VALIDATE_IP) !== false);
        $ipPublic = $ipValid && filter_var($ip, FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

        // ── 1. IPQuery.io — FREE, no key needed, runs for all valid IPs ──────
        if ($ipValid) {
            try {
                $_ipqR = FraudIQ::checkIPQuery($ip);
                Database::query(
                    "UPDATE `conversions`
                        SET `ipquery_risk_score`  = ?,
                            `ipquery_risk_level`  = ?,
                            `ipquery_vpn`         = ?,
                            `ipquery_proxy`       = ?,
                            `ipquery_tor`         = ?,
                            `ipquery_datacenter`  = ?
                      WHERE `conversion_id` = ?",
                    [
                        $_ipqR['risk_score'],
                        $_ipqR['risk_level'],
                        $_ipqR['is_vpn'],
                        $_ipqR['is_proxy'],
                        $_ipqR['is_tor'],
                        $_ipqR['is_datacenter'],
                        $conv['conversion_id'],
                    ]
                );
            } catch (\Throwable $_e) {
                // On failure write score=0 so column is not left NULL
                try { Database::query("UPDATE `conversions` SET `ipquery_risk_score`=0,`ipquery_risk_level`='low' WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `ipquery_risk_score`=0,`ipquery_risk_level`='low' WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }

        // ── 2. IPQS — requires API key ────────────────────────────────────────
        $_ipqsEnabled = !empty($cfg['ipqs_api_key']) && (bool)($cfg['ipqs_enabled'] ?? false);
        if ($_ipqsEnabled && $ipValid) {
            try {
                $_ipqsR = FraudIQ::checkIPQS($ip, $conv['click_id']);
                $_score = (int)($_ipqsR['score'] ?? 0);
                Database::query(
                    "UPDATE `conversions` SET `fraud_score`=?, `fraud_checked_at`=NOW() WHERE `conversion_id`=?",
                    [$_score, $conv['conversion_id']]
                );
            } catch (\Throwable $_e) {}
        } else {
            // Stamp fraud_checked_at so row doesn't stay "Pending" forever
            try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }

        // ── 3. Scamalytics — requires API key + user + server ────────────────
        if (!empty($cfg['scamalytics_api_key']) && !empty($cfg['scamalytics_user']) && $ipPublic) {
            if (!array_key_exists('scamalytics_enabled', $cfg) || (bool)$cfg['scamalytics_enabled']) {
                try {
                    $_scR = FraudIQ::checkFraud($ip);
                    Database::query(
                        "UPDATE `conversions` SET `scamalytics_score`=?,`scamalytics_status`=?,`scamalytics_mode`=? WHERE `conversion_id`=?",
                        [$_scR['score'], $_scR['status'], $_scR['mode'] ?? 'score_only', $conv['conversion_id']]
                    );
                } catch (\Throwable $_e) {
                    try { Database::query("UPDATE `conversions` SET `scamalytics_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
                }
            } else {
                try { Database::query("UPDATE `conversions` SET `scamalytics_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `scamalytics_score`=0 WHERE `conversion_id`=? AND `scamalytics_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }

        // ── 4. ProxyCheck.io — requires API key ───────────────────────────────
        if (!empty($cfg['proxycheck_api_key']) && $ipPublic) {
            if (!array_key_exists('proxycheck_enabled', $cfg) || (bool)$cfg['proxycheck_enabled']) {
                try {
                    $_pcR = FraudIQ::checkProxyCheck($ip);
                    Database::query(
                        "UPDATE `conversions` SET `proxycheck_score`=?,`proxycheck_is_proxy`=?,`proxycheck_is_vpn`=?,`proxycheck_status`=? WHERE `conversion_id`=?",
                        [$_pcR['score'], $_pcR['is_proxy'], $_pcR['is_vpn'], $_pcR['status'], $conv['conversion_id']]
                    );
                } catch (\Throwable $_e) {
                    try { Database::query("UPDATE `conversions` SET `proxycheck_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
                }
            } else {
                try { Database::query("UPDATE `conversions` SET `proxycheck_score`=0 WHERE `conversion_id`=? AND `proxycheck_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `proxycheck_score`=0 WHERE `conversion_id`=? AND `proxycheck_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }

        // ── 5. FraudDefense.io — requires API key ─────────────────────────────
        if (!empty($cfg['frauddefense_api_key']) && $ipPublic) {
            if (!array_key_exists('frauddefense_enabled', $cfg) || (bool)$cfg['frauddefense_enabled']) {
                try {
                    $_fdR = FraudIQ::checkFraudDefense($ip);
                    Database::query(
                        "UPDATE `conversions` SET `frauddefense_score`=?,`frauddefense_status`=? WHERE `conversion_id`=?",
                        [$_fdR['score'], $_fdR['status'], $conv['conversion_id']]
                    );
                } catch (\Throwable $_e) {
                    try { Database::query("UPDATE `conversions` SET `frauddefense_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
                }
            } else {
                try { Database::query("UPDATE `conversions` SET `frauddefense_score`=0 WHERE `conversion_id`=? AND `frauddefense_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `frauddefense_score`=0 WHERE `conversion_id`=? AND `frauddefense_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }

        // ── 6. FraudLabs Pro — requires API key ───────────────────────────────
        if (!empty($cfg['fraudlabspro_api_key']) && $ipPublic) {
            if (!array_key_exists('fraudlabspro_enabled', $cfg) || (bool)$cfg['fraudlabspro_enabled']) {
                try {
                    $_flR = FraudIQ::checkFraudLabsPro($ip);
                    Database::query(
                        "UPDATE `conversions` SET `fraudlabspro_score`=?,`fraudlabspro_status`=?,`fraudlabspro_flp_status`=? WHERE `conversion_id`=?",
                        [$_flR['score'], $_flR['status'], $_flR['flp_status'], $conv['conversion_id']]
                    );
                } catch (\Throwable $_e) {
                    try { Database::query("UPDATE `conversions` SET `fraudlabspro_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
                }
            } else {
                try { Database::query("UPDATE `conversions` SET `fraudlabspro_score`=0 WHERE `conversion_id`=? AND `fraudlabspro_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `fraudlabspro_score`=0 WHERE `conversion_id`=? AND `fraudlabspro_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }

        // Fire instant notification if this conversion just crossed the High Risk threshold
        // during the re-check. afterCheck() de-dupes, so re-running this loop is safe.
        try { FraudAutoNotify::afterCheck($conv['conversion_id']); } catch (\Throwable $_naEx) {}

        $checked++;
        usleep(200000); // 200ms rate-limit between conversions
    }

    $remaining = (int)(Database::fetchOne(
        "SELECT COUNT(*) as c FROM conversions
         WHERE fraud_checked_at IS NULL
            OR ipquery_risk_score IS NULL
            OR scamalytics_score IS NULL
            OR proxycheck_score IS NULL
            OR frauddefense_score IS NULL
            OR fraudlabspro_score IS NULL",
        []
    )['c'] ?? 0);
    echo json_encode(['status' => 'ok', 'checked' => $checked, 'remaining' => $remaining]);
    exit;
}

// Approve / Reject conversion
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'update_conv_status') {
    $convId    = Helpers::postRaw('conversion_id');
    $newStatus = Helpers::post('conv_status');
    if (in_array($newStatus, ['approved', 'rejected'])) {
        $conv = Database::fetchOne("SELECT * FROM conversions WHERE conversion_id=?", [$convId]);
        if ($conv) {
            $oldStatus    = $conv['status'];
            $rejectReason = trim((string)Helpers::postRaw('rejection_reason'));
            $payload      = RejectionHelper::buildUpdatePayload($newStatus, $rejectReason, (int)(Auth::id() ?? 0));
            Database::update('conversions', $payload, 'conversion_id=?', [$convId]);

            // Notify affiliate when transitioning into a rejected-style state.
            if ($newStatus === 'rejected' && $oldStatus !== 'rejected') {
                try { RejectionNotifier::afterReject((string)$convId); } catch (\Throwable $_rn) {}
            }
            if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                Database::query("UPDATE affiliates SET balance = balance + ? WHERE id = ?", [$conv['payout'], $conv['affiliate_id']]);
                // Auto-generate manager commission for this newly approved conversion
                try {
                    require_once BASE_PATH . '/core/ManagerCommissionService.php';
                    ManagerCommissionService::recordForConversion((int)$conv['id']);
                } catch (\Throwable $_mce) {}
            } elseif ($newStatus === 'rejected' && $oldStatus === 'approved') {
                Database::query("UPDATE affiliates SET balance = balance - ? WHERE id = ?", [$conv['payout'], $conv['affiliate_id']]);
                // Reverse manager commission for rejected conversion
                try {
                    require_once BASE_PATH . '/core/ManagerCommissionService.php';
                    ManagerCommissionService::reverseForConversion((int)$conv['id']);
                } catch (\Throwable $_mce) {}
            }
            Helpers::flash('success', 'Conversion ' . $newStatus . '.');
        }
    }
    Helpers::redirect('/admin/fraud-score-report');
}

// Bulk Reject conversions
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token')) && Helpers::post('action') === 'bulk_reject_fraud') {
    $convIds      = Helpers::postRaw('conversion_ids');
    $rejectReason = trim((string)Helpers::postRaw('rejection_reason')) ?: 'Bulk Fraud Rejection';
    $count        = 0;

    if (is_array($convIds)) {
        foreach ($convIds as $convId) {
            $convId = trim((string)$convId);
            if ($convId === '') continue;

            $conv = Database::fetchOne("SELECT * FROM conversions WHERE conversion_id=?", [$convId]);
            if ($conv && $conv['status'] !== 'rejected') {
                $oldStatus = $conv['status'];
                $payload   = RejectionHelper::buildUpdatePayload('rejected', $rejectReason, (int)(Auth::id() ?? 0));
                Database::update('conversions', $payload, 'conversion_id=?', [$convId]);

                if ($oldStatus !== 'rejected') {
                    try { RejectionNotifier::afterReject((string)$convId); } catch (\Throwable $_rn) {}
                }
                if ($oldStatus === 'approved') {
                    Database::query("UPDATE affiliates SET balance = balance - ? WHERE id = ?", [$conv['payout'], $conv['affiliate_id']]);
                    try {
                        require_once BASE_PATH . '/core/ManagerCommissionService.php';
                        ManagerCommissionService::reverseForConversion((int)$conv['id']);
                    } catch (\Throwable $_mce) {}
                }
                $count++;
            }
        }
    }

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'rejected_count' => $count]);
        exit;
    }

    Helpers::flash('success', "Successfully rejected $count conversion(s).");
    Helpers::redirect('/admin/fraud-score-report');
}

// Filters
$filterStatus    = Helpers::get('status') ?: 'all';
$filterAffiliate = (int)Helpers::get('affiliate_id');
$_affCode = trim(Helpers::get('affiliate_code') ?? '');
if ($_affCode !== '' && $filterAffiliate === 0) {
    $_affRow = Database::fetchOne("SELECT id FROM affiliates WHERE affiliate_code = ?", [$_affCode]);
    if ($_affRow) $filterAffiliate = (int)$_affRow['id'];
}
$filterOffer     = (int)Helpers::get('offer_id');
$filterScoreMin  = Helpers::get('score_min') !== null && Helpers::get('score_min') !== '' ? (int)Helpers::get('score_min') : 0;
$filterScoreMax  = Helpers::get('score_max') !== null && Helpers::get('score_max') !== '' ? (int)Helpers::get('score_max') : 100;
$sortBy          = in_array(Helpers::get('sort'), ['fraud_score','converted_at']) ? Helpers::get('sort') : 'converted_at';
$sortDir         = Helpers::get('dir') === 'asc' ? 'ASC' : 'DESC';

// Date-range filter (defaults to current month).
$from = Helpers::get('from') ?: date('Y-m-01');
$to   = Helpers::get('to')   ?: date('Y-m-d');
$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

$where  = ['COALESCE(cv.is_hidden, 0) = 0', 'cv.converted_at BETWEEN ? AND ?'];
$params = [$dateFrom, $dateTo];

if (in_array($filterStatus, ['pending','approved','rejected'])) {
    $where[]  = 'cv.status = ?';
    $params[] = $filterStatus;
}
if ($filterAffiliate) {
    $where[]  = 'cv.affiliate_id = ?';
    $params[] = $filterAffiliate;
}
if ($filterOffer) {
    $where[]  = 'cv.offer_id = ?';
    $params[] = $filterOffer;
}
if ($filterScoreMin > 0) {
    $where[]  = 'COALESCE(cv.fraud_score, 0) >= ?';
    $params[] = $filterScoreMin;
}
if ($filterScoreMax < 100) {
    $where[]  = 'COALESCE(cv.fraud_score, 0) <= ?';
    $params[] = $filterScoreMax;
}

$whereSQL = implode(' AND ', $where);

// CSV / XLS export
$_fsrExportFmt = Helpers::get('export');
if ($_fsrExportFmt === 'csv' || $_fsrExportFmt === 'xls') {
    try {
        $csvOrder = $sortBy === 'fraud_score'
            ? "ISNULL(cv.fraud_score) ASC, cv.fraud_score $sortDir"
            : "cv.converted_at $sortDir";
        $rows = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.affiliate_id, af.affiliate_code,
                    cv.offer_id, o.name as offer_name,
                    cv.ip_address, cv.fraud_score, cv.fraud_checked_at,
                    cv.status, cv.payout, cv.converted_at,
                    COALESCE(cv.rejection_reason,'') as rejection_reason, cv.rejected_at,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name
             FROM conversions cv
             JOIN offers o ON o.id = cv.offer_id
             JOIN affiliates af ON af.id = cv.affiliate_id
             JOIN users u ON u.id = af.user_id
             WHERE $whereSQL
             ORDER BY $csvOrder
             LIMIT 10000",
            $params
        );
    } catch (\Throwable $e) { $rows = []; }

    $headerRow = ['Conversion ID','Click ID','Affiliate ID','Affiliate','Aff Code','Offer ID','Offer','IP Address','Fraud Score','Checked At','Status','Rejection Reason','Rejected At','Payout','Converted At'];

    if ($_fsrExportFmt === 'xls') {
        ExportHelper::beginXls('fraud_score_report');
        ExportHelper::xlsHeaderRow($headerRow);
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="fraud_score_report_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        $fh = fopen('php://output', 'w');
        fputcsv($fh, $headerRow);
    }
    foreach ($rows as $r) {
        $rowCells = [
            $r['conversion_id'], $r['click_id'], $r['affiliate_id'], $r['aff_name'], $r['affiliate_code'],
            $r['offer_id'] ?: '', $r['offer_name'] ?: '— Custom URL —', $r['ip_address'],
            $r['fraud_score'] !== null ? (int)$r['fraud_score'] : 'pending',
            $r['fraud_checked_at'] ?? '',
            $r['status'],
            $r['rejection_reason'] ?? '',
            $r['rejected_at'] ?? '',
            $r['payout'], $r['converted_at'],
        ];
        if ($_fsrExportFmt === 'xls') ExportHelper::xlsRow($rowCells);
        else                          fputcsv($fh, $rowCells);
    }
    if ($_fsrExportFmt === 'xls') ExportHelper::endXls();
    else                          fclose($fh);
    exit;
}

// When sorting by fraud_score, push unchecked (NULL) rows to the end.
// MySQL: ISNULL(col) ASC puts NULLs last regardless of sort direction.
$orderClause = $sortBy === 'fraud_score'
    ? "ISNULL(cv.fraud_score) ASC, cv.fraud_score $sortDir"
    : "cv.converted_at $sortDir";

try {
    $conversions = Database::fetchAll(
        "SELECT cv.conversion_id, cv.click_id, cv.affiliate_id, af.affiliate_code,
                cv.offer_id, o.name as offer_name,
                cv.ip_address, cv.fraud_score, cv.fraud_checked_at,
                cv.status, cv.payout, cv.converted_at,
                CONCAT(u.first_name,' ',u.last_name) as aff_name,
                cv.ipquery_risk_score, cv.ipquery_risk_level,
                cv.ipquery_vpn, cv.ipquery_proxy, cv.ipquery_tor, cv.ipquery_datacenter,
                cv.scamalytics_score, cv.scamalytics_status,
                cv.proxycheck_score, cv.proxycheck_is_proxy, cv.proxycheck_is_vpn,
                cv.frauddefense_score, cv.frauddefense_status,
                cv.fraudlabspro_score, cv.fraudlabspro_flp_status
         FROM conversions cv
         LEFT JOIN offers o ON o.id = cv.offer_id
         JOIN affiliates af ON af.id = cv.affiliate_id
         JOIN users u ON u.id = af.user_id
         WHERE $whereSQL
         ORDER BY $orderClause
         LIMIT 2000",
        $params
    );
} catch (\Throwable $e) { $conversions = []; }

// Count pending (not yet checked) — use fraud_checked_at as the indicator.
// fraud_score defaults to 0, so IS NULL is unreliable; fraud_checked_at is
// only set when the API call actually completes.
$hasPendingScores = false;
foreach ($conversions as $cv) {
    if ($cv['fraud_checked_at'] === null || $cv['fraud_checked_at'] === '') {
        $hasPendingScores = true;
        break;
    }
}

// Fraud mode for UI banner
$fraudCfg  = Config::get('fraud') ?? [];
$fraudMode = $fraudCfg['mode'] ?? 'block';

// Summary stats
try {
    $stats = Database::fetchOne(
        "SELECT COUNT(*) as total,
                SUM(CASE WHEN cv.fraud_checked_at IS NOT NULL THEN 1 ELSE 0 END) as checked,
                SUM(CASE WHEN cv.fraud_checked_at IS NULL THEN 1 ELSE 0 END) as pending_check,
                AVG(CASE WHEN cv.fraud_checked_at IS NOT NULL AND cv.fraud_score > 0 THEN cv.fraud_score ELSE NULL END) as avg_score,
                MAX(CASE WHEN cv.fraud_checked_at IS NOT NULL THEN cv.fraud_score ELSE NULL END) as max_score,
                SUM(CASE WHEN COALESCE(cv.fraud_score,0) >= 75 AND cv.fraud_checked_at IS NOT NULL THEN 1 ELSE 0 END) as high_risk
         FROM conversions cv WHERE COALESCE(cv.is_hidden, 0) = 0"
    );
} catch (\Throwable $e) { $stats = []; }

// Affiliate list for filter dropdown
try {
    $affiliateList = Database::fetchAll(
        "SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name
         FROM affiliates af JOIN users u ON u.id = af.user_id
         WHERE u.status = 'active' ORDER BY u.first_name"
    );
} catch (\Throwable $e) { $affiliateList = []; }

// Offer list for filter dropdown
try {
    $offerList = Database::fetchAll("SELECT id, name FROM offers WHERE status='active' ORDER BY name");
} catch (\Throwable $e) { $offerList = []; }

// ── Cron URL for cPanel / external schedulers ────────────────────────────
// Auto-provision the token on the first report visit so cPanel admins can
// copy the URL straight from this page without digging through config.
$cronToken = trim((string)Config::get('config', 'app.fraud_scan_cron_token'));
if ($cronToken === '') {
    try {
        $cronToken = bin2hex(random_bytes(16));
        Config::set('config', 'app.fraud_scan_cron_token', $cronToken);
    } catch (\Throwable $_) {}
}
$cronAppUrl = rtrim((string)(Config::get('config', 'app.url') ?? ''), '/');
if ($cronAppUrl === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $cronAppUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
$cronFullUrl = $cronAppUrl . '/cron/fraud-scan?token=' . $cronToken;

require BASE_PATH . '/views/admin/fraud_score_report/index.php';
