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
    <a href="/admin/email?action=logs"      style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid #4F46E5;margin-bottom:-2px;color:#4F46E5">Logs & Settings</a>
</div>

<?php
$offerNew = Config::get('config', 'app.offer_new_notify') === '1';
$offerStatus = Config::get('config', 'app.offer_status_notify') === '1';
$offerLink = Config::get('config', 'app.offer_link_notify') === '1';
?>
<form method="post" action="/admin/email?action=logs" class="card mb-4" style="margin-bottom:30px">
    <div class="card-header"><span class="card-title">Offer Notification Settings</span></div>
    <div class="card-body">
        <?= Helpers::csrf() ?>
        
        <div style="margin-bottom:20px">
            <div style="display:flex;align-items:center;margin-bottom:6px">
                <strong style="font-size:15px">🎯 New Offer Notification</strong>
                <label class="switch" style="margin-left:auto;margin-bottom:0">
                    <input type="checkbox" name="offer_new_notify" value="1" <?= $offerNew ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <p style="font-size:13px;color:#64748B;margin:0">When enabled, all active affiliates automatically receive a styled email the first time an offer becomes active — whether the offer is created as active or flipped active later via edit / quick-toggle. Drafts and paused offers stay quiet, and each offer only broadcasts once. Notifications are logged in Email Logs.</p>
        </div>
        
        <div style="margin-bottom:20px;border-top:1px solid #E2E8F0;padding-top:16px">
            <div style="display:flex;align-items:center;margin-bottom:6px">
                <strong style="font-size:15px">🔄 Offer Status Change Notification</strong>
                <label class="switch" style="margin-left:auto;margin-bottom:0">
                    <input type="checkbox" name="offer_status_notify" value="1" <?= $offerStatus ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <p style="font-size:13px;color:#64748B;margin:0">When enabled, all active affiliates automatically receive a styled email when an offer's status changes (e.g. from Active to Paused). Notifications are logged in Email Logs.</p>
        </div>
        
        <div style="margin-bottom:20px;border-top:1px solid #E2E8F0;padding-top:16px">
            <div style="display:flex;align-items:center;margin-bottom:6px">
                <strong style="font-size:15px">🔗 Offer Link / Details Change Notification</strong>
                <label class="switch" style="margin-left:auto;margin-bottom:0">
                    <input type="checkbox" name="offer_link_notify" value="1" <?= $offerLink ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <p style="font-size:13px;color:#64748B;margin:0">When enabled, all active affiliates automatically receive a styled email when an offer's tracking URL, name, payout (including custom device/country payouts), cap, device targeting, or description is updated. Notifications are logged in Email Logs.</p>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:10px">Save Settings</button>
    </div>
</form>

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
            <?php foreach ($logs as $log): 
                $eventName = $log['event_type'];
                if ($eventName === 'offer_new') $eventName = 'New Offer Notification';
                elseif ($eventName === 'offer_status') $eventName = 'Offer Status Change Notification';
                elseif ($eventName === 'offer_link') $eventName = 'Offer Link / Details Change Notification';
                elseif ($eventName === 'payout_update') $eventName = 'Advanced Payout Update Notification';
                elseif ($eventName === 'blast') $eventName = 'Promotional Blast';
            ?>
            <tr>
                <td style="white-space:nowrap"><?= Helpers::e($log['sent_at']) ?></td>
                <td><?= Helpers::e($log['to_email']) ?></td>
                <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= Helpers::e($log['subject']) ?></td>
                <td><span style="font-size:13px;font-weight:500;color:#1E293B"><?= Helpers::e($eventName) ?></span></td>
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
