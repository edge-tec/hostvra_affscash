<?php
/**
 * Admin → Points Module config + balances overview.
 * Read-only against existing earnings — extends via PointsService only.
 */
Auth::check('admin');
$pageTitle = 'Points Module';

if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    if (Helpers::post('action') === 'save_config') {
        $enabled  = isset($_POST['enabled']) && $_POST['enabled'] === '1';
        $usdPerPt = (int)Helpers::postRaw('usd_per_point');
        if ($usdPerPt < 1) $usdPerPt = 1;
        if ($usdPerPt > 100) $usdPerPt = 100;
        PointsService::setConfig($enabled, $usdPerPt);
        Helpers::flash('success', 'Points configuration saved.');
        Helpers::redirect('/admin/points');
    }
    if (Helpers::post('action') === 'adjust') {
        $affId  = (int)Helpers::postRaw('affiliate_id');
        $delta  = (int)Helpers::postRaw('delta');
        $reason = trim((string)Helpers::postRaw('reason'));
        if ($affId > 0 && $delta !== 0) {
            $r = PointsService::adjust($affId, $delta, $reason ?: 'Admin adjustment');
            if ($r === -1) {
                Helpers::flash('error', 'Adjustment rejected: would create negative balance.');
            } else {
                Helpers::flash('success', sprintf('Balance for affiliate #%d is now %d points.', $affId, $r));
            }
        }
        Helpers::redirect('/admin/points');
    }
    if (Helpers::post('action') === 'sync_all') {
        $dryRun = isset($_POST['dry_run']) && $_POST['dry_run'] === '1';
        $since  = trim((string)Helpers::postRaw('since'));
        $result = PointsService::syncAllApproved($dryRun, $since ?: null);
        $msg = sprintf(
            'Sync %s: %d processed, %d credited, %d skipped, %d errors.',
            $dryRun ? '(dry run)' : 'complete',
            $result['processed'],
            $result['credited'],
            $result['skipped'],
            $result['errors']
        );
        Helpers::flash($result['errors'] > 0 ? 'warning' : 'success', $msg);
        // Store log in session so the view can display it.
        $_SESSION['points_sync_log'] = $result['log'] ?? [];
        Helpers::redirect('/admin/points');
    }
}

$cfg = PointsService::config();

// Pull sync log from session if available (set after a sync action).
$syncLog = [];
if (!empty($_SESSION['points_sync_log'])) {
    $syncLog = $_SESSION['points_sync_log'];
    unset($_SESSION['points_sync_log']);
}

// All affiliates with non-zero history. Joined to users for the display name.
$rows = Database::fetchAll(
    "SELECT af.id AS affiliate_id,
            CONCAT(u.first_name,' ',u.last_name) AS name,
            u.email,
            COALESCE(ap.balance, 0)         AS balance,
            COALESCE(ap.lifetime_earned, 0) AS lifetime_earned,
            COALESCE(ap.lifetime_spent, 0)  AS lifetime_spent,
            ap.updated_at
     FROM affiliates af
     JOIN users u ON u.id = af.user_id
     LEFT JOIN affiliate_points ap ON ap.affiliate_id = af.id
     WHERE u.status = 'active'
     ORDER BY balance DESC, lifetime_earned DESC
     LIMIT 500"
) ?: [];

// Recent ledger entries
$recent = Database::fetchAll(
    "SELECT pt.*, CONCAT(u.first_name,' ',u.last_name) AS aff_name, u.email
     FROM points_transactions pt
     LEFT JOIN affiliates af ON af.id = pt.affiliate_id
     LEFT JOIN users u ON u.id = af.user_id
     ORDER BY pt.id DESC LIMIT 50"
) ?: [];

require BASE_PATH . '/views/admin/points/index.php';
