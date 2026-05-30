<?php require BASE_PATH . '/views/layouts/super_admin.php'; ?>

<h2 style="font-family: var(--font-heading); font-weight: 700; color: #FFFFFF; margin-bottom: 30px;">🛡️ Security Operations &amp; Audit Logs</h2>

<div style="display: grid; grid-template-columns: 1fr; gap: 30px; margin-bottom: 30px;">
    <!-- Global Audit Trail Logs -->
    <div class="glass-card">
        <div class="glass-header">
            <h3 class="glass-title">System-wide Security Audit Trail</h3>
        </div>
        <div style="padding: 24px; overflow-x: auto;">
            <table class="glass-table" id="auditTrailTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Email</th>
                        <th>System Role</th>
                        <th>Action Performed</th>
                        <th>Context Description</th>
                        <th>Logged IP</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activityLogs as $a): ?>
                        <tr>
                            <td><strong>#<?= $a['id'] ?></strong></td>
                            <td><strong style="color: #FFFFFF; font-size: 13.5px;"><?= Helpers::e($a['user_email'] ?? 'System Process') ?></strong></td>
                            <td>
                                <span style="padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: <?= ($a['user_role'] ?? '') === 'super_admin' ? 'rgba(139, 92, 246, 0.15)' : 'rgba(99, 102, 241, 0.15)' ?>; color: <?= ($a['user_role'] ?? '') === 'super_admin' ? '#A78BFA' : '#818CF8' ?>;">
                                    <?= strtoupper(str_replace('_', ' ', $a['user_role'] ?? 'cron')) ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 12px; font-weight: 700; color: #E2E8F0; text-transform: uppercase;"><?= Helpers::e($a['action'] ?? '') ?></span>
                            </td>
                            <?php
                            $details = $a['new_data'] ?? '';
                            if ($details && str_starts_with($details, '{')) {
                                $decoded = json_decode($details, true);
                                $details = $decoded['note'] ?? $decoded['description'] ?? $details;
                            }
                            ?>
                            <td><span style="font-size: 12.5px; color: var(--text-muted);"><?= Helpers::e($details) ?></span></td>
                            <td><span style="font-family: monospace; font-size: 12px; color: #A5B4FC;"><?= Helpers::e($a['ip_address'] ?? '') ?></span></td>
                            <td><?= date('M d, Y H:i:s', strtotime($a['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active IP Login Bans -->
    <div class="glass-card">
        <div class="glass-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="glass-title">Active IP Blacklist</h3>
        </div>
        <div style="padding: 24px; overflow-x: auto;">
            <table class="glass-table" id="ipBansTable">
                <thead>
                    <tr>
                        <th>Ban ID</th>
                        <th>IP Address / Scope</th>
                        <th>Reason for Block</th>
                        <th>Enforced By</th>
                        <th>Active</th>
                        <th>Enforced Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ipBans as $ban): ?>
                        <tr>
                            <td><strong>#<?= $ban['id'] ?></strong></td>
                            <td><span style="font-family: monospace; font-size: 13.5px; color: #EF4444; font-weight: 700;"><?= Helpers::e($ban['ip_address']) ?></span></td>
                            <td><span style="font-size: 12.5px; color: var(--text-muted);"><?= Helpers::e($ban['reason']) ?></span></td>
                            <td><strong>#<?= $ban['created_by'] ?? '1' ?></strong></td>
                            <td>
                                <span style="padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: <?= $ban['is_active'] ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)' ?>; color: <?= $ban['is_active'] ? '#FCA5A5' : '#34D399' ?>;">
                                    <?= $ban['is_active'] ? 'BLOCKED' : 'INACTIVE' ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y H:i:s', strtotime($ban['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#auditTrailTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 25,
        "language": {
            "search": "Filter Activity:"
        }
    });

    $('#ipBansTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 10,
        "language": {
            "search": "Filter Blocked IP:"
        }
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/super_admin_footer.php'; ?>
