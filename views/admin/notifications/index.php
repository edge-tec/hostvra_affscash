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
            <?php foreach($notifications as $n): 
                $toUser = $n['user_name'] ? Helpers::e($n['user_name']) : ('All ' . ucfirst($n['target_role'] ?? ''));
                $notifJson = htmlspecialchars(json_encode([
                    'id' => $n['id'],
                    'type' => $n['type'],
                    'title' => $n['title'],
                    'to' => $toUser,
                    'message' => $n['message'],
                    'is_read' => (bool)$n['is_read'],
                    'created_at' => date('M j, Y, H:i', strtotime($n['created_at']))
                ]), ENT_QUOTES, 'UTF-8');
            ?>
            <tr>
                <td><span class="badge badge-<?= $n['type']==='success'?'success':($n['type']==='warning'?'warning':($n['type']==='danger'?'danger':'info')) ?>"><?= $n['type'] ?></span></td>
                <td class="fw-bold" style="cursor:pointer" data-notif='<?= $notifJson ?>' onclick="openNotifModal(this)" title="Click to view full message"><?= Helpers::e($n['title']) ?></td>
                <td class="text-sm"><?= $toUser ?></td>
                <td class="text-sm notif-msg-cell" style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;cursor:pointer;" data-notif='<?= $notifJson ?>' onclick="openNotifModal(this)" title="Click to view full message">
                    <span style="border-bottom:1px dashed currentColor;padding-bottom:1px;"><?= Helpers::e($n['message']) ?></span>
                </td>
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

<!-- ═══════════════════ NOTIFICATION DETAIL MODAL ═══════════════════ -->
<div id="notifDetailModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(3px);z-index:10000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--card-bg, #ffffff);border:1px solid var(--border, #e2e8f0);border-radius:16px;max-width:560px;width:100%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);overflow:hidden;animation:modalFadeIn 0.2s ease-out;">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border, #e2e8f0);display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span id="notifModalBadge" class="badge"></span>
                <h3 id="notifModalTitle" style="margin:0;font-size:16px;font-weight:700;color:var(--text, #1e293b);">Notification Details</h3>
            </div>
            <button type="button" onclick="closeNotifModal()" style="background:none;border:none;font-size:22px;color:var(--text-muted, #64748b);cursor:pointer;line-height:1;padding:4px 8px;border-radius:6px;" onmouseover="this.style.background='rgba(0,0,0,0.05)'" onmouseout="this.style.background='transparent'">&times;</button>
        </div>
        <div style="padding:20px;font-size:14px;color:var(--text, #334155);">
            <div style="display:grid;grid-template-columns:auto 1fr;gap:8px 16px;margin-bottom:16px;font-size:13px;background:var(--bg-subtle, #f8fafc);padding:12px 14px;border-radius:10px;border:1px solid var(--border, #e2e8f0);">
                <div style="color:var(--text-muted, #64748b);font-weight:600;">Recipient / To:</div>
                <div id="notifModalTo" style="font-weight:600;color:var(--text, #0f172a);"></div>
                <div style="color:var(--text-muted, #64748b);font-weight:600;">Sent Date:</div>
                <div id="notifModalSent" style="color:var(--text-muted, #64748b);"></div>
            </div>
            <div style="font-weight:600;margin-bottom:6px;color:var(--text-muted, #64748b);font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">Full Message</div>
            <div id="notifModalMessage" style="background:var(--bg, #f1f5f9);padding:14px;border-radius:10px;border:1px solid var(--border, #cbd5e1);white-space:pre-wrap;word-break:break-word;line-height:1.6;font-size:14px;max-height:320px;overflow-y:auto;color:var(--text, #0f172a);"></div>
        </div>
        <div style="padding:12px 20px;border-top:1px solid var(--border, #e2e8f0);background:var(--bg-subtle, #f8fafc);display:flex;justify-content:flex-end;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeNotifModal()">Close</button>
        </div>
    </div>
</div>

<style>
.notif-msg-cell {
    transition: background 0.15s ease;
}
.notif-msg-cell:hover {
    background: rgba(59, 130, 246, 0.08);
}
@keyframes modalFadeIn {
    from { opacity: 0; transform: scale(0.96); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<script>
function updateTarget(val) {
    document.getElementById('specificUserGroup').style.display = val === 'specific' ? 'block' : 'none';
}

function openNotifModal(elem) {
    try {
        const raw = elem.dataset.notif || elem.getAttribute('data-notif');
        if (!raw) return;
        const data = JSON.parse(raw);
        
        const modal = document.getElementById('notifDetailModal');
        const badge = document.getElementById('notifModalBadge');
        const title = document.getElementById('notifModalTitle');
        const to = document.getElementById('notifModalTo');
        const sent = document.getElementById('notifModalSent');
        const msg = document.getElementById('notifModalMessage');
        
        if (badge) {
            badge.className = 'badge badge-' + (data.type === 'success' ? 'success' : (data.type === 'warning' ? 'warning' : (data.type === 'danger' ? 'danger' : 'info')));
            badge.textContent = data.type;
        }
        if (title) title.textContent = data.title;
        if (to) to.textContent = data.to;
        if (sent) sent.textContent = data.created_at;
        if (msg) msg.textContent = data.message;
        
        if (modal) modal.style.display = 'flex';
    } catch(e) {
        console.error('Failed to open notification modal:', e);
    }
}

function closeNotifModal() {
    const modal = document.getElementById('notifDetailModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeNotifModal();
});
document.getElementById('notifDetailModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeNotifModal();
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
