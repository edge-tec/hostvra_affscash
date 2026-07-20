<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<?php if (isset($advertiser)): ?>
<!-- Single advertiser view -->
<div class="page-header">
    <div><h1><?= Helpers::e($advertiser['first_name'] . ' ' . $advertiser['last_name']) ?></h1><p><?= Helpers::e($advertiser['company'] ?: $advertiser['email']) ?> &bull; <?= Helpers::e($advertiser['advertiser_code']) ?></p></div>
    <div class="d-flex gap-2">
        <a href="/admin/advertisers?action=edit&id=<?= $advertiser['adv_id'] ?? $advertiser['id'] ?>" class="btn btn-sm" style="background:#F59E0B;color:#fff">Edit</a>
        <a href="/admin/advertisers?action=impersonate&user_id=<?= $advertiser['user_id'] ?>" class="btn btn-primary" onclick="return confirm('Login as this advertiser?')">&#128064; Login As</a>
        <form method="POST" action="/admin/advertisers?action=delete" style="display:inline" onsubmit="return confirm('Delete advertiser <?= htmlspecialchars(Helpers::e($advertiser['first_name'].' '.$advertiser['last_name']), ENT_QUOTES, 'UTF-8') ?>?\n\nThis will permanently delete the account. This cannot be undone.')">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="adv_id" value="<?= $advertiser['adv_id'] ?? $advertiser['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
        <a href="/admin/advertisers" class="btn btn-secondary">← Back</a>
    </div>
</div>
<div class="grid-2 mb-3">
    <div class="card">
        <div class="card-header"><span class="card-title">Account Status</span></div>
        <div class="card-body">
            <form method="POST">
                <?= Helpers::csrf() ?>
                <div class="form-group"><label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach(['active','pending','suspended','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $advertiser['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary">Update Status</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Profile</span></div>
        <div class="card-body">
            <table style="width:100%">
                <?php foreach(['email'=>'Email','company'=>'Company','phone'=>'Phone','country'=>'Country','billing_email'=>'Billing Email','credit_limit'=>'Credit Limit','balance'=>'Balance'] as $k=>$l): ?>
                <tr><td style="width:40%;color:var(--text-muted);font-size:13px;padding:4px 0"><?= $l ?></td><td style="font-size:13px;padding:4px 0"><?= Helpers::e((string)($advertiser[$k]??'—')) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">&#128172; Contact Details</span>
    </div>
    <div class="card-body">
        <?php
        $advContacts = [
            'telegram' => ['label'=>'Telegram ID', 'color'=>'#2AABEE', 'badge'=>'TG', 'link'=>'https://t.me/'],
            'skype'    => ['label'=>'Skype ID',    'color'=>'#00AFF0', 'badge'=>'SK', 'link'=>'skype:'],
            'discord'  => ['label'=>'Discord ID',  'color'=>'#5865F2', 'badge'=>'DC', 'link'=>null],
        ];
        $advHasAny = false;
        foreach ($advContacts as $field => $cfg):
            $val = trim($advertiser[$field] ?? '');
            if (!$val) continue;
            $advHasAny = true;
        ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
            <span style="background:<?= $cfg['color'] ?>;color:#fff;border-radius:4px;padding:3px 8px;font-size:11px;font-weight:700;flex-shrink:0"><?= $cfg['badge'] ?></span>
            <span style="font-size:12px;color:var(--text-muted);width:90px;flex-shrink:0"><?= $cfg['label'] ?></span>
            <?php if ($cfg['link'] && $field !== 'discord'): ?>
            <a href="<?= $cfg['link'] . ltrim(Helpers::e($val), '@') ?>" target="_blank"
               style="font-size:13px;font-weight:600;color:<?= $cfg['color'] ?>;text-decoration:none">
                <?= Helpers::e($val) ?>
            </a>
            <?php else: ?>
            <span style="font-size:13px;font-weight:600"><?= Helpers::e($val) ?></span>
            <?php endif; ?>
            <button type="button" onclick="navigator.clipboard.writeText('<?= Helpers::e($val) ?>')"
                    style="margin-left:auto;font-size:11px;padding:2px 8px;border:1px solid var(--border);border-radius:4px;background:var(--bg);cursor:pointer;color:var(--text-muted)"
                    title="Copy to clipboard">&#128203;</button>
        </div>
        <?php endforeach; ?>
        <?php if (!$advHasAny): ?>
        <div style="color:var(--text-muted);font-size:13px;text-align:center;padding:16px 0">No contact details provided.</div>
        <?php endif; ?>
    </div>
</div>
<div class="card mb-3">
    <div class="card-header"><span class="card-title">&#128184; Budget Exemption</span></div>
    <div class="card-body">
        <?php $isExempt = (int)($advertiser['budget_exempt'] ?? 0) === 1; ?>
        <p style="font-size:13px;color:#64748B;margin:0 0 14px">
            When exempt, this advertiser can create and add offers <strong>without adding any balance</strong>, even if the global budget requirement is enabled. All non-exempt advertisers must top up before creating offers.
        </p>
        <div style="display:flex;align-items:center;gap:12px">
            <span id="exempt-status-badge" style="font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;<?= $isExempt ? 'background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE' : 'background:#F1F5F9;color:#64748B;border:1px solid #E2E8F0' ?>">
                <?= $isExempt ? '✓ EXEMPT' : 'BUDGET REQUIRED' ?>
            </span>
            <button type="button" id="exempt-toggle-btn"
                    data-adv-id="<?= (int)($advertiser['adv_id'] ?? $advertiser['id']) ?>"
                    data-exempt="<?= $isExempt ? 1 : 0 ?>"
                    class="btn btn-sm"
                    style="<?= $isExempt ? 'background:#FEF2F2;color:#DC2626;border:1px solid #FCA5A5' : 'background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE' ?>">
                <?= $isExempt ? 'Remove Exemption' : 'Grant Exemption' ?>
            </button>
            <span id="exempt-toggle-msg" style="font-size:12px;color:#10B981;display:none">Saved!</span>
        </div>
        <input type="hidden" id="csrf-token-adv" value="<?= htmlspecialchars(Helpers::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <script>
        document.getElementById('exempt-toggle-btn').addEventListener('click', function() {
            var btn    = this;
            var advId  = btn.dataset.advId;
            var exempt = parseInt(btn.dataset.exempt) === 1;
            var newVal = exempt ? 0 : 1;
            var token  = document.getElementById('csrf-token-adv').value;
            btn.disabled = true;
            fetch('/admin/advertisers?action=toggle_budget_exempt', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'adv_id=' + advId + '&exempt=' + newVal + '&_token=' + encodeURIComponent(token)
            }).then(function(r){ return r.json(); }).then(function(resp) {
                if (resp.ok) {
                    btn.dataset.exempt = newVal;
                    var badge = document.getElementById('exempt-status-badge');
                    if (newVal) {
                        badge.textContent = '✓ EXEMPT';
                        badge.style.cssText = 'font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE';
                        btn.textContent = 'Remove Exemption';
                        btn.style.cssText = 'background:#FEF2F2;color:#DC2626;border:1px solid #FCA5A5';
                    } else {
                        badge.textContent = 'BUDGET REQUIRED';
                        badge.style.cssText = 'font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;background:#F1F5F9;color:#64748B;border:1px solid #E2E8F0';
                        btn.textContent = 'Grant Exemption';
                        btn.style.cssText = 'background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE';
                    }
                    var msg = document.getElementById('exempt-toggle-msg');
                    msg.style.display = 'inline';
                    setTimeout(function(){ msg.style.display = 'none'; }, 2000);
                }
            }).finally(function(){ btn.disabled = false; });
        });
        </script>
    </div>
</div>

<?php
// Build a full merged list: DB questions (active + answered deactivated) + orphan answers.
$_knownQIds2     = array_map('intval', array_column($questions ?? [], 'id'));
$_orphanAnswers2 = array_filter(
    $answers ?? [],
    fn($v, $k) => is_numeric($k) && !in_array((int)$k, $_knownQIds2),
    ARRAY_FILTER_USE_BOTH
);
?>
<!-- Registration Q&A — always visible to admin -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">&#128221; Registration Answers</span>
        <span style="font-size:11px;color:var(--text-muted)">Submitted at signup</span>
    </div>
    <div class="card-body" style="padding:0">
        <?php $_anyShown = false; ?>

        <?php foreach (($questions ?? []) as $q):
            $answer = trim((string)($answers[$q['id']] ?? ''));
            $_anyShown = true; ?>
        <div style="display:flex;align-items:flex-start;gap:0;border-bottom:1px solid var(--border)">
            <div style="width:42%;min-width:160px;padding:10px 14px;background:#F8FAFC;border-right:1px solid var(--border);font-size:12px;color:var(--text-muted);font-weight:600;line-height:1.4">
                <?= Helpers::e($q['question_text']) ?>
                <?php if (!(int)$q['is_active']): ?>
                <span style="display:inline-block;background:#FEF3C7;color:#92400E;font-size:10px;padding:1px 5px;border-radius:4px;margin-top:4px">Deactivated</span>
                <?php endif; ?>
            </div>
            <div style="flex:1;padding:10px 14px;font-size:13px;color:<?= $answer !== '' ? '#0F172A' : '#94A3B8' ?>;line-height:1.5">
                <?= $answer !== '' ? nl2br(Helpers::e($answer)) : '<em>No answer provided</em>' ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php foreach ($_orphanAnswers2 as $qId => $answer):
            $answer = trim((string)$answer);
            $_anyShown = true; ?>
        <div style="display:flex;align-items:flex-start;gap:0;border-bottom:1px solid var(--border)">
            <div style="width:42%;min-width:160px;padding:10px 14px;background:#FFF7F7;border-right:1px solid var(--border);font-size:12px;color:var(--text-muted);font-weight:600;line-height:1.4">
                Question #<?= (int)$qId ?>
                <span style="display:inline-block;background:#FEE2E2;color:#991B1B;font-size:10px;padding:1px 5px;border-radius:4px;margin-top:4px">Question Deleted</span>
            </div>
            <div style="flex:1;padding:10px 14px;font-size:13px;color:<?= $answer !== '' ? '#0F172A' : '#94A3B8' ?>;line-height:1.5">
                <?= $answer !== '' ? nl2br(Helpers::e($answer)) : '<em>No answer provided</em>' ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (!$_anyShown): ?>
        <div style="padding:20px 16px;font-size:13px;color:var(--text-muted);font-style:italic">
            This advertiser registered before the questionnaire was added — no answers on file.
        </div>
        <?php endif; ?>
    </div>
</div>

    <div class="table-wrap"><table><thead><tr><th>Name</th><th>Type</th><th>Payout</th><th>Status</th></tr></thead><tbody>
    <?php if(empty($offers)): ?>
    <tr><td colspan="4" class="text-center text-muted" style="padding:24px">No offers</td></tr>
    <?php else: ?>
    <?php foreach($offers as $o): ?>
    <tr><td><?= Helpers::e($o['name']) ?></td><td><?= $o['payout_type'] ?></td><td>$<?= number_format($o['payout_amount'],2) ?></td>
    <td><span class="badge badge-<?= $o['status']==='active'?'success':'muted' ?>"><?= $o['status'] ?></span></td></tr>
    <?php endforeach; ?><?php endif; ?>
    </tbody></table></div>
</div>

<?php else: ?>
<!-- List view -->
<div class="page-header">
    <div><h1>Advertisers</h1><p>Manage advertiser accounts</p></div>
    <a href="/admin/advertisers/create" class="btn btn-primary">+ Create Advertiser</a>
</div>
<div class="card">
    <div class="table-wrap"><table id="tbl-advertisers">
        <thead><tr><th>Advertiser</th><th>Code</th><th>Company</th><th>Status</th><th>Budget</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($advertisers as $adv): ?>
        <tr>
            <td><div class="fw-bold"><?= Helpers::e($adv['first_name'].' '.$adv['last_name']) ?></div><div class="text-muted text-sm"><?= Helpers::e($adv['email']) ?></div></td>
            <td><code style="background:#F1F5F9;padding:2px 6px;border-radius:4px;font-size:12px"><?= Helpers::e($adv['advertiser_code']) ?></code></td>
            <td><?= Helpers::e($adv['company']?:'—') ?></td>
            <td><span class="badge badge-<?= ['active'=>'success','pending'=>'warning','suspended'=>'danger','rejected'=>'muted'][$adv['status']]??'muted' ?>"><?= $adv['status'] ?></span></td>
            <td>
                <button type="button"
                        class="budget-exempt-toggle btn btn-sm"
                        data-adv-id="<?= (int)$adv['adv_id'] ?>"
                        data-exempt="<?= (int)($adv['budget_exempt'] ?? 0) ?>"
                        style="font-size:11px;padding:3px 10px;<?= (int)($adv['budget_exempt']??0) ? 'background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE' : 'background:#F1F5F9;color:#64748B;border:1px solid #E2E8F0' ?>"
                        title="<?= (int)($adv['budget_exempt']??0) ? 'Exempt — click to require budget' : 'Required — click to exempt' ?>">
                    <?= (int)($adv['budget_exempt']??0) ? '✓ Exempt' : 'Required' ?>
                </button>
            </td>
            <td class="text-sm text-muted"><?= date('M j, Y',strtotime($adv['created_at'])) ?></td>
            <td style="white-space:nowrap">
                <a href="/admin/advertisers/<?= $adv['adv_id'] ?>" class="btn btn-secondary btn-sm">View</a>
                <a href="/admin/advertisers?action=edit&id=<?= $adv['adv_id'] ?>" class="btn btn-sm" style="background:#F59E0B;color:#fff">Edit</a>
                <a href="/admin/advertisers?action=impersonate&user_id=<?= $adv['id'] ?>" class="btn btn-sm" style="background:#6366F1;color:#fff" onclick="return confirm('Login as this advertiser?')">Login As</a>
                <?php if($adv['status']==='pending'): ?>
                <form method="POST" action="/admin/advertisers/<?= $adv['adv_id'] ?>" style="display:inline">
                    <?= Helpers::csrf() ?><input type="hidden" name="status" value="active">
                    <button class="btn btn-success btn-sm">Approve</button>
                </form>
                <?php endif; ?>
                <form method="POST" action="/admin/advertisers?action=delete" style="display:inline" onsubmit="return confirm('Delete advertiser <?= htmlspecialchars(Helpers::e($adv['first_name'].' '.$adv['last_name']), ENT_QUOTES, 'UTF-8') ?>?\n\nThis will permanently delete the account. This cannot be undone.')">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="adv_id" value="<?= $adv['adv_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<!-- Hidden CSRF token for AJAX budget-exempt toggle -->
<input type="hidden" id="csrf-token" value="<?= htmlspecialchars(Helpers::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

<script>
$(function() {
    $('#tbl-advertisers').DataTable({
        destroy: true,
        stateSave: true,
        pageLength: 25,
        order: [],
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });

    // Budget Exempt quick-toggle
    $(document).on('click', '.budget-exempt-toggle', function() {
        var btn     = $(this);
        var advId   = btn.data('adv-id');
        var exempt  = parseInt(btn.data('exempt')) === 1;
        var newVal  = exempt ? 0 : 1;
        var token   = $('#csrf-token').val();

        btn.prop('disabled', true).text('…');
        $.post('/admin/advertisers?action=toggle_budget_exempt', {
            adv_id:  advId,
            exempt:  newVal,
            _token:  token
        }, function(resp) {
            if (resp.ok) {
                btn.data('exempt', newVal);
                if (newVal) {
                    btn.text('✓ Exempt').attr('title','Exempt — click to require budget')
                       .css({background:'#EEF2FF',color:'#4F46E5',border:'1px solid #C7D2FE'});
                } else {
                    btn.text('Required').attr('title','Required — click to exempt')
                       .css({background:'#F1F5F9',color:'#64748B',border:'1px solid #E2E8F0'});
                }
            } else {
                alert('Error: ' + (resp.message || 'Could not update.'));
            }
        }, 'json').fail(function() {
            alert('Request failed. Please try again.');
        }).always(function() {
            btn.prop('disabled', false);
        });
    });
});
</script>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
