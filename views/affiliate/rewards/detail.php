<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<style>
.rd-hero{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start}
.rd-img-wrap{border-radius:14px;overflow:hidden;border:1px solid #E5E7EB;background:#F8FAFC;aspect-ratio:16/9;background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:64px;position:relative}
.rd-badge{position:absolute;top:12px;left:12px;display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:99px;font-size:11px;font-weight:700;color:#fff;letter-spacing:.04em;text-transform:uppercase;box-shadow:0 4px 12px rgba(0,0,0,.2)}
.rd-vis{position:absolute;top:12px;right:12px;background:rgba(15,23,42,.7);color:#fff;padding:4px 10px;border-radius:99px;font-size:10px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.rd-status-block{display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:99px;font-size:13px;font-weight:700;margin-bottom:14px}
.rd-status-block.unlocked{background:#D1FAE5;color:#065F46}
.rd-status-block.locked{background:#F1F5F9;color:#475569}
.rd-title{font-size:24px;font-weight:800;color:#0F172A;line-height:1.25;margin-bottom:10px}
.rd-value-block{display:flex;align-items:baseline;gap:8px;margin-bottom:18px}
.rd-value{font-size:28px;font-weight:900;color:#4F46E5}
.rd-value-label{font-size:13px;color:#64748B;font-weight:500}
.rd-info-row{display:flex;justify-content:space-between;align-items:center;font-size:13px;padding:10px 0;border-bottom:1px solid #F1F5F9}
.rd-info-row:last-child{border-bottom:none}
.rd-desc{font-size:14px;color:#334155;line-height:1.8;white-space:pre-wrap;word-break:break-word}
.rd-progress{background:#E5E7EB;border-radius:99px;height:10px;overflow:hidden;margin:10px 0 4px}
.rd-progress > div{background:linear-gradient(90deg,#4F46E5,#7C3AED);height:100%;transition:width .4s}

.ar-embed-wrap{margin-top:12px}
.ar-embed-toggle{font-size:12px;color:#4F46E5;background:transparent;border:none;cursor:pointer;padding:0;text-decoration:underline}
.ar-embed-iframe{margin-top:8px;border-radius:8px;border:1px solid #E2E8F0;width:100%;height:220px;background:#fff}

@media(max-width:680px){
    .rd-hero{grid-template-columns:1fr}
}
</style>

<div class="page-header">
    <div>
        <h1><?= Helpers::e($reward['title']) ?></h1>
        <p>Reward details</p>
    </div>
    <a href="/affiliate/rewards" class="btn btn-secondary">← Back to My Rewards</a>
</div>

<div class="rd-hero">
    <!-- Left: Image -->
    <div class="rd-img-wrap" <?= !empty($reward['image_path']) ? 'style="background-image:url(\'' . Helpers::e($reward['image_path']) . '\')"' : '' ?>>
        <?= empty($reward['image_path']) ? '🎁' : '' ?>
        <?php if (!empty($reward['badge_label'])): ?>
        <span class="rd-badge" style="background:<?= Helpers::e($reward['badge_color'] ?: '#4F46E5') ?>"><?= Helpers::e($reward['badge_label']) ?></span>
        <?php endif; ?>
        <?php if (($reward['visibility'] ?? 'public') !== 'public'): ?>
        <span class="rd-vis"><?= Helpers::e($reward['visibility']) ?></span>
        <?php endif; ?>
    </div>

    <!-- Right: Info panel -->
    <div>
        <!-- Unlocked / Locked status -->
        <div class="rd-status-block <?= $isUnlocked ? 'unlocked' : 'locked' ?>">
            <?= $isUnlocked ? '✓ Unlocked' : '🔒 Locked' ?>
        </div>

        <div class="rd-title"><?= Helpers::e($reward['title']) ?></div>

        <?php
            $valLabel = ($reward['kind'] === 'cash' || $reward['kind'] === 'bonus_credit')
                ? '$' . number_format((float)$reward['value_amount'], 2)
                : (string)$reward['value_text'];
        ?>
        <div class="rd-value-block">
            <span class="rd-value"><?= Helpers::e($valLabel) ?></span>
            <span class="rd-value-label"><?= Helpers::e($reward['kind']) ?></span>
        </div>

        <!-- Milestone & progress -->
        <div class="card" style="margin-bottom:18px">
            <div class="card-body" style="padding:14px 18px">
                <?php
                    $earned   = $rewardEarned ?? 0.0;
                    $startTs  = $reward['publish_at'] ?: $reward['created_at'];
                    $endTs    = $reward['expires_at'] ?? null;
                    $windowLabel = '';
                    if ($startTs) {
                        $windowLabel = ' (' . date('M j, Y', strtotime((string)$startTs));
                        $windowLabel .= $endTs ? ' → ' . date('M j, Y', strtotime((string)$endTs)) : ' → no expiry';
                        $windowLabel .= ')';
                    }
                ?>
                <div class="rd-info-row">
                    <span class="text-muted">Milestone threshold</span>
                    <strong>$<?= number_format((float)$reward['threshold_usd'], 2) ?></strong>
                </div>
                <div class="rd-info-row">
                    <span class="text-muted">Your earnings in reward window<?= Helpers::e($windowLabel) ?></span>
                    <strong>$<?= number_format($earned, 2) ?></strong>
                </div>
                <?php if (!$isUnlocked): ?>
                <?php $need = max(0, (float)$reward['threshold_usd'] - $earned); ?>
                <div class="rd-info-row" style="font-weight:700">
                    <span>Still needed</span>
                    <span style="color:#DC2626">$<?= number_format($need, 2) ?> more</span>
                </div>
                <?php $pct = min(100, $earned > 0 ? round(($earned / (float)$reward['threshold_usd']) * 100, 1) : 0); ?>
                <div style="margin-top:8px">
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:#94A3B8;margin-bottom:4px">
                        <span>Progress</span><span><?= $pct ?>%</span>
                    </div>
                    <div class="rd-progress"><div style="width:<?= $pct ?>%"></div></div>
                </div>
                <?php else: ?>
                <?php $grant = $grantByRule[(int)$reward['id']] ?? null; ?>
                <div class="rd-info-row" style="font-weight:700;color:#065F46">
                    <span>Awarded on</span>
                    <span><?= $grant ? Helpers::e(date('M j, Y', strtotime((string)$grant['granted_at']))) : '—' ?></span>
                </div>
                <?php if ($grant): ?>
                <div class="rd-info-row">
                    <span class="text-muted">Grant status</span>
                    <?php $b = ['granted'=>'warning','claimed'=>'info','paid'=>'success','cancelled'=>'muted'][$grant['status']] ?? 'muted'; ?>
                    <span class="badge badge-<?= $b ?>"><?= Helpers::e($grant['status']) ?></span>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($reward['expires_at'])): ?>
                <div class="rd-info-row" style="color:#DC2626;font-weight:600">
                    <span>Expires</span>
                    <span><?= Helpers::e(date('M j, Y', strtotime((string)$reward['expires_at']))) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Full description -->
<?php if (!empty(trim((string)($reward['description'] ?? '')))): ?>
<div class="card" style="margin-top:24px">
    <div class="card-header"><span class="card-title">Description</span></div>
    <div class="card-body">
        <div class="rd-desc"><?= preg_replace('/\s(on\w+|href\s*=\s*["\']?\s*javascript:)[^\s>]*/i', '', strip_tags((string)$reward['description'], '<p><br><strong><em><b><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div><blockquote><pre><code><img>')) ?></div>
    </div>
</div>
<?php endif; ?>

<!-- Embed preview (if set) -->
<?php if (!empty($reward['embed_code'])):
    $embedHtml = htmlspecialchars(
        '<!doctype html><html><head><meta charset="utf-8"><base target="_blank">'
        . '<style>body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;font-size:14px;color:#0F172A;background:#fff;padding:8px}</style>'
        . '</head><body>' . $reward['embed_code'] . '</body></html>',
        ENT_QUOTES
    );
?>
<div class="card" style="margin-top:18px">
    <div class="card-header"><span class="card-title">Preview</span></div>
    <div class="card-body">
        <div class="ar-embed-wrap">
            <button type="button" class="ar-embed-toggle" data-toggle-embed>Show preview</button>
            <iframe class="ar-embed-iframe"
                    style="display:none"
                    sandbox="allow-scripts allow-popups"
                    referrerpolicy="no-referrer"
                    loading="lazy"
                    title="Reward preview"
                    srcdoc="<?= $embedHtml ?>"></iframe>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('[data-toggle-embed]').forEach(function(btn){
    btn.addEventListener('click', function(){
        var iframe = btn.parentNode.querySelector('iframe');
        if (!iframe) return;
        var open = iframe.style.display !== 'none';
        iframe.style.display = open ? 'none' : 'block';
        btn.textContent = open ? 'Show preview' : 'Hide preview';
    });
});
</script>
<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
