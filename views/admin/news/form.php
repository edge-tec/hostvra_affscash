<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<!-- Quill rich text editor -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<div class="page-header">
    <div>
        <h1><?= $newsId ? '✏ Edit News' : '+ Create News' ?></h1>
        <p><?= $newsId ? 'Update this news article' : 'Write a new article, offer update, or blog post for affiliates' ?></p>
    </div>
    <a href="/admin/news" class="btn btn-secondary">← Back to News</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <?php foreach ($errors as $e): ?><div>⚠ <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="news-form">
    <?= Helpers::csrf() ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

        <!-- Main content -->
        <div>
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">Article Content</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="font-weight:700">Title <span style="color:#EF4444">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?= Helpers::e($newsItem['title'] ?? '') ?>"
                               placeholder="Enter headline…" required style="font-size:18px;font-weight:600;height:48px">
                    </div>

                    <div class="form-group">
                        <label style="font-weight:700">Short Summary</label>
                        <textarea name="summary" class="form-control" rows="3"
                                  placeholder="Brief description shown in news cards and email previews…"
                                  style="resize:vertical"><?= Helpers::e($newsItem['summary'] ?? '') ?></textarea>
                        <div style="font-size:11px;color:#94A3B8;margin-top:3px">Shown on the news card and in email notifications.</div>
                    </div>

                    <div class="form-group">
                        <label style="font-weight:700">Full Content</label>
                        <div id="quill-editor" style="min-height:280px;background:#fff;border:1px solid #E2E8F0;border-radius:0 0 8px 8px"></div>
                        <input type="hidden" name="body" id="body-input" value="<?= Helpers::e($newsItem['body'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar settings -->
        <div>
            <!-- Publish box -->
            <div class="card mb-3">
                <div class="card-header"><span class="card-title">Publish Settings</span></div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="font-weight:700">Status</label>
                        <select name="status" class="form-control" id="news-status-select" onchange="syncPublishBtn()">
                            <option value="published" <?= ($newsItem['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>● Published — visible to affiliates</option>
                            <option value="draft" <?= ($newsItem['status'] ?? '') === 'draft' ? 'selected' : '' ?>>○ Draft — hidden from affiliates</option>
                        </select>
                        <div id="draft-warning" style="display:none;margin-top:6px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:6px;padding:8px 10px;font-size:12px;color:#92400E">
                            ⚠ <strong>Draft</strong> — affiliates will NOT see this news until you publish it.
                        </div>
                    </div>

                    <!-- Hot News badge -->
                    <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;padding:12px 14px;margin-bottom:16px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:700;color:#92400E">
                            <input type="checkbox" name="is_hot" value="1" id="chk-hot"
                                   <?= ($newsItem['is_hot'] ?? 0) ? 'checked' : '' ?>
                                   style="width:18px;height:18px;accent-color:#EF4444">
                            <div>
                                <div style="font-size:14px">🔥 Hot News Badge</div>
                                <div style="font-size:11px;font-weight:400;color:#B45309;margin-top:2px">Shows red HOT badge on the card</div>
                            </div>
                        </label>
                    </div>

                    <?php if ($newsId && $newsItem['status'] === 'published' && $newsItem['email_sent']): ?>
                    <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:7px;padding:10px 12px;font-size:12px;color:#065F46;margin-bottom:12px">
                        ✉ Email already sent to affiliates
                    </div>
                    <?php elseif (!$newsId): ?>
                    <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:7px;padding:10px 12px;font-size:12px;color:#1E40AF;margin-bottom:12px">
                        📧 Publishing will automatically email all active affiliates
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary" id="news-submit-btn" style="width:100%;justify-content:center">
                        <?= $newsId ? '💾 Update News' : '🚀 Publish News' ?>
                    </button>
                    <?php if ($newsId): ?>
                    <a href="/admin/news" class="btn btn-secondary" style="width:100%;justify-content:center;margin-top:8px">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>

<script>
function syncPublishBtn() {
    var sel = document.getElementById('news-status-select');
    var btn = document.getElementById('news-submit-btn');
    var warn = document.getElementById('draft-warning');
    if (!sel || !btn) return;
    var isDraft = sel.value === 'draft';
    warn.style.display = isDraft ? 'block' : 'none';
    <?php if (!$newsId): ?>
    btn.textContent = isDraft ? '💾 Save as Draft' : '🚀 Publish News';
    btn.style.background = isDraft ? '#64748B' : '';
    btn.style.borderColor = isDraft ? '#64748B' : '';
    <?php endif; ?>
}
// Run on page load to set correct initial state
document.addEventListener('DOMContentLoaded', syncPublishBtn);
</script>
                </div>
            </div>

            <!-- Featured image -->
            <div class="card">
                <div class="card-header"><span class="card-title">Featured Image</span></div>
                <div class="card-body">
                    <?php if (!empty($newsItem['image'])): ?>
                    <div style="margin-bottom:12px;border-radius:8px;overflow:hidden">
                        <img src="<?= Helpers::e($newsItem['image']) ?>" alt="" style="width:100%;height:140px;object-fit:cover">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" id="img-input">
                    <div id="img-preview" style="display:none;margin-top:10px;border-radius:8px;overflow:hidden">
                        <img id="img-preview-img" src="" alt="" style="width:100%;height:140px;object-fit:cover">
                    </div>
                    <div style="font-size:11px;color:#94A3B8;margin-top:6px">Recommended: 1200×630px JPEG/PNG</div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// ── Quill editor ──────────────────────────────────────────────────────────
var quill = new Quill('#quill-editor', {
    theme: 'snow',
    placeholder: 'Write your article content here…',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            ['link', 'image'],
            ['blockquote', 'code-block'],
            ['clean']
        ]
    }
});

// Load existing content
var existingBody = document.getElementById('body-input').value;
if (existingBody) quill.root.innerHTML = existingBody;

// Sync before submit
document.getElementById('news-form').addEventListener('submit', function() {
    document.getElementById('body-input').value = quill.root.innerHTML;
});

// ── Image preview ──────────────────────────────────────────────────────────
document.getElementById('img-input').addEventListener('change', function() {
    var file = this.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('img-preview-img').src = e.target.result;
        document.getElementById('img-preview').style.display = 'block';
    };
    reader.readAsDataURL(file);
});
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
