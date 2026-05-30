<?php
Auth::check('admin');
$pageTitle = 'Rejection Reasons';

RejectionHelper::ensureSchema();

$message = '';
$error   = '';

if (Helpers::isPost()) {
    Auth::verifyCsrf(Helpers::postRaw('_token'));
    $action = Helpers::post('action');

    if ($action === 'add') {
        $label = trim((string)Helpers::postRaw('label'));
        if ($label === '') {
            $error = 'Reason label is required.';
        } else {
            try {
                $existing = Database::fetchOne("SELECT id FROM rejection_reasons WHERE label=?", [$label]);
                if ($existing) {
                    $error = 'A reason with this label already exists.';
                } else {
                    $maxSort = (int)(Database::fetchOne("SELECT COALESCE(MAX(sort_order),0) AS m FROM rejection_reasons")['m'] ?? 0);
                    Database::insert('rejection_reasons', [
                        'label'      => mb_substr($label, 0, 255),
                        'is_default' => 0,
                        'is_active'  => 1,
                        'sort_order' => $maxSort + 1,
                    ]);
                    $message = 'Reason added.';
                }
            } catch (\Throwable $e) { $error = 'Add failed: ' . $e->getMessage(); }
        }

    } elseif ($action === 'edit') {
        $id    = (int)Helpers::post('id');
        $label = trim((string)Helpers::postRaw('label'));
        $isActive = Helpers::post('is_active') ? 1 : 0;
        if ($id <= 0 || $label === '') {
            $error = 'Missing id or label.';
        } else {
            try {
                $other = Database::fetchOne("SELECT id FROM rejection_reasons WHERE label=? AND id<>?", [$label, $id]);
                if ($other) {
                    $error = 'Another reason already uses this label.';
                } else {
                    Database::update('rejection_reasons', [
                        'label'      => mb_substr($label, 0, 255),
                        'is_active'  => $isActive,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ], 'id=?', [$id]);
                    $message = 'Reason updated.';
                }
            } catch (\Throwable $e) { $error = 'Update failed: ' . $e->getMessage(); }
        }

    } elseif ($action === 'delete') {
        $id = (int)Helpers::post('id');
        if ($id <= 0) {
            $error = 'Missing id.';
        } else {
            try {
                // Hard-delete custom reasons; soft-deactivate defaults so existing data
                // referencing them by label still renders the label correctly elsewhere.
                $row = Database::fetchOne("SELECT is_default FROM rejection_reasons WHERE id=?", [$id]);
                if ($row && (int)$row['is_default'] === 1) {
                    Database::update('rejection_reasons', ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
                    $message = 'Default reason deactivated (kept for historical data).';
                } else {
                    Database::query("DELETE FROM rejection_reasons WHERE id=?", [$id]);
                    $message = 'Reason removed.';
                }
            } catch (\Throwable $e) { $error = 'Delete failed: ' . $e->getMessage(); }
        }

    } elseif ($action === 'toggle') {
        $id = (int)Helpers::post('id');
        try {
            $row = Database::fetchOne("SELECT is_active FROM rejection_reasons WHERE id=?", [$id]);
            if ($row) {
                $new = (int)$row['is_active'] === 1 ? 0 : 1;
                Database::update('rejection_reasons', ['is_active' => $new, 'updated_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
                $message = 'Reason ' . ($new ? 'enabled' : 'disabled') . '.';
            }
        } catch (\Throwable $e) { $error = 'Toggle failed.'; }
    }

    Helpers::flash($error ? 'error' : 'success', $error ?: $message);
    Helpers::redirect('/admin/rejection-reasons');
}

$reasons = RejectionHelper::getAllReasons();

require BASE_PATH . '/views/admin/rejection_reasons/index.php';
