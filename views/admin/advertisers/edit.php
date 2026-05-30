<?php
$pageTitle = 'Edit Advertiser';
require BASE_PATH . '/views/layouts/admin.php';
$countries = ['AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AR'=>'Argentina','AU'=>'Australia','AT'=>'Austria','BD'=>'Bangladesh','BE'=>'Belgium','BR'=>'Brazil','CA'=>'Canada','CL'=>'Chile','CN'=>'China','CO'=>'Colombia','HR'=>'Croatia','CZ'=>'Czech Republic','DK'=>'Denmark','EG'=>'Egypt','FI'=>'Finland','FR'=>'France','DE'=>'Germany','GH'=>'Ghana','GR'=>'Greece','HK'=>'Hong Kong','HU'=>'Hungary','IN'=>'India','ID'=>'Indonesia','IE'=>'Ireland','IL'=>'Israel','IT'=>'Italy','JP'=>'Japan','KE'=>'Kenya','KR'=>'South Korea','MY'=>'Malaysia','MX'=>'Mexico','MA'=>'Morocco','NL'=>'Netherlands','NZ'=>'New Zealand','NG'=>'Nigeria','NO'=>'Norway','PK'=>'Pakistan','PE'=>'Peru','PH'=>'Philippines','PL'=>'Poland','PT'=>'Portugal','RO'=>'Romania','RU'=>'Russia','SA'=>'Saudi Arabia','SG'=>'Singapore','ZA'=>'South Africa','ES'=>'Spain','SE'=>'Sweden','CH'=>'Switzerland','TW'=>'Taiwan','TH'=>'Thailand','TN'=>'Tunisia','TR'=>'Turkey','UA'=>'Ukraine','AE'=>'United Arab Emirates','GB'=>'United Kingdom','US'=>'United States','VN'=>'Vietnam'];
?>

<div class="page-header">
    <div><h1>Edit Advertiser</h1><p><?= Helpers::e($advertiser['first_name'].' '.$advertiser['last_name']) ?> &bull; <?= Helpers::e($advertiser['advertiser_code']) ?></p></div>
    <a href="/admin/advertisers/<?= $advertiser['id'] ?>" class="btn btn-secondary">← Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach($errors as $e): ?><div>• <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$_knownQIds     = array_map('intval', array_column($questions ?? [], 'id'));
$_orphanAnswers = array_filter(
    $answers ?? [],
    fn($v, $k) => is_numeric($k) && !in_array((int)$k, $_knownQIds),
    ARRAY_FILTER_USE_BOTH
);
?>
<!-- Registration Q&A — always visible to admin -->
<div class="card mb-3" style="max-width:600px">
    <div class="card-header">
        <span class="card-title">&#128221; Registration Answers</span>
        <span style="font-size:11px;color:var(--text-muted)">Submitted at signup</span>
    </div>
    <div class="card-body" style="padding:0">
        <?php $_anyShown = false; ?>

        <?php foreach (($questions ?? []) as $q):
            $answer = trim((string)($answers[$q['id']] ?? ''));
            $_anyShown = true; ?>
        <div style="display:flex;align-items:flex-start;border-bottom:1px solid var(--border)">
            <div style="width:42%;min-width:140px;padding:10px 14px;background:#F8FAFC;border-right:1px solid var(--border);font-size:12px;color:var(--text-muted);font-weight:600;line-height:1.4">
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

        <?php foreach ($_orphanAnswers as $qId => $answer):
            $answer = trim((string)$answer);
            $_anyShown = true; ?>
        <div style="display:flex;align-items:flex-start;border-bottom:1px solid var(--border)">
            <div style="width:42%;min-width:140px;padding:10px 14px;background:#FFF7F7;border-right:1px solid var(--border);font-size:12px;color:var(--text-muted);font-weight:600;line-height:1.4">
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

<div class="card" style="max-width:600px">
    <div class="card-header"><span class="card-title">Account Details</span></div>
    <div class="card-body">
        <form method="POST" action="/admin/advertisers?action=edit&id=<?= $advertiser['id'] ?>">
            <?= Helpers::csrf() ?>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" class="form-control" required value="<?= Helpers::e($_POST['first_name'] ?? $advertiser['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required value="<?= Helpers::e($_POST['last_name'] ?? $advertiser['last_name']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" class="form-control" required value="<?= Helpers::e($_POST['email'] ?? $advertiser['email']) ?>">
            </div>
            <div class="form-group">
                <label>New Password <span class="text-muted text-sm">(leave blank to keep current)</span></label>
                <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters">
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" name="company" class="form-control" value="<?= Helpers::e($_POST['company'] ?? $advertiser['company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= Helpers::e($_POST['phone'] ?? $advertiser['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Country</label>
                    <select name="country" class="form-control">
                        <?php $sel = strtoupper($_POST['country'] ?? $advertiser['country'] ?? 'US');
                        foreach ($countries as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $sel === $code ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Account Status</label>
                    <select name="status" class="form-control">
                        <?php $curStatus = $_POST['status'] ?? $advertiser['status'];
                        foreach(['active','pending','suspended','rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $curStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Billing Email</label>
                    <input type="email" name="billing_email" class="form-control" value="<?= Helpers::e($_POST['billing_email'] ?? $advertiser['billing_email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Credit Limit ($)</label>
                    <input type="number" step="0.01" name="credit_limit" class="form-control" value="<?= Helpers::e($_POST['credit_limit'] ?? $advertiser['credit_limit'] ?? '0') ?>" min="0">
                </div>
                <div class="form-group" style="padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px">
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;margin:0">
                        <input type="checkbox" name="budget_exempt" value="1"
                               <?= (int)($advertiser['budget_exempt'] ?? 0) === 1 ? 'checked' : '' ?>
                               style="margin-top:3px;accent-color:#4F46E5;width:16px;height:16px;flex-shrink:0">
                        <div>
                            <div style="font-weight:600;font-size:14px;color:#1E293B">Exempt from Budget Requirement</div>
                            <div style="font-size:12px;color:#64748B;margin-top:3px">
                                When checked, this advertiser can create offers without adding any balance, even when the global budget requirement is enabled.
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label style="font-size:13px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;display:block">Contact Details</label>
                <div class="form-row cols-2" style="grid-template-columns:1fr 1fr 1fr;gap:12px">
                    <div class="form-group mb-0">
                        <label style="display:flex;align-items:center;gap:6px">
                            <span style="background:#2AABEE;color:#fff;border-radius:4px;padding:2px 6px;font-size:11px;font-weight:700">TG</span> Telegram ID
                        </label>
                        <input type="text" name="telegram" class="form-control" placeholder="@username"
                               value="<?= Helpers::e($_POST['telegram'] ?? $advertiser['telegram'] ?? '') ?>">
                    </div>
                    <div class="form-group mb-0">
                        <label style="display:flex;align-items:center;gap:6px">
                            <span style="background:#00AFF0;color:#fff;border-radius:4px;padding:2px 6px;font-size:11px;font-weight:700">SK</span> Skype ID
                        </label>
                        <input type="text" name="skype" class="form-control" placeholder="live:username"
                               value="<?= Helpers::e($_POST['skype'] ?? $advertiser['skype'] ?? '') ?>">
                    </div>
                    <div class="form-group mb-0">
                        <label style="display:flex;align-items:center;gap:6px">
                            <span style="background:#5865F2;color:#fff;border-radius:4px;padding:2px 6px;font-size:11px;font-weight:700">DC</span> Discord ID
                        </label>
                        <input type="text" name="discord" class="form-control" placeholder="username"
                               value="<?= Helpers::e($_POST['discord'] ?? $advertiser['discord'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="/admin/advertisers/<?= $advertiser['id'] ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
