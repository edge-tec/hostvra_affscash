<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<div class="page-header">
    <div><h1>My Offers</h1><p>Manage your CPA campaigns</p></div>
    <a href="/advertiser/offers/create" class="btn btn-primary">+ Create Offer</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Offer Name</th><th>Type</th><th>Payout</th><th>Budget</th><th>GEO</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
            <?php if(empty($offers)): ?>
            <tr><td colspan="7"><div class="empty-state"><div class="icon">&#127991;</div><h3>No offers yet</h3><p>Create your first offer to start driving traffic.</p></div></td></tr>
            <?php else: ?>
            <?php foreach($offers as $o):
                $geos       = $o['geo_targeting'] ? json_decode($o['geo_targeting'],true) : [];
                $bTotal     = $o['budget_total'] !== null && $o['budget_total'] !== '' ? (float)$o['budget_total'] : null;
                $bSpent     = (float)($o['budget_spent'] ?? 0);
                $bRemaining = $bTotal !== null ? max(0, $bTotal - $bSpent) : null;
                $bPct       = ($bTotal !== null && $bTotal > 0) ? min(100, ($bSpent / $bTotal) * 100) : 0;
                $bColor     = $bPct >= 100 ? '#DC2626' : ($bPct >= 80 ? '#F59E0B' : '#10B981');
            ?>
            <tr>
                <td><div class="fw-bold"><?= Helpers::e($o['name']) ?></div><div class="text-sm text-muted"><?= Helpers::e($o['category']?:'') ?></div></td>
                <td><span class="badge badge-info"><?= $o['payout_type'] ?></span></td>
                <td>$<?= number_format($o['payout_amount'],2) ?></td>
                <td style="min-width:140px">
                    <?php if ($bTotal !== null): ?>
                    <div style="font-size:12px;font-weight:600">$<?= number_format($bRemaining,2) ?> <span style="color:#94A3B8;font-weight:400">/ $<?= number_format($bTotal,2) ?></span></div>
                    <div style="height:5px;background:#E2E8F0;border-radius:3px;margin-top:4px;overflow:hidden">
                        <div style="height:100%;width:<?= $bPct ?>%;background:<?= $bColor ?>;transition:width .3s"></div>
                    </div>
                    <?php else: ?>
                    <span style="color:#94A3B8;font-size:12px">No budget</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm"><?= Helpers::geoList($geos, 3) ?></td>
                <td><span class="badge badge-<?= $o['status']==='active'?'success':($o['status']==='pending'?'warning':'muted') ?>"><?= $o['status'] ?></span></td>
                <td class="text-sm text-muted"><?= date('M j, Y',strtotime($o['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/advertiser_footer.php'; ?>
