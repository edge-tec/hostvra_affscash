<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<div class="page-header">
    <div>
        <h1>Click Report</h1>
        <p>Every click that hit your offers, with full device, geo, and conversion detail.</p>
    </div>
    <div>
        <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary">Export CSV</a>
    </div>
</div>

<!-- Filters --------------------------------------------------------------- -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;align-items:end">
            <div class="form-group mb-0"><label>From</label><input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>"></div>
            <div class="form-group mb-0"><label>To</label><input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>"></div>
            <div class="form-group mb-0"><label>Offer</label>
                <select name="offer_id" class="form-control">
                    <option value="">All offers</option>
                    <?php foreach ($myOffers as $o): ?>
                    <option value="<?= (int)$o['id'] ?>" <?= $offerId === (int)$o['id'] ? 'selected' : '' ?>><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0"><label>Affiliate</label>
                <select name="affiliate_id" class="form-control">
                    <option value="">All affiliates</option>
                    <?php foreach ($myAffiliates as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= $affId === (int)$a['id'] ? 'selected' : '' ?>>
                        <?= Helpers::e($a['name']) ?> (<?= Helpers::e($a['affiliate_code']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0"><label>Country</label>
                <select name="country" class="form-control">
                    <option value="">All</option>
                    <?php foreach ($countries as $c): ?>
                    <option value="<?= Helpers::e($c['country']) ?>" <?= $country === $c['country'] ? 'selected' : '' ?>><?= Helpers::e($c['country']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0"><label>Device</label>
                <select name="device" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['desktop','mobile','tablet','bot','unknown'] as $d): ?>
                    <option value="<?= $d ?>" <?= $device === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0"><label>Conversion</label>
                <select name="conv_status" class="form-control">
                    <option value="">All clicks</option>
                    <option value="converted"     <?= $convStatus==='converted'     ? 'selected' : '' ?>>Converted only</option>
                    <option value="not_converted" <?= $convStatus==='not_converted' ? 'selected' : '' ?>>Not converted</option>
                </select>
            </div>
            <div class="form-group mb-0"><label>Click ID</label><input type="text" name="click_id" class="form-control" value="<?= Helpers::e($clickIdQ) ?>" placeholder="prefix"></div>
            <div class="form-group mb-0"><label>Aff Click ID</label><input type="text" name="aff_click_id" class="form-control" value="<?= Helpers::e($affClickIdQ) ?>" placeholder="prefix"></div>
            <div class="form-group mb-0"><button class="btn btn-primary" style="width:100%">Apply</button></div>
        </form>
    </div>
</div>

<!-- Click table ---------------------------------------------------------- -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span style="font-weight:700;font-size:14px"><?= number_format($total) ?> click<?= $total===1?'':'s' ?> match the filters</span>
        <small class="text-muted">Page <?= $page ?> / <?= $pages ?></small>
    </div>
    <div class="table-wrap" style="overflow:auto">
        <table>
            <thead>
                <tr>
                    <th>Clicked At</th>
                    <th>Offer</th>
                    <th>Affiliate</th>
                    <th>Click ID</th>
                    <th>Aff Click ID</th>
                    <th>Aff Sub 1</th>
                    <th>Aff Sub 2</th>
                    <th>Aff Sub 3</th>
                    <th>OS</th>
                    <th>OS Ver</th>
                    <th>Browser</th>
                    <th>Br Ver</th>
                    <th>Device</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>User Agent</th>
                    <th>IP</th>
                    <th>Country</th>
                    <th>Region</th>
                    <th>City</th>
                    <th>Conversion</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($clicks)): ?>
                <tr><td colspan="21" class="text-center text-muted" style="padding:32px">No clicks match the filters.</td></tr>
            <?php else: foreach ($clicks as $c):
                $converted = !empty($c['conv_id']);
            ?>
                <tr>
                    <td style="white-space:nowrap;font-size:12px"><?= Helpers::e($c['clicked_at']) ?></td>
                    <td style="white-space:nowrap"><?= Helpers::e($c['offer_name']) ?></td>
                    <td style="white-space:nowrap">
                        <?= Helpers::e($c['aff_name'] ?? '—') ?>
                        <small class="text-muted">(<?= Helpers::e($c['affiliate_code'] ?? '') ?>)</small>
                    </td>
                    <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['click_id']) ?></td>
                    <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['sub1'] ?? '') ?></td>
                    <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['sub2'] ?? '') ?></td>
                    <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['sub3'] ?? '') ?></td>
                    <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['sub4'] ?? '') ?></td>
                    <td><?= Helpers::e($c['os'] ?: '—') ?></td>
                    <td><?= Helpers::e($c['os_version'] ?? '') ?></td>
                    <td><?= Helpers::e($c['browser'] ?: '—') ?></td>
                    <td><?= Helpers::e($c['browser_version'] ?? '') ?></td>
                    <td><?= Helpers::e($c['device_type']) ?></td>
                    <td><?= Helpers::e($c['device_brand'] ?? '') ?></td>
                    <td><?= Helpers::e($c['device_model'] ?? '') ?></td>
                    <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px"
                        title="<?= Helpers::e($c['user_agent'] ?? '') ?>"><?= Helpers::e($c['user_agent'] ?? '') ?></td>
                    <td style="font-family:monospace;font-size:11px"><?= Helpers::e($c['ip_address']) ?></td>
                    <td><?= Helpers::e($c['country']) ?></td>
                    <td><?= Helpers::e($c['region']) ?></td>
                    <td><?= Helpers::e($c['city']) ?></td>
                    <td>
                        <?php if ($converted): ?>
                            <span class="badge badge-success">CONVERT</span>
                            <?php if (!empty($c['conv_status'])): ?>
                                <small class="text-muted">(<?= Helpers::e($c['conv_status']) ?>)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-muted">NOT CONVERT</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div style="display:flex;gap:6px;padding:14px;flex-wrap:wrap;justify-content:center">
        <?php for ($i = max(1,$page-3); $i <= min($pages, $page+3); $i++):
            $qs = $_GET; $qs['page'] = $i; ?>
        <a href="?<?= http_build_query($qs) ?>" class="btn btn-sm <?= $i===$page ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
