<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Affiliate Managers</h1><p>Manage affiliate manager accounts and permissions</p></div>
    <a href="/admin/affiliate-managers/create" class="btn btn-primary">+ Create Manager</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table id="tbl-managers">
            <thead><tr><th>Manager</th><th>Permissions</th><th>Affiliates</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($managers as $m):
                $perms = json_decode($m['permissions'] ?? '[]', true) ?: [];
            ?>
            <tr>
                <td><div class="fw-bold"><?= Helpers::e($m['first_name'].' '.$m['last_name']) ?></div><div class="text-sm text-muted"><?= Helpers::e($m['email']) ?></div></td>
                <td>
                    <?php foreach ($perms as $p): ?>
                    <span class="badge badge-info" style="margin:1px 2px;font-size:10px"><?= str_replace('_',' ',$p) ?></span>
                    <?php endforeach; ?>
                    <?php if (empty($perms)): ?><span class="text-muted text-sm">None</span><?php endif; ?>
                </td>
                <td><?= $m['aff_count'] ?> affiliates</td>
                <td><span class="badge badge-<?= $m['status']==='active'?'success':'danger' ?>"><?= $m['status'] ?></span></td>
                <td class="text-sm text-muted"><?= date('M j, Y',strtotime($m['created_at'])) ?></td>
                <td style="white-space:nowrap">
                    <a href="/admin/affiliate-managers/<?= $m['mgr_id'] ?>" class="btn btn-secondary btn-sm">Manage</a>
                    <a href="/admin/affiliate-managers?action=edit&id=<?= $m['mgr_id'] ?>" class="btn btn-sm" style="background:#F59E0B;color:#fff">Edit</a>
                    <a href="/admin/affiliate-managers?action=impersonate&user_id=<?= $m['id'] ?>" class="btn btn-sm" style="background:#6366F1;color:#fff" onclick="return confirm('Login as this manager?')">Login As</a>
                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $m['mgr_id'] ?>, '<?= Helpers::e($m['first_name'].' '.$m['last_name']) ?>')">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete confirmation form -->
<form id="delete-mgr-form" method="POST" action="/admin/affiliate-managers?action=delete" style="display:none">
    <?= Helpers::csrf() ?>
    <input type="hidden" name="mgr_id" id="delete-mgr-id">
</form>

<script>
$(function() {
    $('#tbl-managers').DataTable({ destroy:true, stateSave:true, pageLength:25, order:[], language:{search:'Search:',lengthMenu:'Show _MENU_ entries'} });
});
function confirmDelete(id, name) {
    if (!confirm('Delete affiliate manager "' + name + '"?\n\nThis will permanently delete the account and unassign all their affiliates. This cannot be undone.')) return;
    document.getElementById('delete-mgr-id').value = id;
    document.getElementById('delete-mgr-form').submit();
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
