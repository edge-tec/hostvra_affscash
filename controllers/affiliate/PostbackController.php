<?php
Auth::check('affiliate');
$pageTitle = 'Postback Setup';
$affId  = Auth::affiliateId();
$appUrl = Config::get('config', 'app.url') ?? '';

// ── Schema migrations (MySQL 5.6+ compatible — no ADD COLUMN IF NOT EXISTS) ──
try { Database::query("ALTER TABLE `affiliates` ADD COLUMN `global_postback_url` VARCHAR(2000) DEFAULT NULL AFTER `notes`"); } catch (\Throwable $e) {}
$_affCols = ['global_pb_admin_status'=>"ENUM('pending','approved','rejected') DEFAULT NULL",'global_pb_admin_note'=>"VARCHAR(500) DEFAULT NULL",'global_pb_submitted_at'=>"DATETIME DEFAULT NULL",'global_pb_reviewed_at'=>"DATETIME DEFAULT NULL",'global_pb_reviewed_by'=>"INT UNSIGNED DEFAULT NULL",'global_pb_active'=>"TINYINT(1) DEFAULT 1"];
foreach ($_affCols as $_col => $_def) {
    // Use INFORMATION_SCHEMA — SHOW COLUMNS LIKE ? is not supported by MariaDB prepared statements
    if (!Database::fetchOne("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='affiliates' AND COLUMN_NAME=?", [$_col])) {
        try { Database::query("ALTER TABLE `affiliates` ADD COLUMN `{$_col}` {$_def}"); } catch (\Throwable $e) {}
    }
}
unset($_affCols, $_col, $_def);
// Auto-approve any existing URL (NULL status or NULL active flag)
try { Database::query("UPDATE `affiliates` SET `global_pb_admin_status`='approved', `global_pb_active`=1 WHERE `global_postback_url` IS NOT NULL AND `global_postback_url`!='' AND (`global_pb_admin_status` IS NULL OR `global_pb_active` IS NULL)"); } catch (\Throwable $e) {}
// Also auto-approve URLs stuck in 'pending' — no manual admin approval required
try { Database::query("UPDATE `affiliates` SET `global_pb_admin_status`='approved', `global_pb_active`=1 WHERE `global_postback_url` IS NOT NULL AND `global_postback_url`!='' AND `global_pb_admin_status`='pending'"); } catch (\Throwable $e) {}

// ── POST: save or clear global postback URL ───────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $action = Helpers::post('action');

    if ($action === 'save_global') {
        $url = trim(Helpers::postRaw('global_postback_url'));
        if ($url && !filter_var(strtok($url, '?'), FILTER_VALIDATE_URL)) {
            Helpers::flash('error', 'Invalid URL. Must start with https://');
            Helpers::redirect('/affiliate/postbacks');
        }
        if ($url) {
            $current = Database::fetchOne(
                "SELECT global_postback_url, global_pb_admin_status, global_pb_submitted_at FROM affiliates WHERE id=?",
                [$affId]
            );
            $isNew = ($current['global_postback_url'] !== $url);
            // Auto-approve immediately — no manual admin approval step required
            Database::update('affiliates', [
                'global_postback_url'    => $url,
                'global_pb_admin_status' => 'approved',
                'global_pb_active'       => 1,
                'global_pb_submitted_at' => $isNew ? date('Y-m-d H:i:s') : ($current['global_pb_submitted_at'] ?? null),
                'global_pb_admin_note'   => null,
            ], 'id=?', [$affId]);
            Helpers::flash('success', 'Global postback URL saved and activated.');
        } else {
            Database::update('affiliates', [
                'global_postback_url'    => null,
                'global_pb_admin_status' => null,
                'global_pb_admin_note'   => null,
                'global_pb_submitted_at' => null,
            ], 'id=?', [$affId]);
            Helpers::flash('success', 'Global postback URL cleared.');
        }
        Helpers::redirect('/affiliate/postbacks');
    }

    if ($action === 'clear_global') {
        Database::update('affiliates', [
            'global_postback_url'    => null,
            'global_pb_admin_status' => null,
            'global_pb_admin_note'   => null,
            'global_pb_submitted_at' => null,
        ], 'id=?', [$affId]);
        Helpers::flash('success', 'Global postback URL cleared.');
        Helpers::redirect('/affiliate/postbacks');
    }

    Helpers::redirect('/affiliate/postbacks');
}

// ── Page data ─────────────────────────────────────────────────────────────
$affiliateRow        = Database::fetchOne(
    "SELECT global_postback_url, global_pb_admin_status, global_pb_admin_note FROM affiliates WHERE id=?",
    [$affId]
);
$globalPostbackUrl   = $affiliateRow['global_postback_url'] ?? '';
$globalPbAdminStatus = $affiliateRow['global_pb_admin_status'] ?? null;
$globalPbAdminNote   = $affiliateRow['global_pb_admin_note'] ?? '';

// Recent global postback fires for this affiliate.
// Uses JOIN instead of LIMIT-in-subquery — MariaDB <10.4 does not support
// LIMIT inside IN/ANY/ALL/SOME subqueries (SQLSTATE 42000 / error 1235).
$logs = Database::fetchAll(
    "SELECT pl.conversion_id, pl.fired_url, pl.http_status,
            pl.response_body, pl.is_success, pl.fired_at
     FROM postback_logs pl
     INNER JOIN conversions c ON c.conversion_id = pl.conversion_id
     WHERE pl.postback_id = 0
       AND c.affiliate_id = ?
     ORDER BY pl.fired_at DESC
     LIMIT 20",
    [$affId]
);

require BASE_PATH . '/views/affiliate/postbacks/index.php';
