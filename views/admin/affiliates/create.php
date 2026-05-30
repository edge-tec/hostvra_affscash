<?php
$pageTitle = 'Create Affiliate';
require BASE_PATH . '/views/layouts/admin.php';
?>

<div class="page-header">
    <div><h1>Create Affiliate Account</h1><p>Manually create a new affiliate account</p></div>
    <a href="/admin/affiliates" class="btn btn-secondary">← Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach($errors as $e): ?><div>&#8226; <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:600px">
    <div class="card-header"><span class="card-title">Account Details</span></div>
    <div class="card-body">
        <form method="POST" action="/admin/affiliates/create">
            <?= Helpers::csrf() ?>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" class="form-control" required value="<?= Helpers::e($_POST['first_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required value="<?= Helpers::e($_POST['last_name'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" class="form-control" required value="<?= Helpers::e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" class="form-control" required placeholder="Minimum 8 characters">
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" name="company" class="form-control" value="<?= Helpers::e($_POST['company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= Helpers::e($_POST['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Country</label>
                    <select name="country" class="form-control">
                        <?php
                        $countries = ['AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AR'=>'Argentina','AU'=>'Australia','AT'=>'Austria','BD'=>'Bangladesh','BE'=>'Belgium','BR'=>'Brazil','CA'=>'Canada','CL'=>'Chile','CN'=>'China','CO'=>'Colombia','HR'=>'Croatia','CZ'=>'Czech Republic','DK'=>'Denmark','EG'=>'Egypt','FI'=>'Finland','FR'=>'France','DE'=>'Germany','GH'=>'Ghana','GR'=>'Greece','HK'=>'Hong Kong','HU'=>'Hungary','IN'=>'India','ID'=>'Indonesia','IE'=>'Ireland','IL'=>'Israel','IT'=>'Italy','JP'=>'Japan','KE'=>'Kenya','KR'=>'South Korea','MY'=>'Malaysia','MX'=>'Mexico','MA'=>'Morocco','NL'=>'Netherlands','NZ'=>'New Zealand','NG'=>'Nigeria','NO'=>'Norway','PK'=>'Pakistan','PE'=>'Peru','PH'=>'Philippines','PL'=>'Poland','PT'=>'Portugal','RO'=>'Romania','RU'=>'Russia','SA'=>'Saudi Arabia','SG'=>'Singapore','ZA'=>'South Africa','ES'=>'Spain','SE'=>'Sweden','CH'=>'Switzerland','TW'=>'Taiwan','TH'=>'Thailand','TN'=>'Tunisia','TR'=>'Turkey','UA'=>'Ukraine','AE'=>'United Arab Emirates','GB'=>'United Kingdom','US'=>'United States','VN'=>'Vietnam'];
                        $sel = strtoupper($_POST['country'] ?? 'US');
                        foreach ($countries as $code => $name):
                        ?>
                        <option value="<?= $code ?>" <?= $sel === $code ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Account Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="pending" <?= ($_POST['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="suspended" <?= ($_POST['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Assign to Affiliate Manager</label>
                <select name="manager_id" class="form-control">
                    <option value="">— No Manager —</option>
                    <?php foreach ($managers as $mgr): ?>
                    <option value="<?= $mgr['id'] ?>" <?= ($_POST['manager_id'] ?? '') == $mgr['id'] ? 'selected' : '' ?>><?= Helpers::e($mgr['label']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">The assigned manager will be able to see and manage this affiliate.</div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Create Affiliate Account</button>
        </form>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
