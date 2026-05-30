<?php
Auth::check('admin');
$pageTitle = 'Fraud Alerts';

FraudAutoNotify::ensureSchema();

// ─── ACTION: backfill — creates missing fraud alerts for every historical
// high-risk conversion. Admin can trigger this from the page header button.
$backfillMessage = '';
if (Helpers::isPost() && Helpers::post('action') === 'backfill') {
    if (Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        // Run a couple of passes so a large backlog gets cleared in one click.
        $totalAdded = 0;
        for ($i = 0; $i < 10; $i++) {
            $n = FraudAutoNotify::backfillRecent(0, 1000);
            $totalAdded += $n;
            if ($n < 1000) break; // drained
        }
        // Reset session flag so the realtime poll also re-evaluates.
        $_SESSION['fa_backfill_admin_done'] = 1;
        $backfillMessage = $totalAdded > 0
            ? ($totalAdded . ' missing fraud alert(s) restored from history.')
            : 'No missing fraud alerts — your history is fully synced.';
    } else {
        $backfillMessage = 'CSRF token mismatch — please reload and try again.';
    }
}

// Filters
$status = Helpers::get('status') ?: 'open';      // open | resolved | all
$risk   = Helpers::get('risk')   ?: 'all';       // high | medium | all
$affId  = (int)Helpers::get('affiliate_id');

$where  = ["n.category = 'fraud'"];
$params = [];
if ($status === 'open')     $where[] = 'n.resolved_at IS NULL';
if ($status === 'resolved') $where[] = 'n.resolved_at IS NOT NULL';

// affiliate filter (by users.id resolved from affiliate_id)
if ($affId > 0) {
    $row = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$affId]);
    if ($row) { $where[] = 'n.user_id = ?'; $params[] = (int)$row['user_id']; }
}

$whereSql = implode(' AND ', $where);

try {
    $alerts = Database::fetchAll(
        "SELECT n.*, u.email AS affiliate_email,
                CONCAT(u.first_name,' ',u.last_name) AS affiliate_name,
                af.id AS affiliate_id, af.affiliate_code
         FROM notifications n
         LEFT JOIN users u ON u.id = n.user_id
         LEFT JOIN affiliates af ON af.user_id = u.id
         WHERE $whereSql
         ORDER BY n.id DESC LIMIT 500",
        $params
    );
} catch (\Throwable $e) { $alerts = []; }

// Decode meta + optional risk filter (in PHP since meta is JSON not a column).
$rows = [];
foreach ($alerts as $a) {
    $meta = [];
    if (!empty($a['meta'])) { $tmp = json_decode($a['meta'], true); if (is_array($tmp)) $meta = $tmp; }
    $rowRisk = $meta['risk_level'] ?? 'medium';
    if ($risk === 'high' && $rowRisk !== 'high') continue;
    if ($risk === 'medium' && $rowRisk !== 'medium') continue;
    $a['_meta']     = $meta;
    $a['_risk']     = $rowRisk;
    $rows[] = $a;
}

require BASE_PATH . '/views/admin/fraud_alerts/index.php';
