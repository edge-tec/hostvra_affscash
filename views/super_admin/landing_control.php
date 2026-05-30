<?php require BASE_PATH . '/views/layouts/super_admin.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-family: var(--font-heading); font-weight: 700; color: #FFFFFF; margin: 0;">🛡️ Landing Page Access Control</h2>
</div>

<form action="/super_admin/landing_control" method="POST" id="bulkForm">
    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
    <input type="hidden" name="action" value="bulk_update">
    
    <div style="display: flex; gap: 14px; margin-bottom: 20px; align-items: center; background: rgba(30, 27, 75, 0.3); border: 1px solid var(--border-glass); padding: 14px 20px; border-radius: 12px;">
        <span style="font-size: 13px; font-weight: 600; color: #C7D2FE;">Bulk Actions:</span>
        <select name="landing_enabled" class="glass-input glass-select" style="padding: 6px 12px !important; font-size: 13px !important; width: auto !important;">
            <option value="1">☑ Enable Landing Page</option>
            <option value="0">☒ Disable Landing Page (Redirect to /login)</option>
        </select>
        <button type="submit" class="btn-premium" style="padding: 8px 16px; font-size: 13px;" onclick="return confirm('Apply bulk action to selected tenants?')">Apply to Selected</button>
    </div>

    <!-- Tenants table -->
    <div class="glass-card" style="margin-bottom: 40px;">
        <div class="glass-header">
            <h3 class="glass-title">Administrator Portals Landing Scopes</h3>
        </div>
        <div style="padding: 24px; overflow-x: auto;">
            <table class="glass-table" id="accessTable">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll" style="cursor: pointer;"></th>
                        <th>Tenant Workspace</th>
                        <th>Admin Owner</th>
                        <th>Status</th>
                        <th>Landing Page Access</th>
                        <th>Toggle Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tenants as $t): ?>
                        <tr>
                            <td><input type="checkbox" name="tenant_ids[]" value="<?= $t['id'] ?>" class="tenant-check" style="cursor: pointer;"></td>
                            <td>
                                <strong><?= Helpers::e($t['company_name']) ?></strong>
                                <br><span style="font-size: 11px; color: var(--text-muted);">Tenant ID: #<?= $t['id'] ?></span>
                            </td>
                            <td>
                                <strong><?= Helpers::e($t['first_name'] . ' ' . $t['last_name']) ?></strong>
                                <br><span style="font-size: 12px; color: #818CF8;"><?= Helpers::e($t['admin_email']) ?></span>
                            </td>
                            <td>
                                <span style="padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; background: <?= $t['status'] === 'active' || $t['status'] === 'trial' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $t['status'] === 'active' || $t['status'] === 'trial' ? '#34D399' : '#FCA5A5' ?>;">
                                    <?= strtoupper($t['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; background: <?= !empty($t['landing_enabled']) ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= !empty($t['landing_enabled']) ? '#34D399' : '#FCA5A5' ?>;">
                                    <?= !empty($t['landing_enabled']) ? 'ENABLED (FULL WEBSITE)' : 'DISABLED (LOGIN ONLY)' ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn-premium" style="padding: 6px 12px; font-size: 12px; background: <?= !empty($t['landing_enabled']) ? 'linear-gradient(135deg, #EF4444 0%, #DC2626 100%)' : 'linear-gradient(135deg, #10B981 0%, #059669 100%)' ?>; box-shadow: none;" onclick="toggleAccess(<?= $t['id'] ?>, <?= !empty($t['landing_enabled']) ? 0 : 1 ?>)">
                                    <?= !empty($t['landing_enabled']) ? 'Disable Access' : 'Enable Access' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<!-- Single Toggle Form (Hidden) -->
<form id="toggleForm" action="/super_admin/landing_control" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
    <input type="hidden" name="action" value="toggle">
    <input type="hidden" name="tenant_id" id="toggleTenantId">
    <input type="hidden" name="landing_enabled" id="toggleStatus">
</form>

<!-- Audit Logs -->
<div class="glass-card">
    <div class="glass-header">
        <h3 class="glass-title">🛡️ Landing Page Policy Audit Logs</h3>
    </div>
    <div style="padding: 24px; overflow-x: auto;">
        <table class="glass-table" id="auditTable">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditLogs as $log): ?>
                    <?php
                    $actType = $log['action'] ?? 'activity';
                    $details = $log['new_data'] ?? '';
                    if ($details && str_starts_with($details, '{')) {
                        $decoded = json_decode($details, true);
                        $details = $decoded['note'] ?? $decoded['description'] ?? $details;
                    }
                    ?>
                    <tr>
                        <td><strong>#<?= $log['id'] ?></strong></td>
                        <td><strong style="color: #818CF8; font-size: 13.5px;"><?= Helpers::e($log['user_email'] ?? 'System Process') ?></strong></td>
                        <td><span style="font-weight: 700; color: #F8FAFC; text-transform: uppercase;"><?= Helpers::e($actType) ?></span></td>
                        <td><span style="font-size: 13px; color: var(--text-muted);"><?= Helpers::e($details) ?></span></td>
                        <td><?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#accessTable').DataTable({
        "order": [[ 1, "asc" ]],
        "pageLength": 10,
        "columnDefs": [
            { "orderable": false, "targets": [0, 5] }
        ],
        "language": {
            "search": "Filter Workspace:"
        }
    });

    $('#auditTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 10,
        "language": {
            "search": "Filter Logs:"
        }
    });

    // Select all logic
    $('#selectAll').click(function() {
        $('.tenant-check').prop('checked', this.checked);
    });
});

function toggleAccess(tenantId, targetStatus) {
    var actionText = targetStatus ? 'enable marketing website access' : 'disable marketing website (redirect visitors to login page)';
    if (confirm('Are you sure you want to ' + actionText + ' for tenant #' + tenantId + '?')) {
        document.getElementById('toggleTenantId').value = tenantId;
        document.getElementById('toggleStatus').value = targetStatus;
        document.getElementById('toggleForm').submit();
    }
}
</script>

<?php require BASE_PATH . '/views/layouts/super_admin_footer.php'; ?>
