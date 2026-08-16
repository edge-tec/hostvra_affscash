<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<style>
/* Scoped to /affiliate/rewards only — no global styles affected. */
.ar-hero{background:linear-gradient(135deg,#1E1B4B,#312E81);color:#fff;border-radius:14px;padding:18px 22px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px}
.ar-hero h2{margin:0;font-size:18px;font-weight:800;line-height:1.2}
.ar-hero .ar-sub{font-size:12.5px;opacity:.85;margin-top:4px}
.ar-progress{background:rgba(255,255,255,.18);border-radius:99px;height:10px;overflow:hidden;margin-top:10px}
.ar-progress > div{background:#fff;height:100%;transition:width .4s}

.ar-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:24px}
.ar-card{background:#fff;border:1px solid #E5E7EB;border-radius:14px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s;position:relative}
.ar-card:hover{box-shadow:0 14px 32px -18px rgba(15,23,42,.2)}
.ar-img{aspect-ratio:16/9;background:#F1F5F9;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:34px;position:relative}
.ar-badge{position:absolute;top:10px;left:10px;display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:99px;font-size:10.5px;font-weight:700;color:#fff;letter-spacing:.04em;text-transform:uppercase;box-shadow:0 4px 12px rgba(0,0,0,.18)}
.ar-vis{position:absolute;top:10px;right:10px;background:rgba(15,23,42,.7);color:#fff;padding:3px 8px;border-radius:99px;font-size:10px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.ar-body{padding:14px 16px;display:flex;flex-direction:column;flex:1}
.ar-title{font-weight:800;font-size:14.5px;color:#0F172A}
.ar-desc{font-size:12.5px;color:#64748B;line-height:1.5;margin-top:4px;flex:1}
.ar-meta{display:flex;justify-content:space-between;align-items:center;border-top:1px solid #F1F5F9;padding-top:10px;margin-top:12px;font-size:12px}
.ar-threshold{font-weight:800;color:#4F46E5;font-size:14px}
.ar-status{padding:3px 9px;border-radius:99px;font-size:11px;font-weight:700}
.ar-status.unlocked{background:#D1FAE5;color:#065F46}
.ar-status.locked  {background:#F1F5F9;color:#475569}
.ar-expiry{font-size:11px;color:#94A3B8;margin-top:4px}

.ar-embed-wrap{margin-top:12px}
.ar-embed-toggle{font-size:11.5px;color:#4F46E5;background:transparent;border:none;cursor:pointer;padding:0;text-decoration:underline}
.ar-embed-iframe{margin-top:8px;border-radius:8px;border:1px solid #E2E8F0;width:100%;height:200px;background:#fff}

.ar-section-title{font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748B;margin:20px 0 12px;display:flex;align-items:center;gap:8px}
.ar-section-title::before{content:"";width:5px;height:16px;border-radius:3px;background:linear-gradient(135deg,#7C3AED,#4F46E5)}

@media(max-width:480px){
    .ar-hero{padding:14px 16px;text-align:left;flex-direction:column;align-items:stretch}
    .ar-grid{grid-template-columns:1fr;gap:12px}
    .ar-img{aspect-ratio:16/9}
}
</style>

<div class="page-header">
    <div><h1>My Rewards</h1><p>Milestones you've unlocked and what's coming up next.</p></div>
</div>

<?php if ($nextRule):
    $pct = min(100, (float)$nextRule['threshold_usd'] > 0
        ? round(($nextEarned / (float)$nextRule['threshold_usd']) * 100, 1)
        : 0);
    $lastGrantTs = RewardsService::lastGrantTimestamp($affId);
    $cycleStart  = $lastGrantTs ?: ($nextRule['publish_at'] ?: $nextRule['created_at']);
?>
<div class="ar-hero">
    <div>
        <div style="font-size:11px;letter-spacing:.05em;text-transform:uppercase;opacity:.8">Next milestone</div>
        <h2><?= Helpers::e($nextRule['title']) ?></h2>
        <div class="ar-sub">
            Earn <strong>$<?= number_format(max(0, (float)$nextRule['threshold_usd'] - $nextEarned), 2) ?></strong> more to unlock.
        </div>
        <div class="ar-progress"><div style="width:<?= $pct ?>%"></div></div>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:11px;opacity:.85">
            <span>$<?= number_format($nextEarned, 2) ?></span>
            <span>$<?= number_format((float)$nextRule['threshold_usd'], 2) ?></span>
        </div>
        <?php if ($cycleStart): ?>
        <div style="margin-top:6px;font-size:11px;opacity:.7">
            Counting earnings between
            <strong style="opacity:1"><?= Helpers::e(date('M j, Y', strtotime((string)$cycleStart))) ?></strong>
            <?php if (!empty($nextRule['expires_at'])): ?>
                and <strong style="opacity:1"><?= Helpers::e(date('M j, Y', strtotime((string)$nextRule['expires_at']))) ?></strong>.
            <?php else: ?>
                and onward. Counter resets after unlock.
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div style="text-align:right">
        <div style="font-size:11px;opacity:.85">Progress</div>
        <div style="font-size:28px;font-weight:800"><?= $pct ?>%</div>
    </div>
</div>
<?php endif; ?>

<?php
// Map granted rule_ids → grant row so we can mark cards as "Unlocked".
// $grantByRule is pre-built in the controller when the detail action is not used.
if (!isset($grantByRule)) {
    $grantByRule = [];
    foreach ($grants as $g) { $grantByRule[(int)$g['rule_id']] = $g; }
}
?>

<?php if (!empty($visibleRewards)): ?>
<div class="ar-section-title">Available Rewards</div>
<div class="ar-grid">
    <?php foreach ($visibleRewards as $r):
        $isUnlocked = isset($grantByRule[(int)$r['id']]);
        $badge     = (string)($r['badge_label'] ?? '');
        $badgeCol  = (string)($r['badge_color'] ?? '');
        $vis       = (string)($r['visibility']  ?? 'public');
        $valLabel  = ($r['kind'] === 'cash' || $r['kind'] === 'bonus_credit')
            ? '$' . number_format((float)$r['value_amount'], 2)
            : (string)$r['value_text'];

        // Build the sandboxed iframe srcdoc only when embed_code is set.
        // The iframe sandbox flags allow scripts inside but block top
        // navigation, popups, forms and same-origin requests — XSS-safe.
        $embedHtml = '';
        if (!empty($r['embed_code'])) {
            $embedHtml = htmlspecialchars(
                '<!doctype html><html><head><meta charset="utf-8"><base target="_blank">'
                . '<style>body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;font-size:14px;color:#0F172A;background:#fff;padding:8px}</style>'
                . '</head><body>' . $r['embed_code'] . '</body></html>',
                ENT_QUOTES
            );
        }
    ?>
    <div class="ar-card">
        <a href="/affiliate/rewards?action=detail&reward_id=<?= (int)$r['id'] ?>" class="ar-img" style="text-decoration:none<?= !empty($r['image_path']) ? ';background-image:url(\'' . Helpers::e($r['image_path']) . '\');background-size:cover;background-position:center;background-repeat:no-repeat' : '' ?>">
            <?= empty($r['image_path']) ? '🎁' : '' ?>
            <?php if ($badge !== ''): ?>
            <span class="ar-badge" style="background:<?= Helpers::e($badgeCol ?: '#4F46E5') ?>"><?= Helpers::e($badge) ?></span>
            <?php endif; ?>
            <?php if ($vis !== 'public'): ?>
            <span class="ar-vis"><?= Helpers::e($vis) ?></span>
            <?php endif; ?>
        </a>
        <div class="ar-body">
            <a href="/affiliate/rewards?action=detail&reward_id=<?= (int)$r['id'] ?>" class="ar-title" style="text-decoration:none;color:inherit"><?= Helpers::e($r['title']) ?></a>
            <?php if (!empty($r['description'])): ?>
            <div class="ar-desc"><?= nl2br(Helpers::e(mb_strimwidth(trim(strip_tags((string)$r['description'])), 0, 120, '…'))) ?></div>
            <?php endif; ?>

            <div class="ar-meta">
                <div>
                    <span class="ar-threshold">$<?= number_format((float)$r['threshold_usd'], 0) ?></span>
                    <div style="font-size:11px;color:#94A3B8"><?= Helpers::e($valLabel) ?></div>
                </div>
                <span class="ar-status <?= $isUnlocked ? 'unlocked' : 'locked' ?>">
                    <?= $isUnlocked ? '✓ Unlocked' : 'Locked' ?>
                </span>
            </div>

            <?php if (!empty($r['expires_at'])): ?>
            <div class="ar-expiry">Expires <?= Helpers::e(date('M j, Y', strtotime((string)$r['expires_at']))) ?></div>
            <?php endif; ?>

            <div style="margin-top:12px">
                <a href="/affiliate/rewards?action=detail&reward_id=<?= (int)$r['id'] ?>" class="btn btn-secondary btn-sm" style="width:100%;text-align:center">View Details</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="ar-section-title">Earned Rewards</div>
<div class="card">
    <div class="card-body" style="padding:0">
        <?php if (empty($grants)): ?>
        <div class="text-center text-muted" style="padding:48px 20px">
            <div style="font-size:32px;margin-bottom:8px;opacity:.6">🏆</div>
            No rewards yet — keep earning to unlock your first milestone!
        </div>
        <?php else: ?>
        <table style="width:100%">
            <thead style="background:#F9FAFB">
                <tr><th>Awarded</th><th>Reward</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($grants as $g):
                $valLabel = ($g['kind'] === 'cash' || $g['kind'] === 'bonus_credit')
                    ? '$' . number_format((float)$g['value_amount'], 2)
                    : (string)$g['value_text'];
            ?>
            <tr>
                <td class="text-muted text-sm" style="white-space:nowrap"><?= Helpers::e(date('M j, Y', strtotime((string)$g['granted_at']))) ?></td>
                <td>
                    <div style="font-weight:700"><?= Helpers::e($g['title_snapshot']) ?></div>
                    <div class="text-muted" style="font-size:11.5px">
                        <span class="badge badge-info"><?= Helpers::e($g['kind']) ?></span>
                        <?= Helpers::e($valLabel) ?> · crossed $<?= number_format((float)$g['lifetime_at_grant'], 2) ?> earned
                    </div>
                </td>
                <td>
                    <?php $b = ['granted'=>'warning','claimed'=>'info','paid'=>'success','cancelled'=>'muted'][$g['status']] ?? 'muted'; ?>
                    <span class="badge badge-<?= $b ?>"><?= Helpers::e($g['status']) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>


<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
