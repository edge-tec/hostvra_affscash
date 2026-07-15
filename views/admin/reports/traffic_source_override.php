<?php
$title = "Traffic Source Override Logs";
require BASE_PATH . '/views/layouts/admin.php';
?>

<div class="page-header">
    <div>
        <h1>Traffic Source Override Logs</h1>
        <p class="text-muted">Clicks that had their traffic source overridden based on active rules.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/admin/reports/traffic-source-override" class="d-flex gap-3 align-items-center" style="flex-wrap:wrap">
            <div class="form-group mb-0">
                <label>Date From</label>
                <input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>">
            </div>
            <div class="form-group mb-0">
                <label>Date To</label>
                <input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>">
            </div>
            <div class="form-group mb-0">
                <label>Offer</label>
                <select name="offer_id" class="form-control">
                    <option value="">-- All Offers --</option>
                    <?php foreach ($offerList as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= $offerId==$o['id'] ? 'selected' : '' ?>>
                            <?= Helpers::e($o['name']) ?> (ID: <?= $o['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Affiliate</label>
                <select name="affiliate_id" class="form-control">
                    <option value="">-- All Affiliates --</option>
                    <?php foreach ($affList as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $affId==$a['id'] ? 'selected' : '' ?>>
                            <?= Helpers::e($a['name']) ?> (ID: <?= $a['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label>Limit</label>
                <select name="limit" class="form-control">
                    <option value="100"  <?= $limit===100 ? 'selected':'' ?>>100</option>
                    <option value="500"  <?= $limit===500 ? 'selected':'' ?>>500</option>
                    <option value="1000" <?= $limit===1000 ? 'selected':'' ?>>1000</option>
                    <option value="5000" <?= $limit===5000 ? 'selected':'' ?>>5000</option>
                </select>
            </div>
            <div style="align-self:flex-end" class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="/admin/reports/traffic-source-override" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div style="padding:15px;background:#f8f9fa;border-bottom:1px solid #eee;font-weight:600">
            Total Logs (in view): <?= number_format($totalLogs) ?>
        </div>
        <table class="table table-hover mb-0" id="tbl-tso-logs" style="width:100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Click ID</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>Original Source</th>
                    <th>Overridden Source</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr class="dt-empty-row"><td colspan="7" class="text-center text-muted">No override logs found for this period.</td></tr>
            <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="white-space:nowrap"><?= date('M j, Y H:i:s', strtotime($log['clicked_at'])) ?></td>
                    <td style="font-family:monospace;font-size:12px;white-space:nowrap"><?= Helpers::e($log['click_id'] ?: '—') ?></td>
                    <td>
                        <?php if ($log['aff_name']): ?>
                            <a href="/admin/affiliates?id=<?= $log['affiliate_id'] ?>" style="font-weight:600;text-decoration:none">
                                <?= Helpers::e($log['aff_name']) ?>
                            </a>
                            <br>
                            <span class="badge badge-secondary"><?= Helpers::e($log['affiliate_code']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['offer_name']): ?>
                            <a href="/admin/offers/<?= $log['offer_id'] ?>" style="font-weight:600;text-decoration:none">
                                <?= Helpers::e($log['offer_name']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-secondary"><?= Helpers::e($log['original_source'] ?: 'Unknown') ?></span>
                    </td>
                    <td>
                        <span class="badge badge-primary"><?= Helpers::e($log['override_source'] ?: 'Unknown') ?></span>
                    </td>
                    <td>
                        <?= Helpers::e($log['ip_address'] ?: '—') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$.fn.dataTable.ext.errMode = 'none';
$(function() {
    $('#tbl-tso-logs .dt-empty-row').remove();
    $('#tbl-tso-logs').DataTable({
        destroy: true,
        stateSave: true,
        pageLength: 50,
        order: [[0, 'desc']],
        scrollX: true,
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries', emptyTable: 'No logs found for the selected filters' }
    });
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
