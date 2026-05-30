<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Email Logs</h1><p>Last 200 email attempts</p></div>
    <div class="d-flex gap-2">
        <a href="/admin/email?action=templates" class="btn btn-secondary">Templates</a>
        <a href="/admin/email?action=blast"     class="btn btn-secondary">&#128231; Send Blast</a>
    </div>
</div>

<!-- Tab nav -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E2E8F0;padding-bottom:0">
    <a href="/admin/email?action=templates" style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:#64748B">Templates</a>
    <a href="/admin/email?action=blast"     style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:#64748B">Promotional Blast</a>
    <a href="/admin/email?action=logs"      style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid #4F46E5;margin-bottom:-2px;color:#4F46E5">Logs</a>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Sent Email Log</span></div>
    <div class="table-wrap">
        <table id="logTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Event</th>
                    <th>Status</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td style="white-space:nowrap"><?= Helpers::e($log['sent_at']) ?></td>
                <td><?= Helpers::e($log['to_email']) ?></td>
                <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= Helpers::e($log['subject']) ?></td>
                <td><code style="font-size:12px"><?= Helpers::e($log['event_type']) ?></code></td>
                <td>
                    <?php if ($log['status'] === 'sent'): ?>
                    <span class="badge badge-success">Sent</span>
                    <?php else: ?>
                    <span class="badge badge-danger">Failed</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:#EF4444;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= $log['error'] ? Helpers::e($log['error']) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:24px">No email logs yet</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function(){ $('#logTable').DataTable({order:[[0,'desc']],pageLength:50,destroy:true}); });
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
