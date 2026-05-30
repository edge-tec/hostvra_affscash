<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Email Notifications</h1><p>Manage transactional email templates and send promotional emails</p></div>
    <div class="d-flex gap-2">
        <a href="/admin/email?action=blast" class="btn btn-primary">&#128231; Send Blast</a>
        <a href="/admin/email?action=logs"  class="btn btn-secondary">&#128196; View Logs</a>
    </div>
</div>

<!-- Tab nav -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E2E8F0;padding-bottom:0">
    <a href="/admin/email?action=templates" style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid #4F46E5;margin-bottom:-2px;color:#4F46E5">Templates</a>
    <a href="/admin/email?action=blast"     style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:#64748B">Promotional Blast</a>
    <a href="/admin/email?action=logs"      style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:#64748B">Logs</a>
</div>

<!-- Info box -->
<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#1E40AF">
    <strong>Available placeholders:</strong>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{name}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{email}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{site_name}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{app_url}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{affiliate_code}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{invoice_number}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{total}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{due_date}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{offer_name}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{category}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{geos}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{commission}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{status_badge}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{old_status}}</code>
    <code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px">{{new_status}}</code>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Email Templates</span></div>
    <div class="table-wrap">
        <table id="emailTplTable">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Label</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($templates as $tpl): ?>
            <tr>
                <td><code style="font-size:12px;color:#6366F1"><?= Helpers::e($tpl['event_type']) ?></code></td>
                <td><?= Helpers::e($tpl['label']) ?></td>
                <td style="max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= Helpers::e($tpl['subject']) ?></td>
                <td>
                    <?php if ($tpl['is_active']): ?>
                    <span class="badge badge-success">Active</span>
                    <?php else: ?>
                    <span class="badge badge-danger">Disabled</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/admin/email?action=edit&id=<?= $tpl['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function(){ $('#emailTplTable').DataTable({pageLength:25,destroy:true}); });
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
