<?php
Auth::check('admin');
require_once BASE_PATH . '/controllers/admin/fraud/_helper.php';
fraud_ensure_tables();

$action  = $_POST['action'] ?? '';
$message = '';
$error   = '';

// ── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf(Helpers::postRaw('_token'));

    if ($action === 'toggle_rule') {
        $ruleId = (int)($_POST['rule_id'] ?? 0);
        $newVal = (int)($_POST['active'] ?? 0);
        try {
            Database::query("UPDATE fraud_rules SET is_active=? WHERE id=?", [$newVal, $ruleId]);
            $message = 'Rule updated.';
        } catch (\Exception $e) { $error = 'Update failed.'; }

    } elseif ($action === 'save_rule') {
        $ruleId   = (int)($_POST['rule_id'] ?? 0);
        $name     = substr(trim($_POST['name'] ?? ''), 0, 100);
        $desc     = substr(trim($_POST['description'] ?? ''), 0, 500);
        $ruleType = $_POST['rule_type'] ?? '';
        $condJson = $_POST['condition_json'] ?? '{}';
        $ruleAction = $_POST['rule_action'] ?? 'flag';
        $isActive = (int)($_POST['is_active'] ?? 1);

        $validTypes   = ['click_rate','low_cvr','geo_mismatch','bot_ua','conversion_time','ip_range','affiliate_age','high_cvr','datacenter_ip','duplicate_ip'];
        $validActions = ['flag','block','alert','auto_case'];

        if (!in_array($ruleType, $validTypes, true) || !in_array($ruleAction, $validActions, true)) {
            $error = 'Invalid rule type or action.';
        } elseif (json_decode($condJson) === null) {
            $error = 'Condition JSON is not valid JSON.';
        } elseif ($name === '') {
            $error = 'Name is required.';
        } else {
            try {
                if ($ruleId > 0) {
                    Database::query("UPDATE fraud_rules SET name=?,description=?,rule_type=?,condition_json=?,action=?,is_active=? WHERE id=?",
                        [$name, $desc, $ruleType, $condJson, $ruleAction, $isActive, $ruleId]);
                    $message = 'Rule saved.';
                } else {
                    Database::query("INSERT INTO fraud_rules (name,description,rule_type,condition_json,action,is_active,created_by) VALUES (?,?,?,?,?,?,?)",
                        [$name, $desc, $ruleType, $condJson, $ruleAction, $isActive, Auth::currentUser()['id'] ?? null]);
                    $message = 'Rule created.';
                }
            } catch (\Exception $e) { $error = 'Save failed.'; }
        }

    } elseif ($action === 'delete_rule') {
        $ruleId = (int)($_POST['rule_id'] ?? 0);
        try {
            Database::query("DELETE FROM fraud_rules WHERE id=?", [$ruleId]);
            $message = 'Rule deleted.';
        } catch (\Exception $e) { $error = 'Delete failed.'; }
    }
}

// Date-filtered rule list (filters by rule creation date).
$dr = fraud_date_range();
$rules = Database::fetchAll(
    "SELECT * FROM fraud_rules WHERE created_at BETWEEN ? AND ?
     ORDER BY is_active DESC, id ASC",
    [$dr['date_from'], $dr['date_to']]
) ?: [];

// Edit mode
$editRule = null;
if (isset($_GET['edit'])) {
    $editId   = (int)$_GET['edit'];
    $editRule = Database::fetchOne("SELECT * FROM fraud_rules WHERE id=?", [$editId]);
}

$validTypes   = ['click_rate','low_cvr','geo_mismatch','bot_ua','conversion_time','ip_range','affiliate_age','high_cvr','datacenter_ip','duplicate_ip'];
$validActions = ['flag','block','alert','auto_case'];

require BASE_PATH . '/views/admin/fraud_center/risk_engine.php';
