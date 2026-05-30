<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

$action  = $_POST['action'] ?? '';
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(Helpers::postRaw('_token'));

    if ($action === 'add') {
        $type      = $_POST['type'] ?? '';
        $value     = substr(trim($_POST['value'] ?? ''), 0, 255);
        $reason    = substr(trim($_POST['reason'] ?? ''), 0, 255) ?: null;
        $blAction  = $_POST['bl_action'] ?? 'block';
        $scope     = $_POST['scope'] ?? 'all';
        $expiresAt = $_POST['expires_at'] ?? '';

        $validTypes    = ['ip','cidr','user_agent','affiliate_id','device_fp','asn'];
        $validActions  = ['flag','block','throttle'];
        $validScopes   = ['clicks','conversions','all'];

        if (!in_array($type, $validTypes, true) || !in_array($blAction, $validActions, true) || !in_array($scope, $validScopes, true)) {
            $error = 'Invalid type, action, or scope.';
        } elseif ($value === '') {
            $error = 'Value is required.';
        } else {
            $expires = ($expiresAt !== '') ? date('Y-m-d H:i:s', strtotime($expiresAt)) : null;
            try {
                Database::query(
                    "INSERT INTO fraud_blocklist (type,value,reason,action,scope,source,expires_at,is_active,created_by) VALUES (?,?,?,?,?,'manual',?,1,?)
                     ON DUPLICATE KEY UPDATE reason=VALUES(reason),action=VALUES(action),scope=VALUES(scope),expires_at=VALUES(expires_at),is_active=1",
                    [$type, $value, $reason, $blAction, $scope, $expires, Auth::currentUser()['id'] ?? null]
                );
                $message = 'Entry added to blocklist.';
            } catch (\Exception $e) { $error = 'Add failed.'; }
        }

    } elseif ($action === 'toggle') {
        $entryId = (int)($_POST['entry_id'] ?? 0);
        $newVal  = (int)($_POST['active'] ?? 0);
        try {
            Database::query("UPDATE fraud_blocklist SET is_active=? WHERE id=?", [$newVal, $entryId]);
            $message = 'Entry ' . ($newVal ? 'enabled' : 'disabled') . '.';
        } catch (\Exception $e) { $error = 'Update failed.'; }

    } elseif ($action === 'delete') {
        $entryId = (int)($_POST['entry_id'] ?? 0);
        try {
            Database::query("DELETE FROM fraud_blocklist WHERE id=?", [$entryId]);
            $message = 'Entry removed.';
        } catch (\Exception $e) { $error = 'Remove failed.'; }
    }
}

// Filters
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 50;
$filterType = $_GET['type'] ?? '';
$search    = trim($_GET['q'] ?? '');

$dr = fraud_date_range();
$where  = "created_at BETWEEN ? AND ?";
$params = [$dr['date_from'], $dr['date_to']];
if ($filterType !== '') { $where .= " AND type=?"; $params[] = $filterType; }
if ($search !== '') { $where .= " AND (value LIKE ? OR reason LIKE ?)"; $s = "%$search%"; $params[] = $s; $params[] = $s; }

$totalRow = Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_blocklist WHERE $where", $params);
$total    = $totalRow['c'] ?? 0;
$pag      = fraud_paginate($total, $perPage, $page);

$entries = Database::fetchAll(
    "SELECT * FROM fraud_blocklist WHERE $where ORDER BY is_active DESC, created_at DESC LIMIT $perPage OFFSET {$pag['offset']}",
    $params
) ?: [];

$stats = [
    'total'  => Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_blocklist")['c'] ?? 0,
    'active' => Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_blocklist WHERE is_active=1")['c'] ?? 0,
    'ips'    => Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_blocklist WHERE type='ip' AND is_active=1")['c'] ?? 0,
    'hits'   => Database::fetchOne("SELECT COALESCE(SUM(hit_count),0) AS c FROM fraud_blocklist")['c'] ?? 0,
];

$validTypes   = ['ip','cidr','user_agent','affiliate_id','device_fp','asn'];
$validActions = ['flag','block','throttle'];
$validScopes  = ['clicks','conversions','all'];

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="blocklist-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Type','Value','Reason','Action','Scope','Active','Hit Count','Created At','Expires At']);
    $exp = Database::fetchAll("SELECT * FROM fraud_blocklist WHERE $where ORDER BY is_active DESC, created_at DESC LIMIT 10000", $params) ?: [];
    foreach ($exp as $r) fputcsv($f, [$r['type'],$r['value'],$r['reason']??'',$r['action'],$r['scope'],$r['is_active']?'Yes':'No',$r['hit_count']??0,$r['created_at'],$r['expires_at']??'']);
    fclose($f); exit;
}

require BASE_PATH . '/views/admin/fraud_center/blocklist.php';
