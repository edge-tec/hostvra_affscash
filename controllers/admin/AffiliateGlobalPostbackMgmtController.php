<?php
/**
 * Admin — Affiliate Global Postback Management
 *
 * Gives admin full control over every affiliate's global postback URL:
 *   – View all submitted global postback URLs
 *   – Approve / Reject (with optional note)
 *   – Activate / Deactivate (toggle approved postbacks on or off)
 *   – Delete (clear URL entirely)
 *
 * Security: only fires when admin_status='approved' AND active flag is set.
 */
Auth::checkAny(['admin', 'affiliate_manager']);
$pageTitle = 'Global Postback Management';

// ── Runtime migration — ensure columns exist (MySQL 5.6+ compatible) ─────
$_mgmtCols = ['global_pb_admin_status'=>"ENUM('pending','approved','rejected') DEFAULT NULL",'global_pb_admin_note'=>"VARCHAR(500) DEFAULT NULL",'global_pb_submitted_at'=>"DATETIME DEFAULT NULL",'global_pb_reviewed_at'=>"DATETIME DEFAULT NULL",'global_pb_reviewed_by'=>"INT UNSIGNED DEFAULT NULL",'global_pb_active'=>"TINYINT(1) DEFAULT 1"];
foreach ($_mgmtCols as $_col => $_def) {
    if (!Database::fetchOne("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='affiliates' AND COLUMN_NAME=?", [$_col])) {
        try { Database::query("ALTER TABLE `affiliates` ADD COLUMN `{$_col}` {$_def}"); } catch (\Throwable $e) {}
    }
}
unset($_mgmtCols, $_col, $_def);
// Auto-approve existing URLs (NULL status = pre-dates this system)
try { Database::query("UPDATE `affiliates` SET `global_pb_admin_status`='approved', `global_pb_active`=1 WHERE `global_postback_url` IS NOT NULL AND `global_postback_url`!='' AND `global_pb_admin_status` IS NULL"); } catch (\Throwable $e) {}

// ── POST actions ──────────────────────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $action = Helpers::post('action');
    $affId  = (int)Helpers::postRaw('aff_id');

    if (!$affId) {
        Helpers::flash('error', 'Invalid affiliate ID.');
        Helpers::redirect('/admin/affiliate-global-postbacks');
    }

    $adminId = Auth::id();
    $now     = date('Y-m-d H:i:s');

    // ── Approve ───────────────────────────────────────────────────────────
    if ($action === 'approve') {
        Database::update('affiliates', [
            'global_pb_admin_status' => 'approved',
            'global_pb_admin_note'   => null,
            'global_pb_active'       => 1,
            'global_pb_reviewed_at'  => $now,
            'global_pb_reviewed_by'  => $adminId,
        ], 'id=?', [$affId]);
        // Notify affiliate
        try {
            $affUser = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$affId]);
            if ($affUser) {
                Database::insert('notifications', [
                    'user_id'     => $affUser['user_id'],
                    'target_role' => 'affiliate',
                    'type'        => 'success',
                    'title'       => 'Global Postback Approved',
                    'message'     => 'Your global postback URL has been approved and is now active.',
                    'link'        => '/affiliate/postbacks',
                ]);
            }
        } catch (\Throwable $e) {}
        Helpers::flash('success', 'Global postback approved and activated.');
    }

    // ── Reject ────────────────────────────────────────────────────────────
    elseif ($action === 'reject') {
        $note = trim(Helpers::postRaw('admin_note'));
        Database::update('affiliates', [
            'global_pb_admin_status' => 'rejected',
            'global_pb_admin_note'   => $note ?: null,
            'global_pb_active'       => 0,
            'global_pb_reviewed_at'  => $now,
            'global_pb_reviewed_by'  => $adminId,
        ], 'id=?', [$affId]);
        // Notify affiliate
        try {
            $affUser = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$affId]);
            if ($affUser) {
                Database::insert('notifications', [
                    'user_id'     => $affUser['user_id'],
                    'target_role' => 'affiliate',
                    'type'        => 'warning',
                    'title'       => 'Global Postback Rejected',
                    'message'     => 'Your global postback URL was rejected.' . ($note ? ' Reason: ' . $note : ' Please update and resubmit.'),
                    'link'        => '/affiliate/postbacks',
                ]);
            }
        } catch (\Throwable $e) {}
        Helpers::flash('success', 'Global postback rejected.');
    }

    // ── Toggle active/inactive (approved postbacks only) ──────────────────
    elseif ($action === 'toggle') {
        $current = Database::fetchOne("SELECT global_pb_active, global_pb_admin_status FROM affiliates WHERE id=?", [$affId]);
        if ($current && $current['global_pb_admin_status'] === 'approved') {
            $newActive = ((int)$current['global_pb_active'] === 1) ? 0 : 1;
            Database::update('affiliates', ['global_pb_active' => $newActive], 'id=?', [$affId]);
            Helpers::flash('success', $newActive ? 'Global postback activated.' : 'Global postback deactivated.');
        } else {
            Helpers::flash('error', 'Only approved postbacks can be toggled. Approve the postback first.');
        }
    }

    // ── Delete (clear URL entirely) ───────────────────────────────────────
    elseif ($action === 'delete') {
        Database::update('affiliates', [
            'global_postback_url'    => null,
            'global_pb_admin_status' => null,
            'global_pb_admin_note'   => null,
            'global_pb_active'       => 0,
            'global_pb_submitted_at' => null,
            'global_pb_reviewed_at'  => null,
            'global_pb_reviewed_by'  => null,
        ], 'id=?', [$affId]);
        // Notify affiliate
        try {
            $affUser = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$affId]);
            if ($affUser) {
                Database::insert('notifications', [
                    'user_id'     => $affUser['user_id'],
                    'target_role' => 'affiliate',
                    'type'        => 'info',
                    'title'       => 'Global Postback Removed',
                    'message'     => 'Your global postback URL has been removed by an admin. Please resubmit if needed.',
                    'link'        => '/affiliate/postbacks',
                ]);
            }
        } catch (\Throwable $e) {}
        Helpers::flash('success', 'Global postback URL deleted.');
    }

    // ── Re-approve (rejected → approved) ─────────────────────────────────
    elseif ($action === 'reapprove') {
        Database::update('affiliates', [
            'global_pb_admin_status' => 'approved',
            'global_pb_admin_note'   => null,
            'global_pb_active'       => 1,
            'global_pb_reviewed_at'  => $now,
            'global_pb_reviewed_by'  => $adminId,
        ], 'id=?', [$affId]);
        try {
            $affUser = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$affId]);
            if ($affUser) {
                Database::insert('notifications', [
                    'user_id'     => $affUser['user_id'],
                    'target_role' => 'affiliate',
                    'type'        => 'success',
                    'title'       => 'Global Postback Re-Approved',
                    'message'     => 'Your global postback URL has been approved and is now active.',
                    'link'        => '/affiliate/postbacks',
                ]);
            }
        } catch (\Throwable $e) {}
        Helpers::flash('success', 'Global postback re-approved and activated.');
    }

    Helpers::redirect('/admin/affiliate-global-postbacks' . (Helpers::get('status') ? '?status=' . urlencode(Helpers::get('status')) : ''));
}

// ── Load data ─────────────────────────────────────────────────────────────
$filterStatus = Helpers::get('status', 'all');
$filterAffId  = (int)Helpers::get('aff');

$where  = ["af.global_postback_url IS NOT NULL", "af.global_postback_url != ''"];
$params = [];

if ($filterStatus === 'pending')  { $where[] = "af.global_pb_admin_status='pending'";  }
if ($filterStatus === 'approved') { $where[] = "af.global_pb_admin_status='approved'"; }
if ($filterStatus === 'rejected') { $where[] = "af.global_pb_admin_status='rejected'"; }
if ($filterAffId)                 { $where[] = "af.id=?"; $params[] = $filterAffId;    }

$whereStr = 'WHERE ' . implode(' AND ', $where);

$rows = Database::fetchAll(
    "SELECT af.id as aff_id,
            af.affiliate_code,
            af.global_postback_url,
            af.global_pb_admin_status,
            af.global_pb_admin_note,
            af.global_pb_active,
            af.global_pb_submitted_at,
            af.global_pb_reviewed_at,
            CONCAT(u.first_name,' ',u.last_name) as aff_name,
            u.email as aff_email
     FROM affiliates af
     JOIN users u ON u.id=af.user_id
     $whereStr
     ORDER BY
        CASE af.global_pb_admin_status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END,
        af.global_pb_submitted_at DESC
     LIMIT 500",
    $params
);

// Counts for tabs
$counts = [];
foreach (['pending', 'approved', 'rejected'] as $s) {
    $counts[$s] = Database::fetchOne(
        "SELECT COUNT(*) as c FROM affiliates WHERE global_postback_url IS NOT NULL AND global_postback_url!='' AND global_pb_admin_status=?",
        [$s]
    )['c'] ?? 0;
}
$counts['all'] = Database::fetchOne(
    "SELECT COUNT(*) as c FROM affiliates WHERE global_postback_url IS NOT NULL AND global_postback_url!=''"
)['c'] ?? 0;

$affiliateList = Database::fetchAll(
    "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as label
     FROM affiliates af JOIN users u ON u.id=af.user_id
     WHERE af.global_postback_url IS NOT NULL AND af.global_postback_url!=''
     ORDER BY u.first_name, u.last_name"
);

require BASE_PATH . '/views/admin/affiliate_global_postbacks/index.php';
