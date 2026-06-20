<?php
Auth::check('admin');
$pageTitle = 'Click Report';

try { Database::query("ALTER TABLE conversions ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(\Throwable $_e) {}
// Allow NULL advertiser_id so in-house offers (no external advertiser) can record conversions
try { Database::query("ALTER TABLE `conversions` MODIFY COLUMN `advertiser_id` INT UNSIGNED NULL DEFAULT NULL"); } catch (\Throwable $_e) {}
// Ensure hide_reason column exists with a default so INSERT never fails
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `hide_reason` VARCHAR(500) NOT NULL DEFAULT ''"); } catch (\Throwable $_e) {}
// Ensure late-added clicks columns exist so report queries never fail on older databases
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `sub6`          VARCHAR(500)  DEFAULT NULL          AFTER `sub5`");        } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `smartlink_id`  INT UNSIGNED  DEFAULT NULL          AFTER `affiliate_id`"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `fraud_reasons` TEXT          DEFAULT NULL");                               } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `source`        VARCHAR(255)  DEFAULT ''");                                 } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `is_fraud`      TINYINT(1)   NOT NULL DEFAULT 0");                         } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `fraud_score`   TINYINT UNSIGNED DEFAULT 0");                              } catch (\Throwable $_e) {}
// Allow offer_id to be NULL so custom-URL smartlink clicks appear in reports
try { Database::query("ALTER TABLE `clicks` MODIFY COLUMN `offer_id` INT UNSIGNED NULL DEFAULT NULL"); } catch (\Throwable $_e) {}

// ── POST: convert click OR update conversion status ───────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $convAction = Helpers::post('conv_action');
    $convId     = Helpers::postRaw('conversion_id');
    $clickId    = Helpers::postRaw('click_id');

    // ── Convert a raw click into an approved conversion ───────────────
    if ($convAction === 'convert_click' && $clickId) {
        $click = Database::fetchOne(
            "SELECT c.*, o.payout_amount, o.revenue_amount, o.advertiser_id
             FROM clicks c JOIN offers o ON o.id=c.offer_id
             WHERE c.click_id=?",
            [$clickId]
        );
        $existing = $click ? Database::fetchOne(
            "SELECT id FROM conversions WHERE click_id=?", [$clickId]
        ) : null;

        if ($click && !$existing) {
            $payout    = $click['payout'] > 0  ? (float)$click['payout']   : (float)$click['payout_amount'];
            $revenue   = $click['revenue'] > 0 ? (float)$click['revenue']  : (float)$click['revenue_amount'];
            $newConvId = Helpers::uuid();
            require_once BASE_PATH . '/core/PostbackFirer.php';
            PostbackFirer::ensurePostbackSentColumn();
            try {
                // Resolve geo data from click, fallback to IP lookup
                $_mCountry = $click['country']  ?? '';
                $_mCity    = $click['city']     ?? '';
                $_mRegion  = $click['region']   ?? '';
                if ($_mCountry === '' || $_mCity === '') {
                    try {
                        $_mGeo = Helpers::getGeoInfo($click['ip_address'] ?? '');
                        if ($_mCountry === '') $_mCountry = $_mGeo['country'] ?? '';
                        if ($_mCity    === '') $_mCity    = $_mGeo['city']    ?? '';
                        if ($_mRegion  === '') $_mRegion  = $_mGeo['region']  ?? '';
                    } catch (\Throwable $_) {}
                }

                Database::insert('conversions', [
                    'conversion_id' => $newConvId,
                    'click_id'      => $clickId,
                    'offer_id'      => $click['offer_id'],
                    'affiliate_id'  => $click['affiliate_id'],
                    'advertiser_id' => $click['advertiser_id'] ?? null, // NULL for in-house offers
                    'payout'        => $payout,
                    'revenue'       => $revenue,
                    'currency'      => 'USD',
                    'status'        => 'approved',
                    'approved_at'   => date('Y-m-d H:i:s'),
                    'is_hidden'     => 0,
                    'hide_reason'   => '',
                    'ip_address'    => $click['ip_address'] ?? '',
                    'country'              => $_mCountry ?: null,
                    'ipquery_country_code' => $_mCountry ?: null,
                    'ipquery_city'         => $_mCity    ?: null,
                    'ipquery_state'        => $_mRegion  ?: null,
                    'postback_sent' => 0,
                ]);
                Database::query("UPDATE affiliates SET balance=balance+? WHERE id=?", [$payout, $click['affiliate_id']]);
                Database::upsertStats(date('Y-m-d'), (int)$click['affiliate_id'], (int)$click['offer_id'], [
                    'conversions' => 1,
                    'approved'    => 1,
                    'payout'      => $payout,
                    'revenue'     => $revenue,
                ]);
            } catch (\Throwable $_convEx) {
                Helpers::flash('error', 'Conversion insert failed: ' . $_convEx->getMessage());
                $qs = http_build_query(array_filter(['from'=>Helpers::get('from'),'to'=>Helpers::get('to'),'offer_id'=>Helpers::get('offer_id'),'affiliate_id'=>Helpers::get('affiliate_id')]));
                Helpers::redirect('/admin/reports/clicks' . ($qs ? '?'.$qs : ''));
            }
            try { Referral::creditCommission($newConvId, (int)$click['affiliate_id'], $payout); } catch(\Throwable $e) {}

            // ── Auto-fire postback to affiliate tracker ────────────────────
            $convForPostback = [
                'conversion_id' => $newConvId,
                'click_id'      => $clickId,
                'offer_id'      => $click['offer_id'],
                'affiliate_id'  => $click['affiliate_id'],
                'payout'        => $payout,
                'revenue'       => $revenue,
                'status'        => 'approved',
                'source'        => $click['source'] ?? '',
                'sub1'          => $click['sub1'] ?? '',
                'sub2'          => $click['sub2'] ?? '',
                'sub3'          => $click['sub3'] ?? '',
                'sub4'          => $click['sub4'] ?? '',
                'sub5'          => $click['sub5'] ?? '',
            ];
            try {
                PostbackFirer::fireAll($convForPostback, 'approved', true);
            } catch (\Throwable $pbEx) {
                PostbackFirer::log('[ClickReportController] fireAll error for ' . $newConvId . ': ' . $pbEx->getMessage());
            }

            // ── Fraud score for this conversion ───────────────────────────
            require_once BASE_PATH . '/core/FraudIQ.php';
            try {
                FraudIQ::checkConversion($click['ip_address'] ?? '', $clickId, $newConvId, $payout, (int)$click['affiliate_id']);
                // Instant affiliate notification when score >= High Risk threshold.
                try { FraudAutoNotify::afterCheck($newConvId); } catch (\Throwable $_naEx) {}
            } catch (\Throwable $e) {}

            Helpers::flash('success', "Click converted to approved conversion ({$newConvId}).");
        } else {
            Helpers::flash('error', $existing ? 'A conversion already exists for this click.' : 'Click not found.');
        }

    // ── Update existing conversion status ─────────────────────────────
    } elseif ($convId && in_array($convAction, ['approved','rejected','pending'])) {
        $conv = Database::fetchOne("SELECT * FROM conversions WHERE conversion_id=?", [$convId]);
        if ($conv) {
            $rejectReason = trim((string)Helpers::postRaw('rejection_reason'));
            $payload = RejectionHelper::buildUpdatePayload($convAction, $rejectReason, (int)(Auth::id() ?? 0));
            Database::update('conversions', $payload, 'conversion_id=?', [$convId]);

            // Notify affiliate when this transitions into a rejected-style state.
            if ($convAction === 'rejected' && $conv['status'] !== 'rejected') {
                try { RejectionNotifier::afterReject((string)$convId); } catch (\Throwable $_rn) {}
            }
            if ($convAction === 'approved' && $conv['status'] !== 'approved') {
                Database::query("UPDATE affiliates SET balance=balance+? WHERE id=?", [$conv['payout'], $conv['affiliate_id']]);
                Database::upsertStats(date('Y-m-d'), (int)$conv['affiliate_id'], (int)$conv['offer_id'], [
                    'conversions' => 1,
                    'approved'    => 1,
                    'payout'      => $conv['payout'],
                    'revenue'     => $conv['revenue'] ?? 0,
                ]);
            } elseif ($convAction !== 'approved' && $conv['status'] === 'approved') {
                Database::query("UPDATE affiliates SET balance=balance-? WHERE id=?", [$conv['payout'], $conv['affiliate_id']]);
            }
            Helpers::flash('success', 'Conversion ' . $convId . ' → ' . $convAction . '.');
        }
    }

    $qs = http_build_query(array_filter([
        'from'         => Helpers::get('from'),
        'to'           => Helpers::get('to'),
        'offer_id'     => Helpers::get('offer_id'),
        'affiliate_id' => Helpers::get('affiliate_id'),
        'fraud'        => Helpers::get('fraud'),
        'limit'        => Helpers::get('limit'),
        'click_filter' => Helpers::get('click_filter'),
    ]));
    Helpers::redirect('/admin/reports/clicks' . ($qs ? '?'.$qs : ''));
}

$from      = Helpers::get('from') ?: date('Y-m-d', strtotime('-29 days'));
$to        = Helpers::get('to')   ?: date('Y-m-d');
$offerId   = (int)(Helpers::get('offer_id') ?: 0);
$affId     = (int)(Helpers::get('affiliate_id') ?: 0);
$affCode   = trim(Helpers::get('affiliate_code') ?? '');
if ($affCode !== '' && $affId === 0) {
    $_affRow = Database::fetchOne("SELECT id FROM affiliates WHERE affiliate_code = ?", [$affCode]);
    if ($_affRow) $affId = (int)$_affRow['id'];
}
$fraud     = Helpers::get('fraud');
$limit     = min((int)(Helpers::get('limit') ?: 500), 5000);
$filterClickId = trim(Helpers::get('filter_click_id') ?? '');
$filterSub1    = trim(Helpers::get('filter_sub1') ?? '');
$filterSub2    = trim(Helpers::get('filter_sub2') ?? '');
$filterSub3    = trim(Helpers::get('filter_sub3') ?? '');
$inhouseOnly   = Helpers::get('inhouse') === '1';
$clickFilter   = Helpers::get('click_filter') ?: 'all';
if (!in_array($clickFilter, ['all','converted','approved'])) $clickFilter = 'all';

$params = [date('Y-m-d 00:00:00', strtotime($from)), date('Y-m-d 23:59:59', strtotime($to))];
$where  = ['c.clicked_at BETWEEN ? AND ?'];

if ($offerId > 0)      { $where[] = 'c.offer_id = ?';                  $params[] = $offerId; }
if ($affId   > 0)      { $where[] = 'c.affiliate_id = ?';              $params[] = $affId; }
if ($fraud === '1')    { $where[] = 'c.is_fraud = 1'; }
if ($fraud === '0')    { $where[] = 'c.is_fraud = 0'; }
if ($filterClickId)    { $where[] = 'c.click_id LIKE ?';               $params[] = $filterClickId . '%'; }
if ($filterSub1)       { $where[] = 'c.sub1 = ?';                      $params[] = $filterSub1; }
if ($filterSub2)       { $where[] = 'c.sub2 = ?';                      $params[] = $filterSub2; }
if ($filterSub3)       { $where[] = 'c.sub3 = ?';                      $params[] = $filterSub3; }
if ($inhouseOnly)      { $where[] = 'o.is_inhouse = 1'; }
if ($clickFilter === 'converted') {
    $where[] = 'EXISTS (SELECT 1 FROM conversions _cv WHERE _cv.click_id = c.click_id AND _cv.is_hidden = 0)';
} elseif ($clickFilter === 'approved') {
    $where[] = "EXISTS (SELECT 1 FROM conversions _cv WHERE _cv.click_id = c.click_id AND _cv.status = 'approved' AND _cv.is_hidden = 0)";
}

$whereStr = implode(' AND ', $where);

// CSV export
if (Helpers::get('export') === 'csv') {
    $rows = Database::fetchAll(
        "SELECT c.click_id, c.sub1, c.sub2, c.sub3, c.sub4, c.sub5, c.sub6, c.source, c.referer,
                c.is_fraud, c.fraud_score, c.os, c.browser, c.user_agent,
                c.device_type, c.ip_address, c.country, c.city, c.region,
                c.revenue, c.payout, (c.revenue - c.payout) as profit, c.clicked_at,
                c.landing_page_idx,
                o.name as offer_name, COALESCE(o.is_inhouse,0) as is_inhouse,
                o.landing_pages as offer_landing_pages,
                o.landing_page_names as offer_landing_page_names,
                CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
                cv.payout as conv_payout, cv.revenue as conv_revenue,
                cv.status as conv_status, cv.converted_at
         FROM clicks c
         LEFT JOIN offers o ON o.id = c.offer_id
         JOIN affiliates af ON af.id = c.affiliate_id
         JOIN users u ON u.id = af.user_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
         WHERE $whereStr ORDER BY c.clicked_at DESC LIMIT $limit",
        $params
    );
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="click-report-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['OFFER','AFFILIATE','AFF CODE','CLICK ID','SUB1','SUB2','SUB3','SUB4','SUB5','SOURCE','REFERER','FRAUD',
                 'OS','BROWSER','USER AGENT','DEVICE','IP','COUNTRY','CITY','REGION',
                 'LANDING PAGE','LANDING PAGE NAME',
                 'REVENUE','PAYOUT','PROFIT','CONV STATUS','CLICK TIME']);
    foreach ($rows as $r) {
        // Resolve LP URL + Name from offer's landing_pages / landing_page_names by index
        $_lpUrl = ''; $_lpName = '';
        if (isset($r['landing_page_idx']) && $r['landing_page_idx'] !== null) {
            $_lpA = !empty($r['offer_landing_pages'])      ? json_decode($r['offer_landing_pages'], true)      : null;
            $_lpN = !empty($r['offer_landing_page_names']) ? json_decode($r['offer_landing_page_names'], true) : null;
            $_idx = (int)$r['landing_page_idx'];
            if (is_array($_lpA) && isset($_lpA[$_idx])) $_lpUrl  = $_lpA[$_idx];
            if (is_array($_lpN) && isset($_lpN[$_idx])) $_lpName = $_lpN[$_idx];
        }
        fputcsv($f, [
            $r['offer_name'] ?: '— Custom URL —', $r['aff_name'], $r['affiliate_code'],
            $r['click_id'], $r['sub1'], $r['sub2'], $r['sub3'], $r['sub4'], $r['sub5'] ?? '', $r['source'] ?? '', $r['referer'],
            $r['is_fraud'] ? 'Yes' : 'No',
            $r['os'], $r['browser'], $r['user_agent'], $r['device_type'],
            $r['ip_address'], $r['country'], $r['city'], $r['region'],
            $_lpUrl, $_lpName,
            number_format($r['conv_revenue'] ?? $r['revenue'], 4),
            number_format($r['conv_payout']  ?? $r['payout'],  4),
            number_format(($r['conv_revenue'] ?? $r['revenue']) - ($r['conv_payout'] ?? $r['payout']), 4),
            $r['conv_status'] ?? '—',
            $r['clicked_at'],
        ]);
    }
    fclose($f);
    exit;
}

$clicks = Database::fetchAll(
    "SELECT c.click_id, c.sub1, c.sub2, c.sub3, c.sub4, c.sub5, c.sub6, c.source, c.referer,
            c.is_fraud, c.fraud_score, c.fraud_reasons, c.os, c.browser, c.user_agent,
            c.device_type,
            c.ip_address, c.country, c.city, c.region,
            c.revenue, c.payout, (c.revenue - c.payout) as profit, c.clicked_at, c.status,
            c.landing_page_idx,
            o.name as offer_name, o.id as offer_id, COALESCE(o.is_inhouse,0) as is_inhouse,
            o.landing_pages as offer_landing_pages,
            o.landing_page_names as offer_landing_page_names,
            CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code, af.id as aff_id,
            cv.conversion_id, cv.payout as conv_payout, cv.revenue as conv_revenue,
            cv.status as conv_status, cv.converted_at
     FROM clicks c
     JOIN offers o ON o.id = c.offer_id
     JOIN affiliates af ON af.id = c.affiliate_id
     JOIN users u ON u.id = af.user_id
     LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.is_hidden = 0
     WHERE $whereStr
     ORDER BY c.clicked_at DESC
     LIMIT $limit",
    $params
);

// Resolve landing-page URL + Name per click for the view
foreach ($clicks as &$_c) {
    $_c['lp_url']  = '';
    $_c['lp_name'] = '';
    if (isset($_c['landing_page_idx']) && $_c['landing_page_idx'] !== null) {
        $_lpA = !empty($_c['offer_landing_pages'])      ? json_decode($_c['offer_landing_pages'], true)      : null;
        $_lpN = !empty($_c['offer_landing_page_names']) ? json_decode($_c['offer_landing_page_names'], true) : null;
        $_idx = (int)$_c['landing_page_idx'];
        if (is_array($_lpA) && isset($_lpA[$_idx])) $_c['lp_url']  = $_lpA[$_idx];
        if (is_array($_lpN) && isset($_lpN[$_idx])) $_c['lp_name'] = $_lpN[$_idx];
    }
}
unset($_c);

// ── Real-time GeoIP re-resolution for blank records ────────────────────
// If a click has a valid IP but country/city are blank (due to prior
// rate-limit failures), re-run the geo lookup now and patch the record.
// Cap at 30 re-lookups per page load to avoid slowing down the report.
$_geoFixCount = 0;
$_geoFixMax   = 30;
foreach ($clicks as &$_c) {
    if ($_geoFixCount >= $_geoFixMax) break;
    $_cIp = trim($_c['ip_address'] ?? '');
    if ($_cIp === '' || $_cIp === '127.0.0.1' || $_cIp === '::1') continue;
    if (!empty($_c['country']) && $_c['country'] !== '' && !empty($_c['city']) && $_c['city'] !== '') continue;

    // This IP's click has missing geo — attempt to re-resolve
    $geoRetry = Helpers::getGeoInfo($_cIp);
    if (!empty($geoRetry['country'])) {
        $_c['country'] = $geoRetry['country'];
        $_c['city']    = $geoRetry['city'];
        $_c['region']  = $geoRetry['region'];
        // Also update the database so future loads don't need to re-resolve
        try {
            Database::query(
                "UPDATE clicks SET country=?, city=?, region=? WHERE click_id=?",
                [$geoRetry['country'], $geoRetry['city'], $geoRetry['region'], $_c['click_id']]
            );
        } catch (\Throwable $_geoUpEx) {
            error_log("GeoIP backfill update failed for click {$_c['click_id']}: " . $_geoUpEx->getMessage());
        }
        $_geoFixCount++;
    }
}
unset($_c);

$totalClicks  = count($clicks);
$fraudCount   = count(array_filter($clicks, fn($r) => $r['is_fraud']));
// Stats: only count approved conversions — use conv_revenue/conv_payout when available, else offer revenue/payout
$totalRevenue = array_sum(array_map(fn($r) => ($r['conv_status']==='approved' && !empty($r['conversion_id'])) ? ((float)$r['conv_revenue'] ?: (float)$r['revenue']) : 0, $clicks));
$totalPayout  = array_sum(array_map(fn($r) => ($r['conv_status']==='approved' && !empty($r['conversion_id'])) ? ((float)$r['conv_payout']  ?: (float)$r['payout'])  : 0, $clicks));
$totalProfit  = $totalRevenue - $totalPayout;
$convCount    = count(array_filter($clicks, fn($r) => $r['conv_status']==='approved' && !empty($r['conversion_id'])));

$offerList = Database::fetchAll("SELECT id, name FROM offers WHERE status='active' ORDER BY name");
$affList   = Database::fetchAll("SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates af JOIN users u ON u.id=af.user_id ORDER BY name");

require BASE_PATH . '/views/admin/reports/clicks.php';
