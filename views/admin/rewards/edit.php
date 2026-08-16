<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<!-- Quill rich-text editor -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>

<style>
/* Scoped responsive styles for Reward edit view */
.rw-edit-card {
    max-width: 820px;
    width: 100%;
    margin: 0 auto;
    box-sizing: border-box;
}
.rw-edit-card .card-body {
    padding: 24px;
    box-sizing: border-box;
}
@media (max-width: 575px) {
    .rw-edit-card .card-body {
        padding: 16px 12px !important;
    }
}

.form-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    box-sizing: border-box;
}
.form-grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 14px;
    box-sizing: border-box;
}
@media (max-width: 640px) {
    .form-grid-2, .form-grid-3 {
        grid-template-columns: 1fr !important;
    }
}

.rw-img-prev {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    background: #F1F5F9;
    border: 1px dashed #CBD5E1;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94A3B8;
    overflow: hidden;
    margin-bottom: 8px;
    max-width: 100%;
    box-sizing: border-box;
}
.rw-img-prev img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.rw-img-prev .rw-empty {
    font-size: 12px;
    text-align: center;
    padding: 12px;
}

/* Quill editor container styling */
#quill-description-wrap {
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
    max-width: 100%;
    box-sizing: border-box;
}
#quill-description-wrap .ql-toolbar {
    border: none;
    border-bottom: 1px solid #E2E8F0;
    background: #F8FAFC;
    padding: 8px 10px;
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 4px !important;
}
#quill-description-wrap .ql-formats {
    display: inline-flex !important;
    align-items: center !important;
    flex-wrap: wrap !important;
    margin-right: 4px !important;
}
#quill-description-wrap button {
    width: 26px !important;
    height: 26px !important;
    padding: 3px !important;
}
#quill-description-wrap .ql-container {
    border: none;
    font-size: 13.5px;
    font-family: inherit;
    min-height: 120px;
}
#quill-description-wrap .ql-editor {
    min-height: 120px;
    padding: 12px 14px;
    color: #0F172A;
    line-height: 1.6;
    word-break: break-word;
    overflow-wrap: anywhere;
}

.rw-badge-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 11px;
    border-radius: 99px;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    letter-spacing: .02em;
    text-transform: uppercase;
    max-width: 100%;
    word-break: break-word;
    white-space: nowrap;
}
.rw-badge-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
    max-width: 100%;
    box-sizing: border-box;
}
.rw-badge-pick {
    cursor: pointer;
    border: none;
    padding: 0;
    background: transparent;
}
.rw-badge-pick.active {
    outline: 2px solid #0F172A;
    outline-offset: 2px;
    border-radius: 99px;
}

.form-hint {
    font-size: 11.5px;
    color: #64748B;
    margin-top: 4px;
    word-break: break-word;
    overflow-wrap: anywhere;
}
</style>

<div class="page-header">
    <div>
        <h1><?= $rule ? 'Edit Reward Rule' : 'New Reward Rule' ?></h1>
        <p><?= $rule ? 'Update milestone threshold, values, badge and visibility controls.' : 'Create a new earning-milestone reward rule for affiliates.' ?></p>
    </div>
    <a href="/admin/rewards" class="btn btn-secondary">← Back to Rewards</a>
</div>

<div class="card rw-edit-card">
    <div class="card-header">
        <span class="card-title"><?= $rule ? 'Edit Rule Details' : 'Reward Rule Configuration' ?></span>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/rewards" enctype="multipart/form-data" id="rwRuleForm">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="save_rule">
            <input type="hidden" name="id" value="<?= (int)($rule['id'] ?? 0) ?>">

            <!-- Reward Image Upload -->
            <div class="form-group">
                <label>Reward Image</label>
                <div class="rw-img-prev" id="rwImgPrev">
                    <?php if (!empty($rule['image_path'])): ?>
                        <img src="<?= Helpers::e($rule['image_path']) ?>" alt="">
                    <?php else: ?>
                        <div class="rw-empty">No image selected</div>
                    <?php endif; ?>
                </div>
                <input type="file" name="image" id="rwImageFile" accept="image/png,image/jpeg,image/gif,image/webp" class="form-control">
                <label style="display:<?= !empty($rule['image_path']) ? 'inline-flex' : 'none' ?>;align-items:center;gap:6px;font-size:12px;color:#DC2626;margin-top:6px" id="rwClearWrap">
                    <input type="checkbox" name="clear_image" value="1" id="rwClearImg"> Remove current image
                </label>
                <div class="form-hint">PNG / JPG / GIF / WebP · max 4 MB · 16:9 aspect ratio recommended</div>
            </div>

            <!-- Title & Threshold -->
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control" required maxlength="255" value="<?= Helpers::e($rule['title'] ?? '') ?>" placeholder="e.g. $5,000 Milestone Bonus">
                </div>
                <div class="form-group">
                    <label>Threshold (USD) *</label>
                    <input type="number" name="threshold_usd" step="0.01" min="0.01" class="form-control" required value="<?= Helpers::e((string)($rule['threshold_usd'] ?? '')) ?>" placeholder="e.g. 5000.00">
                    <div class="form-hint">Affiliate approved earnings required to unlock</div>
                </div>
            </div>

            <!-- Kind & Visibility -->
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Reward Kind *</label>
                    <select name="kind" class="form-control">
                        <?php foreach (['cash' => 'Cash payout', 'bonus_credit' => 'Bonus credit', 'voucher' => 'Voucher / code', 'product' => 'Product / gift'] as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= ($rule['kind'] ?? 'cash') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Visibility</label>
                    <select name="visibility" class="form-control">
                        <?php foreach (['public' => 'Public — everyone', 'affiliate' => 'Affiliate Only', 'vip' => 'VIP Only', 'private' => 'Private (admin draft)'] as $v => $lbl): ?>
                        <option value="<?= $v ?>" <?= ($rule['visibility'] ?? 'public') === $v ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Value Amount & Value Text -->
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Value Amount ($)</label>
                    <input type="number" name="value_amount" step="0.01" min="0" class="form-control" value="<?= Helpers::e((string)($rule['value_amount'] ?? '')) ?>" placeholder="e.g. 500.00">
                </div>
                <div class="form-group">
                    <label>Value Text</label>
                    <input type="text" name="value_text" class="form-control" maxlength="255" value="<?= Helpers::e($rule['value_text'] ?? '') ?>" placeholder="e.g. Apple Watch Series 11">
                </div>
            </div>

            <!-- Description (Quill Editor) -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:6px">
                    Description
                    <span style="font-size:11px;color:#94A3B8;font-weight:400;background:#F1F5F9;padding:2px 7px;border-radius:99px">Rich Text</span>
                </label>
                <textarea name="description" id="ruleDescHidden" style="display:none"><?= Helpers::e($rule['description'] ?? '') ?></textarea>
                <div id="quill-description-wrap">
                    <div id="quill-editor"></div>
                </div>
                <div class="form-hint">Detailed reward description displayed on affiliate milestone cards</div>
            </div>

            <!-- Embed Code -->
            <div class="form-group">
                <label>Embed Code (Sandboxed iframe)</label>
                <textarea name="embed_code" class="form-control" rows="3" placeholder="<iframe src=...></iframe> or custom HTML embed..."><?= Helpers::e($rule['embed_code'] ?? '') ?></textarea>
                <div class="form-hint">Rendered inside a sandboxed iframe on the affiliate side — scripts inside cannot reach your dashboard. 20,000 char max.</div>
            </div>

            <!-- Badge Manager & Preset Picker -->
            <div class="form-group">
                <label>Badge & Highlights</label>
                <div class="rw-badge-row">
                    <button type="button" class="rw-badge-pick <?= empty($rule['badge_label']) ? 'active' : '' ?>" data-label="" data-color="#4F46E5">
                        <span class="rw-badge-chip" style="background:#64748B">NONE</span>
                    </button>
                    <?php foreach ($badges as $b): ?>
                    <button type="button" class="rw-badge-pick <?= ($rule['badge_label'] ?? '') === $b['label'] ? 'active' : '' ?>" data-label="<?= Helpers::e($b['label']) ?>" data-color="<?= Helpers::e($b['color']) ?>">
                        <span class="rw-badge-chip" style="background:<?= Helpers::e($b['color']) ?>"><?= Helpers::e($b['label']) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>

                <div class="form-grid-2" style="margin-top:10px">
                    <div>
                        <input type="text" name="badge_label" id="ruleBadgeLabel" class="form-control" placeholder="Badge label (override)" maxlength="60" value="<?= Helpers::e($rule['badge_label'] ?? '') ?>">
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="color" name="badge_color" id="ruleBadgeColor" class="form-control" value="<?= Helpers::e($rule['badge_color'] ?: '#4F46E5') ?>" style="height:38px;padding:2px 4px;width:60px">
                        <span style="font-size:12px;color:#64748B">Badge Colour</span>
                    </div>
                </div>
            </div>

            <!-- Publish At & Expires At -->
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Publish Date/Time</label>
                    <input type="datetime-local" name="publish_at" class="form-control" value="<?= !empty($rule['publish_at']) ? Helpers::e(date('Y-m-d\TH:i', strtotime($rule['publish_at']))) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Expiry Date/Time</label>
                    <input type="datetime-local" name="expires_at" class="form-control" value="<?= !empty($rule['expires_at']) ? Helpers::e(date('Y-m-d\TH:i', strtotime($rule['expires_at']))) : '' ?>">
                </div>
            </div>

            <!-- Sort Order & Active Toggle -->
            <div class="form-grid-2" style="align-items:center">
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= Helpers::e((string)($rule['sort_order'] ?? 0)) ?>">
                </div>
                <div class="form-group" style="padding-top:12px">
                    <label style="display:inline-flex;align-items:center;gap:10px;cursor:pointer">
                        <input type="checkbox" name="active" value="1" <?= (int)($rule['active'] ?? 1) === 1 ? 'checked' : '' ?> style="width:18px;height:18px">
                        <span style="font-weight:600;font-size:14px;color:#0F172A">Active (Published for affiliates)</span>
                    </label>
                </div>
            </div>

            <!-- Form Action Buttons -->
            <div style="display:flex;gap:10px;margin-top:20px;flex-wrap:wrap">
                <button class="btn btn-primary" type="submit" style="min-width:140px"><?= $rule ? 'Save Changes' : 'Create Reward' ?></button>
                <a href="/admin/rewards" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    // Image preview handler
    var fileInput = document.getElementById('rwImageFile');
    if (fileInput) {
        fileInput.addEventListener('change', function(e){
            var f = e.target.files && e.target.files[0]; if (!f) return;
            var url = URL.createObjectURL(f);
            document.getElementById('rwImgPrev').innerHTML = '<img src="' + url + '" alt="">';
            document.getElementById('rwClearWrap').style.display = 'inline-flex';
        });
    }

    // Badge picker handler
    document.querySelectorAll('.rw-badge-pick').forEach(function(btn){
        btn.addEventListener('click', function(){
            var l = btn.dataset.label || '';
            var c = btn.dataset.color || '#4F46E5';
            document.getElementById('ruleBadgeLabel').value = l;
            document.getElementById('ruleBadgeColor').value = c;
            document.querySelectorAll('.rw-badge-pick').forEach(function(b){ b.classList.toggle('active', b.dataset.label === l); });
        });
    });

    // Quill Rich Text Editor Initialization
    var savedDesc = <?= json_encode($rule['description'] ?? '') ?>;
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Write a description for this reward…',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'header': [1, 2, 3, false] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'blockquote', 'clean']
            ]
        }
    });

    if (savedDesc) {
        if (savedDesc.trim().startsWith('<')) {
            quill.root.innerHTML = savedDesc;
        } else {
            quill.setText(savedDesc);
        }
    }

    document.getElementById('rwRuleForm').addEventListener('submit', function () {
        var html = quill.root.innerHTML;
        if (html === '<p><br></p>') html = '';
        document.getElementById('ruleDescHidden').value = html;
    });
})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
