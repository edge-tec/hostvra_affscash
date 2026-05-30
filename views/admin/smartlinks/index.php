<?php
require BASE_PATH . '/views/layouts/admin.php';
$_pendingReqs = Database::fetchOne("SELECT COUNT(*) as cnt FROM smartlink_requests WHERE status='pending'");
$_pendingCount = (int)($_pendingReqs['cnt'] ?? 0);
?>

<div class="page-header">
    <div><h1>Smartlinks</h1><p>Rotating offer links with intelligent traffic distribution</p></div>
    <div class="d-flex gap-2">
        <a href="/admin/smartlinks?action=requests" class="btn btn-secondary">
            &#128338; Requests<?= $_pendingCount > 0 ? ' <span style="background:#EF4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;font-weight:700">'.$_pendingCount.'</span>' : '' ?>
        </a>
        <a href="/admin/smartlinks/create" class="btn btn-primary">+ Create Smartlink</a>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>#ID</th><th>Name</th><th>Slug</th><th>Rotation</th><th>Offers</th><th>Clicks</th><th>Convs</th><th>Status</th><th>Access</th><th>Link</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $appUrl = Config::get('config','app.url') ?? ''; ?>
            <?php if(empty($smartlinks)): ?>
            <tr><td colspan="11"><div class="empty-state"><div class="icon">&#128279;</div><h3>No smartlinks yet</h3><p>Create your first smartlink to start rotating offers.</p></div></td></tr>
            <?php else: ?>
            <?php foreach($smartlinks as $sl): ?>
            <tr>
                <td class="text-muted" style="font-size:11px;white-space:nowrap;font-family:monospace">#<?= $sl['id'] ?></td>
                <td><div class="fw-bold"><?= Helpers::e($sl['name']) ?></div><div class="text-muted text-sm"><?= Helpers::e($sl['description'] ?: '') ?></div></td>
                <td><code style="background:#F1F5F9;padding:2px 8px;border-radius:4px;font-size:12px"><?= Helpers::e($sl['slug']) ?></code></td>
                <td><span class="badge badge-info"><?= Helpers::e($sl['rotation_type']) ?></span></td>
                <td><?= $sl['offer_count'] ?> offers</td>
                <td><?= number_format((int)$sl['total_clicks']) ?></td>
                <td><?= number_format((int)$sl['total_convs']) ?></td>
                <td><span class="badge badge-<?= $sl['status']==='active'?'success':'muted' ?>"><?= $sl['status'] ?></span></td>
                <td>
                    <?php if (!empty($sl['require_approval'])): ?>
                    <span class="badge badge-warning" style="font-size:11px">Manual</span>
                    <?php else: ?>
                    <span class="badge badge-success" style="font-size:11px">Auto</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="copy-group" style="max-width:280px">
                        <input type="text" class="form-control" id="sl-<?= $sl['id'] ?>" value="<?= Helpers::e($appUrl.'/smartlink/'.$sl['slug'].'?aff={AFF_CODE}') ?>" readonly>
                        <button class="btn btn-secondary btn-sm" data-copy="sl-<?= $sl['id'] ?>">Copy</button>
                        <button class="btn btn-sm" style="background:#7C3AED;color:#fff;border:none;cursor:pointer" id="sl-short-btn-<?= $sl['id'] ?>" onclick="adminShortenSl(<?= $sl['id'] ?>, document.getElementById('sl-<?= $sl['id'] ?>').value)">&#9986; Short</button>
                    </div>
                    <div id="sl-short-result-<?= $sl['id'] ?>" style="display:none;margin-top:6px;max-width:280px">
                        <div class="copy-group">
                            <input type="text" id="sl-short-url-<?= $sl['id'] ?>" class="form-control" readonly style="font-size:11px">
                            <button class="btn btn-secondary btn-sm" data-copy="sl-short-url-<?= $sl['id'] ?>">Copy</button>
                            <a id="sl-short-open-<?= $sl['id'] ?>" href="#" target="_blank" class="btn btn-sm btn-secondary">Open</a>
                        </div>
                    </div>
                </td>
                <td style="white-space:nowrap">
                    <a href="/admin/smartlinks?action=edit&id=<?= $sl['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <form method="POST" action="/admin/smartlinks" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="id" value="<?= $sl['id'] ?>">
                        <input type="hidden" name="toggle_status" value="1">
                        <button class="btn btn-sm btn-secondary"><?= $sl['status']==='active'?'Pause':'Activate' ?></button>
                    </form>
                    <form method="POST" action="/admin/smartlinks?action=delete" style="display:inline" onsubmit="return confirm('Delete this smartlink? This cannot be undone.')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="id" value="<?= $sl['id'] ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
var _adminCsrf = '<?= Auth::generateCsrf() ?>';

// Copy buttons
document.querySelectorAll('[data-copy]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var inp = document.getElementById(this.dataset.copy);
        if (!inp) return;
        inp.select(); document.execCommand('copy');
        var orig = this.textContent;
        this.textContent = '✓ Copied';
        setTimeout(() => this.textContent = orig, 1500);
    });
});

function adminShortenSl(id, url) {
    var btn = document.getElementById('sl-short-btn-' + id);
    btn.disabled = true; btn.textContent = '...';
    fetch('/affiliate/shorten', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: '_token=' + encodeURIComponent(_adminCsrf) + '&url=' + encodeURIComponent(url)
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false; btn.innerHTML = '&#9986; Short';
        if (d.short_url) {
            document.getElementById('sl-short-url-' + id).value = d.short_url;
            document.getElementById('sl-short-result-' + id).style.display = 'block';
            document.getElementById('sl-short-open-' + id).href = d.short_url;
        } else { alert(d.error || 'Failed to shorten'); }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '&#9986; Short'; });
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
