<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

$action  = $_POST['action'] ?? '';
$message = '';
$error   = '';

// ── POST actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(Helpers::postRaw('_token'));

    if ($action === 'create_case') {
        $type      = $_POST['type'] ?? 'click_spam';
        $affId     = (int)($_POST['affiliate_id'] ?? 0) ?: null;
        $refId     = substr(trim($_POST['reference_id'] ?? ''), 0, 255) ?: null;
        $severity  = $_POST['severity'] ?? 'medium';
        $notes     = substr(trim($_POST['notes'] ?? ''), 0, 5000) ?: null;
        $ref       = fraud_new_case_ref();

        $validTypes    = ['click_spam','bot_traffic','fake_conversion','device_abuse','ip_abuse','geo_fraud','duplicate_conv'];
        $validSeverity = ['low','medium','high','critical'];

        if (!in_array($type, $validTypes, true) || !in_array($severity, $validSeverity, true)) {
            $error = 'Invalid type or severity.';
        } else {
            try {
                Database::query(
                    "INSERT INTO fraud_cases (case_ref,type,affiliate_id,reference_id,severity,status,assigned_to,notes) VALUES (?,?,?,?,?,'open',?,?)",
                    [$ref, $type, $affId, $refId, $severity, Auth::currentUser()['id'] ?? null, $notes]
                );
                $message = "Case $ref created.";
            } catch (\Exception $e) { $error = 'Create failed.'; }
        }

    } elseif ($action === 'update_status') {
        $caseId    = (int)($_POST['case_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        $validStatuses = ['open','investigating','resolved','dismissed'];
        if (!in_array($newStatus, $validStatuses, true)) {
            $error = 'Invalid status.';
        } else {
            try {
                $resolvedAt = in_array($newStatus, ['resolved','dismissed']) ? date('Y-m-d H:i:s') : null;
                Database::query("UPDATE fraud_cases SET status=?, resolved_at=? WHERE id=?", [$newStatus, $resolvedAt, $caseId]);
                $message = 'Status updated.';
            } catch (\Exception $e) { $error = 'Update failed.'; }
        }

    } elseif ($action === 'add_note') {
        $caseId = (int)($_POST['case_id'] ?? 0);
        $note   = substr(trim($_POST['notes'] ?? ''), 0, 5000);
        if ($note !== '') {
            try {
                Database::query("UPDATE fraud_cases SET notes=? WHERE id=?", [$note, $caseId]);
                $message = 'Note saved.';
            } catch (\Exception $e) { $error = 'Save failed.'; }
        }
    }
}

// Filters
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 25;
$filterStatus   = $_GET['status'] ?? '';
$filterSeverity = $_GET['severity'] ?? '';
$search         = trim($_GET['q'] ?? '');
$filterAffId    = (int)($_GET['affiliate_id'] ?? 0);

$dr = fraud_date_range();
$where  = "fc.created_at BETWEEN ? AND ?";
$params = [$dr['date_from'], $dr['date_to']];
if ($filterStatus !== '')   { $where .= " AND fc.status=?";   $params[] = $filterStatus; }
if ($filterSeverity !== '') { $where .= " AND fc.severity=?"; $params[] = $filterSeverity; }
if ($filterAffId > 0)       { $where .= " AND fc.affiliate_id=?"; $params[] = $filterAffId; }
if ($search !== '') {
    $where .= " AND (fc.case_ref LIKE ? OR fc.reference_id LIKE ?)";
    $s = "%$search%"; $params[] = $s; $params[] = $s;
}

$totalRow = Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_cases fc WHERE $where", $params);
$total    = $totalRow['c'] ?? 0;
$pag      = fraud_paginate($total, $perPage, $page);

$cases = Database::fetchAll(
    "SELECT fc.*, a.affiliate_code, u.first_name, u.last_name
     FROM fraud_cases fc
     LEFT JOIN affiliates a ON a.id = fc.affiliate_id
     LEFT JOIN users u ON u.id = a.user_id
     WHERE $where
     ORDER BY FIELD(fc.severity,'critical','high','medium','low'), fc.created_at DESC
     LIMIT $perPage OFFSET {$pag['offset']}",
    $params
) ?: [];

// Single case view
$viewCase = null;
if (isset($_GET['view'])) {
    $viewCase = Database::fetchOne(
        "SELECT fc.*, a.affiliate_code, u.first_name, u.last_name
         FROM fraud_cases fc
         LEFT JOIN affiliates a ON a.id=fc.affiliate_id
         LEFT JOIN users u ON u.id=a.user_id
         WHERE fc.id=?", [(int)$_GET['view']]
    );
}

// Counts for summary badges
$counts = [
    'open'          => Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_cases WHERE status='open'")['c'] ?? 0,
    'investigating' => Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_cases WHERE status='investigating'")['c'] ?? 0,
    'critical'      => Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_cases WHERE severity='critical' AND status NOT IN ('resolved','dismissed')")['c'] ?? 0,
];

$affiliates = Database::fetchAll("SELECT a.id, a.affiliate_code, u.first_name, u.last_name FROM affiliates a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.affiliate_code ASC") ?: [];

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="fraud-cases-'.date('Y-m-d').'.csv"');
    $f = fopen('php://output', 'w');
    fputcsv($f, ['Case Ref','Type','Severity','Status','Affiliate','Reference ID','Notes','Created At']);
    $exp = Database::fetchAll(
        "SELECT fc.*, a.affiliate_code FROM fraud_cases fc LEFT JOIN affiliates a ON a.id=fc.affiliate_id WHERE $where ORDER BY fc.created_at DESC LIMIT 10000", $params) ?: [];
    foreach ($exp as $r) fputcsv($f, [$r['case_ref'],$r['type'],$r['severity'],$r['status'],$r['affiliate_code']??'',$r['reference_id']??'',$r['notes']??'',$r['created_at']]);
    fclose($f); exit;
}

require BASE_PATH . '/views/admin/fraud_center/cases.php';
