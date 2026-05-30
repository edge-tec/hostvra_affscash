<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>⭐ Landing Reviews</h1>
        <p>Manage testimonials — approve public submissions, add or edit curated reviews</p>
    </div>
    <a href="/admin/landing/reviews/create" class="btn btn-primary">+ Add Review</a>
</div>

<!-- Status tabs -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
    <a href="/admin/landing/reviews" class="btn btn-sm <?= ($statusFilter??'')==''?'btn-primary':'btn-secondary' ?>">
        All <span style="background:rgba(0,0,0,.15);border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px"><?= $pendingCount+$activeCount+$inactiveCount ?></span>
    </a>
    <a href="/admin/landing/reviews?status_filter=pending" class="btn btn-sm <?= ($statusFilter??'')==='pending'?'btn-primary':'btn-secondary' ?>" style="<?= $pendingCount?'border-color:#F59E0B;':'' ?>">
        ⏳ Pending
        <?php if ($pendingCount): ?>
        <span style="background:#F59E0B;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px"><?= $pendingCount ?></span>
        <?php endif; ?>
    </a>
    <a href="/admin/landing/reviews?status_filter=active" class="btn btn-sm <?= ($statusFilter??'')==='active'?'btn-primary':'btn-secondary' ?>">
        ✅ Approved <span style="background:rgba(0,0,0,.15);border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px"><?= $activeCount ?></span>
    </a>
    <a href="/admin/landing/reviews?status_filter=inactive" class="btn btn-sm <?= ($statusFilter??'')==='inactive'?'btn-primary':'btn-secondary' ?>">
        ✕ Rejected <span style="background:rgba(0,0,0,.15);border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px"><?= $inactiveCount ?></span>
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" action="/admin/landing/reviews" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <?php if ($statusFilter): ?><input type="hidden" name="status_filter" value="<?= Helpers::e($statusFilter) ?>"><?php endif; ?>
            <div style="flex:1;min-width:180px">
                <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Name, review or country…"
                       value="<?= Helpers::e($q ?? '') ?>" style="height:36px">
            </div>
            <div style="min-width:120px">
                <label style="font-size:12px;font-weight:600;color:#64748B;display:block;margin-bottom:4px">Rating</label>
                <select name="rating" class="form-control" style="height:36px">
                    <option value="">All Ratings</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= ($ratingFilter??0)==$i?'selected':'' ?>><?= str_repeat('★',$i).str_repeat('☆',5-$i) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary" style="height:36px">Filter</button>
                <a href="/admin/landing/reviews" class="btn btn-secondary" style="height:36px">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($reviews)): ?>
<div class="card">
    <div style="text-align:center;padding:60px 24px;color:#94A3B8">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 14px;display:block;opacity:.3">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
        <h3 style="font-size:16px;color:#94A3B8;margin-bottom:6px">No reviews found</h3>
        <p style="font-size:13px">
            <?= ($statusFilter??'')==='pending' ? 'No pending submissions yet. Public reviews will appear here after visitors submit them.' : 'Try a different filter or add a review manually.' ?>
        </p>
        <a href="/admin/landing/reviews/create" class="btn btn-primary" style="margin-top:16px">+ Add Manual Review</a>
    </div>
</div>
<?php else: ?>

<?php if ($pendingCount && ($statusFilter??'') !== 'pending'): ?>
<div style="background:#FEF3C7;border:1px solid #F59E0B;border-radius:10px;padding:12px 18px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px">
    <span style="font-size:14px;color:#92400E">⏳ <strong><?= $pendingCount ?> pending review<?= $pendingCount!==1?'s':'' ?></strong> awaiting your approval</span>
    <a href="/admin/landing/reviews?status_filter=pending" class="btn btn-sm" style="background:#F59E0B;color:#fff;border-color:#F59E0B">Review Now →</a>
</div>
<?php endif; ?>

<div class="card">
    <div style="overflow-x:auto">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:50px">Order</th>
                    <th style="width:56px">Avatar</th>
                    <th>Reviewer</th>
                    <th style="width:100px">Rating</th>
                    <th>Review</th>
                    <th style="width:80px">Source</th>
                    <th style="width:90px">Featured</th>
                    <th style="width:110px">Status</th>
                    <th style="width:180px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reviews as $r): ?>
            <tr style="<?= $r['status']==='pending'?'background:#FFFBEB;':'' ?>">
                <td style="color:#94A3B8;font-size:13px;text-align:center"><?= (int)$r['sort_order'] ?></td>
                <td>
                    <?php if ($r['avatar']): ?>
                    <img src="<?= Helpers::e($r['avatar']) ?>" alt=""
                         style="width:40px;height:40px;object-fit:cover;border-radius:50%;border:2px solid #E2E8F0">
                    <?php else: ?>
                    <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#7C3AED);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px">
                        <?= strtoupper(substr($r['name'],0,1)) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-weight:600;font-size:14px"><?= Helpers::e($r['name']) ?></div>
                    <?php if ($r['role_title']): ?><div style="font-size:12px;color:#64748B"><?= Helpers::e($r['role_title']) ?></div><?php endif; ?>
                    <?php if ($r['email']): ?><div style="font-size:11px;color:#94A3B8"><?= Helpers::e($r['email']) ?></div><?php endif; ?>
                    <?php if ($r['country']): ?><div style="font-size:11px;color:#94A3B8">📍 <?= Helpers::e($r['country']) ?></div><?php endif; ?>
                </td>
                <td>
                    <span style="color:#F59E0B;font-size:14px;letter-spacing:1px">
                        <?= str_repeat('★', (int)$r['rating']) ?><?= str_repeat('☆', 5-(int)$r['rating']) ?>
                    </span>
                </td>
                <td style="max-width:220px">
                    <div style="font-size:13px;color:#374151;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
                        <?= Helpers::e($r['review_text']) ?>
                    </div>
                </td>
                <td>
                    <?php if (($r['source'] ?? 'admin') === 'public'): ?>
                    <span style="background:#EFF6FF;color:#2563EB;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:600">Public</span>
                    <?php else: ?>
                    <span style="background:#F1F5F9;color:#64748B;border-radius:20px;padding:2px 10px;font-size:11px;font-weight:600">Admin</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center">
                    <?php if ($r['is_featured']): ?>
                    <span style="color:#F59E0B;font-size:16px" title="Featured">⭐</span>
                    <?php else: ?>
                    <span style="color:#E2E8F0;font-size:16px">☆</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                    <span style="background:#FEF3C7;color:#92400E;border:1px solid #F59E0B;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:700">⏳ Pending</span>
                    <?php elseif ($r['status'] === 'active'): ?>
                    <form method="POST" action="/admin/landing/reviews/<?= $r['id'] ?>/toggle" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <button type="submit" class="btn btn-sm" style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;padding:4px 10px;font-size:12px;font-weight:600">● Active</button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="/admin/landing/reviews/<?= $r['id'] ?>/toggle" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <button type="submit" class="btn btn-sm" style="background:#F1F5F9;color:#64748B;border:1px solid #E2E8F0;padding:4px 10px;font-size:12px;font-weight:600">○ Inactive</button>
                    </form>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:5px;flex-wrap:wrap">
                        <?php if ($r['status'] === 'pending'): ?>
                        <form method="POST" action="/admin/landing/reviews/<?= $r['id'] ?>/approve" style="display:inline">
                            <?= Helpers::csrf() ?>
                            <button type="submit" class="btn btn-sm" style="background:#10B981;color:#fff;border-color:#10B981;padding:4px 10px;font-size:12px" title="Approve">✓ Approve</button>
                        </form>
                        <form method="POST" action="/admin/landing/reviews/<?= $r['id'] ?>/reject" style="display:inline"
                              onsubmit="return confirm('Reject this review?')">
                            <?= Helpers::csrf() ?>
                            <button type="submit" class="btn btn-sm" style="background:#EF4444;color:#fff;border-color:#EF4444;padding:4px 10px;font-size:12px" title="Reject">✕ Reject</button>
                        </form>
                        <?php endif; ?>
                        <a href="/admin/landing/reviews/<?= $r['id'] ?>/edit" class="btn btn-secondary btn-sm">✏</a>
                        <form method="POST" action="/admin/landing/reviews/<?= $r['id'] ?>/delete" style="display:inline"
                              onsubmit="return confirm('Delete this review?')">
                            <?= Helpers::csrf() ?>
                            <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="padding:10px 20px;font-size:12px;color:#94A3B8;border-top:1px solid #F1F5F9">
        <?= count($reviews) ?> review<?= count($reviews)!==1?'s':'' ?> found
        · <strong style="color:#10B981"><?= $activeCount ?> approved</strong>
        · <strong style="color:#F59E0B"><?= $pendingCount ?> pending</strong>
        · <?= $inactiveCount ?> inactive
    </div>
</div>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
