<?php
/**
 * Advertiser — Click Report.
 * Lists every click against the advertiser's offers with the full per-click
 * data the Advertiser Reporting spec requires, including derived conversion
 * status (CONVERT / NOT CONVERT). Filters: date range, offer, affiliate,
 * country, device, conversion status, click ID, aff click ID.
 *
 * Access: advertiser only. Affiliates and unauthenticated users are denied
 * by the controller's first line; the advertiser_id binding makes it
 * impossible to view another advertiser's data.
 */
Auth::check('advertiser');
$pageTitle = 'Click Report';
$advId     = Auth::advertiserId();

// Idempotent migration in case the column wasn't added at first click.
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `browser_version` VARCHAR(64) DEFAULT '' AFTER `browser`"); } catch (\Throwable $_) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `os_version`      VARCHAR(64) DEFAULT '' AFTER `os`"); }      catch (\Throwable $_) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `device_brand`    VARCHAR(64) DEFAULT '' AFTER `device_type`"); } catch (\Throwable $_) {}
try { Database::query("ALTER TABLE `clicks` ADD COLUMN `device_model`    VARCHAR(128) DEFAULT '' AFTER `device_brand`"); } catch (\Throwable $_) {}

// ── Filters ─────────────────────────────────────────────────────────────────
$from        = Helpers::get('from') ?: date('Y-m-01');
$to          = Helpers::get('to')   ?: date('Y-m-d');
$offerId     = (int)Helpers::get('offer_id');
$affId       = (int)Helpers::get('affiliate_id');
$country     = strtoupper(substr(trim(Helpers::get('country') ?? ''), 0, 2));
$device      = Helpers::get('device');
$convStatus  = Helpers::get('conv_status');   // '', 'converted', 'not_converted'
$clickIdQ    = trim(Helpers::get('click_id') ?? '');
$affClickIdQ = trim(Helpers::get('aff_click_id') ?? '');
$page        = max(1, (int)Helpers::get('page'));
$perPage     = 50;
$offset      = ($page - 1) * $perPage;

$where  = ['o.advertiser_id = ?'];
$params = [$advId];

if ($from && $to) {
    $where[]  = 'c.clicked_at BETWEEN ? AND ?';
    $params[] = $from . ' 00:00:00';
    $params[] = $to   . ' 23:59:59';
}
if ($offerId) { $where[] = 'c.offer_id = ?';     $params[] = $offerId; }
if ($affId)   { $where[] = 'c.affiliate_id = ?'; $params[] = $affId; }
if ($country) { $where[] = 'c.country = ?';      $params[] = $country; }
if (in_array($device, ['desktop','mobile','tablet','bot','unknown'], true)) {
    $where[] = 'c.device_type = ?'; $params[] = $device;
}
if ($clickIdQ !== '')    { $where[] = 'c.click_id LIKE ?'; $params[] = $clickIdQ . '%'; }
if ($affClickIdQ !== '') { $where[] = 'c.sub1 LIKE ?';     $params[] = $affClickIdQ . '%'; }
if ($convStatus === 'converted') {
    $where[] = 'cv.id IS NOT NULL';
} elseif ($convStatus === 'not_converted') {
    $where[] = 'cv.id IS NULL';
}

$whereStr = implode(' AND ', $where);

// ── Totals (for header + pagination) ───────────────────────────────────────
try {
    $totalRow = Database::fetchOne(
        "SELECT COUNT(*) AS c
         FROM clicks c
         JOIN offers o ON o.id = c.offer_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status <> 'rejected'
         WHERE $whereStr",
        $params
    );
} catch (\Throwable $_) { $totalRow = ['c' => 0]; }
$total = (int)($totalRow['c'] ?? 0);
$pages = max(1, (int)ceil($total / $perPage));

// ── Click rows ──────────────────────────────────────────────────────────────
try {
    $clicks = Database::fetchAll(
        "SELECT
            c.id, c.click_id, c.clicked_at,
            c.sub1, c.sub2, c.sub3, c.sub4,
            c.ip_address, c.country, c.region, c.city,
            c.user_agent, c.device_type, c.device_brand, c.device_model,
            c.os, c.os_version, c.browser, c.browser_version,
            c.status AS click_status,
            o.id AS offer_id, o.name AS offer_name,
            af.id AS aff_id, af.affiliate_code,
            CONCAT(u.first_name, ' ', u.last_name) AS aff_name,
            cv.id AS conv_id, cv.status AS conv_status
         FROM clicks c
         JOIN offers o      ON o.id = c.offer_id
         LEFT JOIN affiliates af ON af.id = c.affiliate_id
         LEFT JOIN users u  ON u.id = af.user_id
         LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status <> 'rejected'
         WHERE $whereStr
         ORDER BY c.clicked_at DESC
         LIMIT $perPage OFFSET $offset",
        $params
    ) ?: [];
} catch (\Throwable $_) { $clicks = []; }

// Filter dropdown data — only the advertiser's offers and affiliates that
// have sent traffic to those offers.
try {
    $myOffers = Database::fetchAll(
        "SELECT id, name FROM offers WHERE advertiser_id=? ORDER BY name",
        [$advId]
    ) ?: [];
} catch (\Throwable $_) { $myOffers = []; }

try {
    $myAffiliates = Database::fetchAll(
        "SELECT DISTINCT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name
         FROM clicks c
         JOIN offers o      ON o.id = c.offer_id
         JOIN affiliates af ON af.id = c.affiliate_id
         JOIN users u       ON u.id = af.user_id
         WHERE o.advertiser_id = ?
         ORDER BY name LIMIT 500",
        [$advId]
    ) ?: [];
} catch (\Throwable $_) { $myAffiliates = []; }

try {
    $countries = Database::fetchAll(
        "SELECT DISTINCT c.country
         FROM clicks c
         JOIN offers o ON o.id = c.offer_id
         WHERE o.advertiser_id = ? AND c.country != ''
         ORDER BY c.country",
        [$advId]
    ) ?: [];
} catch (\Throwable $_) { $countries = []; }

// CSV export
if (Helpers::get('export') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="click-report-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Offer','Affiliate','Click ID','Aff Click ID','Aff Sub 1','Aff Sub 2','Aff Sub 3',
        'OS Name','OS Version','Browser','Browser Version','User Agent',
        'Device Brand','Device Model','Device Type','IP Address','Country','Region','City',
        'Conversion Status','Clicked At',
    ]);
    try {
        $all = Database::fetchAll(
            "SELECT c.*, o.name AS offer_name, af.affiliate_code,
                    CONCAT(u.first_name,' ',u.last_name) AS aff_name,
                    cv.id AS conv_id
             FROM clicks c
             JOIN offers o      ON o.id = c.offer_id
             LEFT JOIN affiliates af ON af.id = c.affiliate_id
             LEFT JOIN users u  ON u.id = af.user_id
             LEFT JOIN conversions cv ON cv.click_id = c.click_id AND cv.status <> 'rejected'
             WHERE $whereStr
             ORDER BY c.clicked_at DESC
             LIMIT 50000",
            $params
        ) ?: [];
    } catch (\Throwable $_) { $all = []; }
    foreach ($all as $r) {
        fputcsv($out, [
            $r['offer_name'],
            $r['aff_name'] . ' (' . ($r['affiliate_code'] ?? '') . ')',
            $r['click_id'],
            $r['sub1'] ?? '',
            $r['sub2'] ?? '',
            $r['sub3'] ?? '',
            $r['sub4'] ?? '',
            $r['os'] ?? '',
            $r['os_version'] ?? '',
            $r['browser'] ?? '',
            $r['browser_version'] ?? '',
            $r['user_agent'] ?? '',
            $r['device_brand'] ?? '',
            $r['device_model'] ?? '',
            $r['device_type'] ?? '',
            $r['ip_address'] ?? '',
            $r['country'] ?? '',
            $r['region'] ?? '',
            $r['city'] ?? '',
            $r['conv_id'] ? 'CONVERT' : 'NOT CONVERT',
            $r['clicked_at'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

require BASE_PATH . '/views/advertiser/reports/clicks.php';
