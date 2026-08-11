<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
.oa-card { border:1px solid #E2E8F0; border-radius:12px; overflow:hidden; margin-bottom:14px; transition:.15s; }
.oa-card:hover { border-color:#C7D2FE; box-shadow:0 4px 16px rgba(79,70,229,.07); }
.oa-card.pending  { border-left:4px solid #F59E0B; }
.oa-card.approved { border-left:4px solid #10B981; }
.oa-card.rejected { border-left:4px solid #EF4444; }
.oa-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
           padding:14px 18px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; }
.oa-body { padding:16px 18px; }
.oa-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
@media(max-width:900px){ .oa-grid { grid-template-columns:1fr 1fr; } }
@media(max-width:600px){ .oa-grid { grid-template-columns:1fr; } }
.oa-field label { font-size:10px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:3px; }
.oa-field .val   { font-size:13px; color:#1E293B; font-weight:500; }
.oa-field .val.muted { color:#64748B; font-weight:400; }
.oa-promo { background:#FFFBEB; border:1px solid #FDE68A; border-radius:7px; padding:10px 13px;
            font-size:12px; color:#78350F; line-height:1.6; margin-top:12px; }
.oa-promo strong { display:block; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#B45309; margin-bottom:4px; }
.oa-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.oa-stat { display:flex; align-items:center; gap:5px; background:#F1F5F9; border-radius:6px;
           padding:4px 10px; font-size:12px; font-weight:600; color:#475569; }
.oa-stat svg { opacity:.5; }
.filter-bar { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
.filter-bar .form-group { margin-bottom:0; }
.status-tab-row { display:flex; gap:0; border-bottom:2px solid #E2E8F0; margin-bottom:20px; }
.status-tab { padding:9px 18px; font-size:13px; font-weight:600; color:#64748B; cursor:pointer;
              border-bottom:2px solid transparent; margin-bottom:-2px; text-decoration:none; transition:.15s; }
.status-tab:hover  { color:#334155; }
.status-tab.active { color:#4F46E5; border-bottom-color:#4F46E5; }
.empty-requests { text-align:center; padding:60px 24px; color:#94A3B8; }
.empty-requests svg { margin:0 auto 14px; display:block; opacity:.3; }
</style>

<div class="page-header">
    <div>
        <h1>Offer Approval Requests</h1>
        <p>Review and action affiliate requests to access restricted offers.</p>
    </div>
    <?php if ($pendingCount > 0): ?>
    <span style="background:#FEF3C7;color:#92400E;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700">
        ⏳ <?= $pendingCount ?> pending request<?= $pendingCount != 1 ? 's' : '' ?>
    </span>
    <?php endif; ?>
</div>

<!-- Status Tabs -->
<div class="status-tab-row">
    <?php
    $tabs = ['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All'];
    foreach ($tabs as $val => $label):
        $cnt = Database::fetchOne("SELECT COUNT(*) as c FROM affiliate_offers WHERE " . ($val==='all' ? '1=1' : "status='$val'"))['c'] ?? 0;
    ?>
    <a href="?status=<?= $val ?><?= $filterOffer ? '&offer_id='.(int)$filterOffer : '' ?><?= $filterAff ? '&aff='.urlencode($filterAff) : '' ?>"
       class="status-tab <?= $filterStatus === $val ? 'active' : '' ?>">
        <?= $label ?>
        <span style="background:<?= $val==='pending'?'#FEF3C7':($val==='approved'?'#D1FAE5':($val==='rejected'?'#FEE2E2':'#F1F5F9')) ?>;
                     color:<?= $val==='pending'?'#92400E':($val==='approved'?'#065F46':($val==='rejected'?'#991B1B':'#64748B')) ?>;
                     padding:1px 8px;border-radius:10px;font-size:11px;margin-left:4px"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body" style="padding:12px 16px">
        <form method="GET" action="/admin/offer-approvals" class="filter-bar">
            <input type="hidden" name="status" value="<?= Helpers::e($filterStatus) ?>">
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Offer</label>
                <select name="offer_id" class="form-control" style="min-width:180px">
                    <option value="">All Offers</option>
                    <?php foreach ($allOffers as $ao): ?>
                    <option value="<?= $ao['id'] ?>" <?= $filterOffer == $ao['id'] ? 'selected' : '' ?>><?= Helpers::e($ao['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-size:11px;font-weight:600;color:var(--text-muted);display:block;margin-bottom:3px">Affiliate</label>
                <input type="text" name="aff" class="form-control" placeholder="Name, email or code…" value="<?= Helpers::e($filterAff) ?>" style="min-width:180px">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if ($filterOffer || $filterAff): ?>
            <a href="?status=<?= Helpers::e($filterStatus) ?>" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Request Cards -->
<?php if (empty($requests)): ?>
<div class="card">
    <div class="empty-requests">
        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
        </svg>
        <h3 style="font-size:16px;margin-bottom:6px;color:#94A3B8">No requests found</h3>
        <p style="font-size:13px">
            <?= $filterStatus === 'pending' ? 'No pending approval requests at this time.' : 'No ' . Helpers::e($filterStatus) . ' requests match your filters.' ?>
        </p>
    </div>
</div>
<?php else: ?>

<?php foreach ($requests as $req): ?>
<div class="oa-card <?= Helpers::e($req['status']) ?>">
    <!-- Card Header -->
    <div class="oa-head">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <!-- Avatar -->
            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#7C3AED);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;flex-shrink:0">
                <?= strtoupper(substr($req['affiliate_name'], 0, 1)) ?>
            </div>
            <div>
                <div style="font-size:14px;font-weight:700;color:#0F172A"><?= Helpers::e($req['affiliate_name']) ?></div>
                <div style="font-size:12px;color:#64748B"><?= Helpers::e($req['affiliate_email']) ?> · <code style="background:#F1F5F9;padding:1px 6px;border-radius:3px;font-size:11px"><?= Helpers::e($req['affiliate_code']) ?></code></div>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <!-- Stats -->
            <div class="oa-stat">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                <?= number_format($req['total_clicks']) ?> clicks
            </div>
            <div class="oa-stat">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <?= number_format($req['total_conversions']) ?> convs
            </div>
            <!-- Status badge -->
            <?php if ($req['status'] === 'pending'): ?>
            <span class="badge badge-warning">⏳ Pending</span>
            <?php elseif ($req['status'] === 'approved'): ?>
            <span class="badge badge-success">✅ Approved</span>
            <?php else: ?>
            <span class="badge badge-danger">❌ Rejected</span>
            <?php endif; ?>
            <!-- Timestamp -->
            <span style="font-size:11px;color:#94A3B8">
                <?= $req['requested_at'] ? 'Action: ' . date('M j, Y g:ia', strtotime($req['requested_at'])) : 'Pending review' ?>
            </span>
        </div>
    </div>

    <!-- Card Body -->
    <div class="oa-body">
        <div class="oa-grid">
            <div class="oa-field">
                <label>Requested Offer</label>
                <div class="val">
                    <a href="/admin/offers/<?= $req['offer_id'] ?>" style="font-weight:700;color:#4F46E5"><?= Helpers::e($req['offer_name']) ?></a>
                </div>
                <div style="margin-top:3px">
                    <span class="badge badge-info" style="font-size:10px"><?= Helpers::e($req['payout_type']) ?></span>
                    <span style="font-size:12px;color:#10B981;font-weight:600;margin-left:4px">$<?= number_format($req['payout_amount'], 2) ?></span>
                    <?php if ($req['offer_category']): ?><span style="font-size:11px;color:#94A3B8;margin-left:4px"><?= Helpers::e($req['offer_category']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="oa-field">
                <label>Affiliate Details</label>
                <div class="val">
                    <?php if ($req['country']): ?><span>🌍 <?= Helpers::e($req['country']) ?></span><?php endif; ?>
                </div>
                <div style="font-size:11px;color:#64748B;margin-top:3px">
                    Joined <?= date('M j, Y', strtotime($req['affiliate_joined'])) ?>
                </div>
            </div>

            <div class="oa-field">
                <label>Traffic Sources</label>
                <div class="val <?= $req['traffic_sources'] ? '' : 'muted' ?>">
                    <?php
                    if ($req['traffic_sources']) {
                        $sources = json_decode($req['traffic_sources'], true);
                        if (is_array($sources)) {
                            foreach ($sources as $src) {
                                echo '<span style="display:inline-block;background:#EEF2FF;color:#4338CA;border-radius:4px;padding:1px 7px;font-size:11px;margin:1px;">' . Helpers::e($src) . '</span> ';
                            }
                        } else {
                            echo Helpers::e($req['traffic_sources']);
                        }
                    } else {
                        echo '<span class="muted">Not specified</span>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <?php if (!empty($req['duplicate_count']) && $req['duplicate_count'] > 0): ?>
        <div style="background:#FFF1F2; border:1px solid #FECDD3; border-radius:8px; padding:10px 14px; margin-top:12px; color:#9F1239; font-size:12px; font-weight:500;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                <div>
                    ⚠️ <strong>Multi-Account Alert:</strong> This exact offer was also requested by <strong><?= $req['duplicate_count'] ?> other account(s)</strong> with matching name/email/phone:
                    <span style="font-weight:700; color:#BE123C; margin-left:4px"><?= Helpers::e($req['duplicate_info']) ?></span>
                </div>
                <?php if ($req['status'] === 'pending'): ?>
                <form method="POST" action="/admin/offer-approvals" style="margin:0">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="action" value="reject_duplicates">
                    <input type="hidden" name="affiliate_id" value="<?= $req['affiliate_id'] ?>">
                    <input type="hidden" name="offer_id" value="<?= $req['offer_id'] ?>">
                    <button type="submit" class="btn btn-warning btn-sm" style="font-size:11px; padding:3px 10px;" onclick="return confirm('Reject duplicate application requests for this offer?')">
                        🚫 Reject Duplicates
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($req['promotion_description']): ?>
        <div class="oa-promo">
            <strong>📝 Affiliate's Promotion Method</strong>
            <?= Helpers::e($req['promotion_description']) ?>
        </div>
        <?php endif; ?>

        <?php if ($req['status'] === 'pending'): ?>
        <!-- Action Buttons -->
        <div class="oa-actions" style="margin-top:14px;padding-top:14px;border-top:1px solid #F1F5F9">
            <form method="POST" action="/admin/affiliates/approve-offer" style="display:inline">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="affiliate_id" value="<?= $req['affiliate_id'] ?>">
                <input type="hidden" name="offer_id"     value="<?= $req['offer_id'] ?>">
                <input type="hidden" name="from"         value="approvals">
                <button type="submit" class="btn btn-success btn-sm"
                        onclick="return confirm('Approve access to \'<?= Helpers::e(addslashes($req['offer_name'])) ?>\' for <?= Helpers::e(addslashes($req['affiliate_name'])) ?>?')">
                    ✅ Approve Access
                </button>
            </form>
            <form method="POST" action="/admin/affiliates/reject-offer" style="display:inline">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="affiliate_id" value="<?= $req['affiliate_id'] ?>">
                <input type="hidden" name="offer_id"     value="<?= $req['offer_id'] ?>">
                <input type="hidden" name="from"         value="approvals">
                <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirm('Reject this access request?')">
                    ❌ Reject
                </button>
            </form>
            <a href="/admin/affiliates/<?= $req['affiliate_id'] ?>" class="btn btn-secondary btn-sm">
                👤 View Full Profile
            </a>
            <a href="/admin/postback-test" class="btn btn-secondary btn-sm">
                🧪 Test Postback
            </a>
        </div>
        <?php elseif ($req['approved_at']): ?>
        <div style="margin-top:12px;font-size:11px;color:#94A3B8">
            <?= $req['status'] === 'approved' ? '✅ Approved' : '❌ Rejected' ?>
            on <?= date('M j, Y g:ia', strtotime($req['approved_at'])) ?>
            · <a href="/admin/affiliates/<?= $req['affiliate_id'] ?>" style="color:#4F46E5">View Profile</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
