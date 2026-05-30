<?php
/**
 * Advertiser — Conversion Report.
 *
 * One row per conversion against the advertiser's offers, joined to the
 * originating click for device / geo context. Aggregate CR / CTR columns
 * are computed per (offer_id, affiliate_id) within the filter window so
 * they reflect the same scope the operator is looking at — no N+1.
 *
 * Access: advertiser only. advertiser_id is enforced on the offers join
 * so an account can never see another advertiser's data.
 */
Auth::check('advertiser');
$pageTitle = 'Conversion Report';
$advId     = Auth::advertiserId();

// ── Filters ─────────────────────────────────────────────────────────────────
$from         = Helpers::get('from') ?: date('Y-m-01');
$to           = Helpers::get('to')   ?: date('Y-m-d');
$offerId      = (int)Helpers::get('offer_id');
$affId        = (int)Helpers::get('affiliate_id');
$country      = strtoupper(substr(trim(Helpers::get('country') ?? ''), 0, 2));
$status       = Helpers::get('status');     // '', 'pending', 'approved', 'rejected', 'chargebacked'
$goal         = trim(Helpers::get('goal') ?? '');
$txnIdQ       = trim(Helpers::get('txn_id') ?? '');
$clickIdQ     = trim(Helpers::get('click_id') ?? '');
$convIdQ      = trim(Helpers::get('conversion_id') ?? '');
$page         = max(1, (int)Helpers::get('page'));
$perPage      = 50;
$offset       = ($page - 1) * $perPage;
$export       = Helpers::get('export') === 'csv';

// Rejected conversions are hidden from advertisers — they shouldn't see
// quality / fraud rejections in their account. The base WHERE applies the
// exclusion before any user filter, and the status dropdown no longer
// lists 'rejected' as an option, so a crafted ?status=rejected URL still
// returns zero rows.
$where  = ['o.advertiser_id = ?', "cv.status <> 'rejected'"];
$params = [$advId];

if ($from && $to) {
    $where[]  = 'cv.converted_at BETWEEN ? AND ?';
    $params[] = $from . ' 00:00:00';
    $params[] = $to   . ' 23:59:59';
}
if ($offerId)      { $where[] = 'cv.offer_id = ?';     $params[] = $offerId; }
if ($affId)        { $where[] = 'cv.affiliate_id = ?'; $params[] = $affId; }
if ($country)      { $where[] = 'c.country = ?';       $params[] = $country; }
if (in_array($status, ['pending','approved','chargebacked'], true)) {
    $where[] = 'cv.status = ?'; $params[] = $status;
}
if ($goal !== '')     { $where[] = 'cv.goal_name = ?';        $params[] = $goal; }
if ($txnIdQ !== '')   { $where[] = 'cv.transaction_id LIKE ?'; $params[] = $txnIdQ . '%'; }
if ($clickIdQ !== '') { $where[] = 'cv.click_id LIKE ?';       $params[] = $clickIdQ . '%'; }
if ($convIdQ !== '')  { $where[] = 'cv.conversion_id LIKE ?';  $params[] = $convIdQ . '%'; }

$whereStr = implode(' AND ', $where);

// ── Totals (header + pagination) ───────────────────────────────────────────
try {
    $totalRow = Database::fetchOne(
        "SELECT COUNT(*) AS c
         FROM conversions cv
         JOIN offers o ON o.id = cv.offer_id
         LEFT JOIN clicks c ON c.click_id = cv.click_id
         WHERE $whereStr",
        $params
    );
} catch (\Throwable $_) { $totalRow = ['c' => 0]; }
$total = (int)($totalRow['c'] ?? 0);
$pages = max(1, (int)ceil($total / $perPage));

// Sum panel (visible totals) — uses the same filter set, no pagination.
try {
    $sumRow = Database::fetchOne(
        "SELECT SUM(cv.payout)  AS payout,
                SUM(cv.revenue) AS revenue,
                SUM(CASE WHEN cv.status='approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN cv.status='pending'  THEN 1 ELSE 0 END) AS pending
         FROM conversions cv
         JOIN offers o ON o.id = cv.offer_id
         LEFT JOIN clicks c ON c.click_id = cv.click_id
         WHERE $whereStr",
        $params
    ) ?: [];
} catch (\Throwable $_) { $sumRow = []; }

// ── Conversion rows ────────────────────────────────────────────────────────
$limitClause = $export ? 'LIMIT 50000' : "LIMIT $perPage OFFSET $offset";
try {
    $rows = Database::fetchAll(
        "SELECT
            cv.id                AS row_id,
            cv.conversion_id,
            cv.click_id,
            cv.offer_id,
            cv.affiliate_id,
            cv.payout, cv.revenue,
            cv.status            AS conv_status,
            cv.goal_name,
            cv.transaction_id    AS txn_id,
            cv.ip_address        AS conv_ip,
            cv.postback_sent,
            cv.postback_sent_at,
            cv.converted_at,
            o.name               AS offer_name,
            o.offer_url          AS offer_page,
            o.category           AS offer_category,
            o.landing_pages      AS landing_pages_json,
            af.affiliate_code,
            CONCAT(u.first_name,' ',u.last_name) AS aff_name,
            c.sub1               AS aff_click_id,
            c.sub2               AS aff_sub2,
            c.country            AS click_country,
            c.os, c.browser, c.user_agent,
            c.device_brand, c.device_model, c.device_type,
            c.landing_page_idx,
            c.smartlink_id       AS flow_id
         FROM conversions cv
         JOIN offers o      ON o.id = cv.offer_id
         LEFT JOIN affiliates af ON af.id = cv.affiliate_id
         LEFT JOIN users u  ON u.id = af.user_id
         LEFT JOIN clicks c ON c.click_id = cv.click_id
         WHERE $whereStr
         ORDER BY cv.converted_at DESC
         $limitClause",
        $params
    ) ?: [];
} catch (\Throwable $_) { $rows = []; }

// ── Aggregate CR / CTR per (offer_id, affiliate_id) within the date window.
// One query for every (offer, affiliate) pair appearing on the page — avoids
// N+1 and keeps numbers consistent with the filter the user is viewing.
$aggMap = [];
if (!empty($rows)) {
    $pairs = [];
    foreach ($rows as $r) $pairs[$r['offer_id'].':'.$r['affiliate_id']] = [$r['offer_id'], $r['affiliate_id']];
    $pairs = array_values($pairs);

    $ph    = [];
    $aggP  = [];
    foreach ($pairs as [$oid, $aid]) {
        $ph[]   = '(?,?)';
        $aggP[] = $oid; $aggP[] = $aid;
    }
    $pairsSql = '(c.offer_id, c.affiliate_id) IN (' . implode(',', $ph) . ')';
    $dateBetween = '';
    if ($from && $to) {
        $dateBetween = 'AND c.clicked_at BETWEEN ? AND ? ';
        $aggP[] = $from . ' 00:00:00';
        $aggP[] = $to   . ' 23:59:59';
    }
    try {
        $aggRows = Database::fetchAll(
            "SELECT
                c.offer_id, c.affiliate_id,
                COUNT(*)                                              AS visits,
                SUM(CASE WHEN c.status='valid' THEN 1 ELSE 0 END)     AS clicks,
                SUM(CASE WHEN c.is_unique=1 AND c.status='valid' THEN 1 ELSE 0 END) AS unique_clicks,
                (SELECT COUNT(*) FROM conversions cvi
                  WHERE cvi.offer_id = c.offer_id AND cvi.affiliate_id = c.affiliate_id
                    AND cvi.converted_at BETWEEN ? AND ?)             AS conversions
             FROM clicks c
             WHERE $pairsSql $dateBetween
             GROUP BY c.offer_id, c.affiliate_id",
            array_merge([$from.' 00:00:00', $to.' 23:59:59'], $aggP)
        ) ?: [];
        foreach ($aggRows as $a) {
            $aggMap[$a['offer_id'].':'.$a['affiliate_id']] = $a;
        }
    } catch (\Throwable $_) {}
}

// ── LP name resolver — landing_pages may be a JSON array; pick by idx.
$resolveLpName = static function(?string $json, $idx): string {
    if (!$json) return '';
    if ($idx === null || $idx === '') return '';
    $list = json_decode($json, true);
    if (!is_array($list)) return '';
    $row  = $list[$idx] ?? null;
    if (!$row) return '';
    if (is_string($row)) return $row;
    return (string)($row['name'] ?? $row['title'] ?? '');
};

// ── Dropdown sources ──────────────────────────────────────────────────────
try {
    $myOffers = Database::fetchAll(
        "SELECT id, name FROM offers WHERE advertiser_id=? ORDER BY name",
        [$advId]
    ) ?: [];
} catch (\Throwable $_) { $myOffers = []; }

try {
    $myAffiliates = Database::fetchAll(
        "SELECT DISTINCT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name
         FROM conversions cv
         JOIN offers o      ON o.id = cv.offer_id
         JOIN affiliates af ON af.id = cv.affiliate_id
         JOIN users u       ON u.id = af.user_id
         WHERE o.advertiser_id = ?
         ORDER BY name LIMIT 500",
        [$advId]
    ) ?: [];
} catch (\Throwable $_) { $myAffiliates = []; }

try {
    $goalOptions = Database::fetchAll(
        "SELECT DISTINCT cv.goal_name
         FROM conversions cv
         JOIN offers o ON o.id = cv.offer_id
         WHERE o.advertiser_id = ? AND cv.goal_name IS NOT NULL AND cv.goal_name <> ''
         ORDER BY cv.goal_name LIMIT 100",
        [$advId]
    ) ?: [];
} catch (\Throwable $_) { $goalOptions = []; }

try {
    $countries = Database::fetchAll(
        "SELECT DISTINCT c.country
         FROM conversions cv
         JOIN offers o     ON o.id = cv.offer_id
         JOIN clicks c     ON c.click_id = cv.click_id
         WHERE o.advertiser_id = ? AND c.country <> ''
         ORDER BY c.country",
        [$advId]
    ) ?: [];
} catch (\Throwable $_) { $countries = []; }

// ── CSV export ─────────────────────────────────────────────────────────────
if ($export) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="conversion-report-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Offer','Affiliate','Click ID','Conversion ID','Aff Click ID','Aff Sub 2',
        'Status','Payout','Revenue','Goal','Txn ID','Country','OS','Browser',
        'Conv IP','User Agent','Device Brand','Device Model','Category',
        'Preland','LP Name','Offer Page','Flow ID',
        'CR (Visit) %','CR (Click) %','CR (Unique) %','CTR %','Postback','Converted At',
    ]);
    foreach ($rows as $r) {
        $agg     = $aggMap[$r['offer_id'].':'.$r['affiliate_id']] ?? [];
        $visits  = (int)($agg['visits']  ?? 0);
        $clicksN = (int)($agg['clicks']  ?? 0);
        $uniq    = (int)($agg['unique_clicks'] ?? 0);
        $convN   = (int)($agg['conversions']   ?? 0);
        $crVisit = $visits  > 0 ? round($convN / $visits  * 100, 2) : 0;
        $crClick = $clicksN > 0 ? round($convN / $clicksN * 100, 2) : 0;
        $crUniq  = $uniq    > 0 ? round($convN / $uniq    * 100, 2) : 0;
        $ctr     = $visits  > 0 ? round($clicksN / $visits * 100, 2) : 0;
        $lp      = $resolveLpName($r['landing_pages_json'] ?? null, $r['landing_page_idx'] ?? null);

        fputcsv($out, [
            $r['offer_name'],
            ($r['aff_name'] ?? '') . ($r['affiliate_code'] ? ' ('.$r['affiliate_code'].')' : ''),
            $r['click_id'],
            $r['conversion_id'],
            $r['aff_click_id'] ?? '',
            $r['aff_sub2']     ?? '',
            $r['conv_status'],
            $r['payout'],
            $r['revenue'],
            $r['goal_name'] ?? '',
            $r['txn_id']    ?? '',
            $r['click_country'] ?? '',
            $r['os']       ?? '',
            $r['browser']  ?? '',
            $r['conv_ip']  ?? '',
            $r['user_agent']    ?? '',
            $r['device_brand']  ?? '',
            $r['device_model']  ?? '',
            $r['offer_category']?? '',
            $r['landing_page_idx'] === null ? '' : ('LP '.($r['landing_page_idx']+1)),
            $lp,
            $r['offer_page']    ?? '',
            $r['flow_id']       ?? '',
            $crVisit, $crClick, $crUniq, $ctr,
            $r['postback_sent'] ? 'Sent' : 'Pending',
            $r['converted_at']  ?? '',
        ]);
    }
    fclose($out);
    exit;
}

require BASE_PATH . '/views/advertiser/reports/conversions.php';
