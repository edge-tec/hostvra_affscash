<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Notifications</h1><p>Send and manage in-app notifications to users</p></div>
</div>

<div class="grid-2 mb-3">
    <!-- Send Notification -->
    <div class="card">
        <div class="card-header"><span class="card-title">Send Notification</span></div>
        <div class="card-body">
            <form method="POST" id="notifForm">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="send">
                <div class="form-group">
                    <label>Notification Type</label>
                    <select name="type" class="form-control">
                        <option value="info">Info</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="danger">Danger / Alert</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Audience</label>
                    <select name="target_role" class="form-control" id="targetRole" onchange="updateTarget(this.value)">
                        <option value="all">All Users</option>
                        <option value="affiliate">All Affiliates</option>
                        <option value="advertiser">All Advertisers</option>
                        <option value="specific">Specific User</option>
                    </select>
                </div>
                <div class="form-group" id="specificUserGroup" style="display:none">
                    <label>Specific User</label>
                    <select name="user_id" class="form-control">
                        <option value="">Select user...</option>
                        <optgroup label="Affiliates">
                            <?php foreach($affiliates as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= Helpers::e($u['label']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Advertisers">
                            <?php foreach($advertisers as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= Helpers::e($u['label']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="Notification title...">
                </div>
                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" class="form-control" rows="4" required placeholder="Notification message..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Notification</button>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="card">
        <div class="card-header"><span class="card-title">Notification Stats</span></div>
        <div class="card-body">
            <?php
            $total  = Database::count('notifications','1');
            $unread = Database::count('notifications','is_read=0');
            ?>
            <div class="stats-grid" style="grid-template-columns:1fr 1fr">
                <div class="stat-card">
                    <div class="stat-label">Total Sent</div>
                    <div class="stat-value"><?= $total ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Unread</div>
                    <div class="stat-value"><?= $unread ?></div>
                </div>
            </div>
            <div class="alert alert-info mt-2">
                Notifications appear in the top bar bell icon for each user based on their role or direct targeting.
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Recent Notifications</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Type</th><th>Title</th><th>To</th><th>Message</th><th>Read</th><th>Sent</th><th>Del</th></tr></thead>
            <tbody>
            <?php if(empty($notifications)): ?>
            <tr><td colspan="7"><div class="empty-state"><div class="icon">&#128276;</div><h3>No notifications yet</h3></div></td></tr>
            <?php else: ?>
            <?php foreach($notifications as $n): ?>
            <tr>
                <td><span class="badge badge-<?= $n['type']==='success'?'success':($n['type']==='warning'?'warning':($n['type']==='danger'?'danger':'info')) ?>"><?= $n['type'] ?></span></td>
                <td class="fw-bold"><?= Helpers::e($n['title']) ?></td>
                <td class="text-sm"><?= $n['user_name'] ? Helpers::e($n['user_name']) : ('All ' . ucfirst($n['target_role'] ?? '')) ?></td>
                <td class="text-sm" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= Helpers::e($n['message']) ?></td>
                <td><?= $n['is_read'] ? '&#9989;' : '&#9900;' ?></td>
                <td class="text-sm text-muted"><?= date('M j, H:i', strtotime($n['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $n['id'] ?>">
                        <button class="btn btn-danger btn-sm" data-confirm="Delete notification?">&#128465;</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateTarget(val) {
    document.getElementById('specificUserGroup').style.display = val === 'specific' ? 'block' : 'none';
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
