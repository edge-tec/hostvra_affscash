<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>

<style>
/* Scoped to /admin/rewards only — no global styles touched. */

/* ── Tabs ───────────────────────────────────────────────────────────── */
.rw-tab {
    display: inline-block;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #64748B;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    white-space: nowrap;
    flex-shrink: 0;
}
.rw-tab.active {
    color: #4F46E5;
    border-color: #4F46E5;
}
.rw-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 1px solid #E2E8F0;
    margin-bottom: 18px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

/* ── Main Grid & Editor Breakpoints ──────────────────────────────────── */
.rw-grid {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 18px;
    align-items: start;
    min-width: 0;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

@media (max-width: 991px) {
    .rw-grid {
        grid-template-columns: 1fr;
    }
}

.rw-editor {
    min-width: 0;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
}
.rw-editor .card,
.rw-editor .card-body {
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
    overflow: hidden;
    min-width: 0;
}
.rw-editor .form-control,
.rw-editor input,
.rw-editor select,
.rw-editor textarea {
    max-width: 100% !important;
    width: 100% !important;
    min-width: 0 !important;
    box-sizing: border-box !important;
}
.rw-editor input[type="file"] {
    max-width: 100% !important;
    width: 100% !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
    font-size: 12px !important;
}
.rw-editor input[type="file"]::-webkit-file-upload-button {
    font-size: 11px !important;
    padding: 4px 8px !important;
    max-width: 45% !important;
}
.rw-editor .form-row {
    display: grid;
    gap: 10px;
    min-width: 0;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
}
.rw-editor .form-row.cols-2 { grid-template-columns: 1fr 1fr; }
.rw-editor .form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }

@media (max-width: 575px) {
    .rw-editor .form-row.cols-2,
    .rw-editor .form-row.cols-3 {
        grid-template-columns: 1fr;
    }
}

.form-hint {
    word-wrap: break-word !important;
    overflow-wrap: anywhere !important;
    word-break: break-word !important;
    max-width: 100% !important;
    white-space: normal !important;
    font-size: 11.5px;
    color: #64748B;
    margin-top: 4px;
}

.page-header h1,
.page-header p {
    max-width: 100% !important;
    overflow-wrap: anywhere !important;
    word-break: break-word !important;
}

/* ── Image Upload & Preview Container ────────────────────────────────── */
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
    margin-bottom: 6px;
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
    padding: 10px;
}

/* ── Badges ──────────────────────────────────────────────────────────── */
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
}
.rw-badge-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 4px;
    max-width: 100%;
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

/* ── Rules Table & Filter ───────────────────────────────────────────── */
.rw-table-wrap {
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    width: 100%;
    max-width: 100%;
    display: block;
    box-sizing: border-box;
}
.rw-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    max-width: 100%;
}
.rw-table thead th {
    background: #F8FAFC;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748B;
    padding: 10px 12px;
    text-align: left;
    border-bottom: 1px solid #E2E8F0;
}
.rw-table tbody td {
    padding: 11px 12px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
}
.rw-table tbody tr:hover td { background: #FAFAFC; }
.rw-table tbody tr.dragging { opacity: .45; }
.rw-handle {
    cursor: grab;
    color: #94A3B8;
    font-size: 18px;
    line-height: 1;
    user-select: none;
    padding: 0 4px;
}
.rw-handle:active { cursor: grabbing; }

.rw-thumb {
    width: 54px;
    height: 36px;
    border-radius: 6px;
    background: #F1F5F9 center/cover no-repeat;
    border: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94A3B8;
    font-size: 18px;
    flex-shrink: 0;
}

.rw-toggle {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 20px;
}
.rw-toggle input { opacity: 0; width: 0; height: 0; }
.rw-toggle .t {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #CBD5E1;
    border-radius: 99px;
    transition: .18s;
}
.rw-toggle .k {
    position: absolute;
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .18s;
    box-shadow: 0 1px 2px rgba(0,0,0,.18);
}
.rw-toggle input:checked + .t { background: #10B981; }
.rw-toggle input:checked + .t .k { left: 19px; }

.rw-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-bottom: 14px;
    background: #fff;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 10px 12px;
    max-width: 100%;
    box-sizing: border-box;
}
.rw-filter input, .rw-filter select {
    height: 36px;
    font-size: 12.5px;
    padding: 4px 10px;
    border: 1px solid #E2E8F0;
    border-radius: 6px;
    background: #fff;
    max-width: 100%;
    box-sizing: border-box;
}
.rw-filter .grow { flex: 1 1 180px; min-width: 0; }

@media (max-width: 575px) {
    .rw-filter {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        padding: 12px;
    }
    .rw-filter select,
    .rw-filter input,
    .rw-filter button,
    .rw-filter a {
        width: 100% !important;
        flex: 1 1 100% !important;
        box-sizing: border-box !important;
        margin: 0 !important;
    }
}

#ruleDescToolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 6px;
    padding: 6px;
    background: #FAFAFC;
    max-width: 100%;
    box-sizing: border-box;
}
#ruleDescToolbar .ql-formats { margin-right: 4px !important; }

/* ── Mobile Responsive Table Cards (< 768px) ─────────────────────────── */
@media (max-width: 767px) {
    .rw-table-wrap {
        background: transparent;
        border: none;
        overflow-x: hidden;
        width: 100%;
    }
    .rw-table { display: block; width: 100%; box-sizing: border-box; }
    .rw-table thead { display: none; }
    .rw-table tbody { display: block; width: 100%; box-sizing: border-box; }
    .rw-table tbody tr {
        display: block;
        background: #fff;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        margin-bottom: 14px;
        padding: 12px 14px;
        box-shadow: 0 2px 8px rgba(15,23,42,.04);
        box-sizing: border-box;
        position: relative;
        width: 100%;
        overflow: hidden;
    }
    .rw-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #F1F5F9;
        font-size: 13px;
        box-sizing: border-box;
        width: 100%;
        gap: 8px;
    }
    .rw-table tbody td:last-child { border-bottom: none; }
    .rw-table tbody td[data-label]::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748B;
        flex-shrink: 0;
    }
    .rw-table tbody td > * {
        text-align: right;
        max-width: 65%;
        word-break: break-word;
    }
    .rw-mini-actions {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 6px !important;
        flex-wrap: wrap !important;
        max-width: 65% !important;
        white-space: normal !important;
    }
    .rw-mini-actions form {
        display: inline-block !important;
        margin: 0 !important;
    }
    .rw-mini-actions .btn {
        padding: 4px 10px !important;
        font-size: 11.5px !important;
        flex-shrink: 0 !important;
    }
    .rw-td-top {
        display: flex !important;
        align-items: flex-start !important;
        gap: 10px !important;
        border-bottom: 1px solid #E2E8F0 !important;
        padding-bottom: 10px !important;
        margin-bottom: 4px !important;
        justify-content: flex-start !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    .rw-td-top > * {
        text-align: left !important;
        max-width: 100% !important;
    }
    .rw-td-top::before { display: none !important; }
}

.rw-vis-tag {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 99px;
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.rw-vis-public   { background: #E0F2FE; color: #075985; }
.rw-vis-affiliate{ background: #EEF2FF; color: #3730A3; }
.rw-vis-vip      { background: #FEF3C7; color: #92400E; }
.rw-vis-private  { background: #F1F5F9; color: #475569; }

.rw-mini-actions form { display: inline; }
</style>

<div class="page-header">
    <div><h1>Rewards Module</h1><p>Earning-milestone rewards with image, badge, embed and visibility controls. Read-only against the existing earnings system.</p></div>
</div>

<div class="rw-tabs">
    <a href="/admin/rewards?tab=rules"  class="rw-tab <?= $tab==='rules'?'active':'' ?>">Reward Rules</a>
    <a href="/admin/rewards?tab=grants" class="rw-tab <?= $tab==='grants'?'active':'' ?>">Granted Rewards</a>
</div>

<?php if ($tab === 'rules'): ?>

<div class="rw-grid">
    <!-- ─────── Left column: Rule editor + Badge manager ─────── -->
    <div class="rw-editor">
        <div class="card">
            <div class="card-header"><span class="card-title">New / Edit Reward</span></div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="rwForm">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="submit_type" value="save_rule">
                    <input type="hidden" name="id" id="ruleId" value="">

                    <!-- Image with preview -->
                    <div class="form-group">
                        <label>Reward Image</label>
                        <div class="rw-img-prev" id="rwImgPrev"><div class="rw-empty">No image selected</div></div>
                        <input type="file" name="image" id="rwImageFile" accept="image/png,image/jpeg,image/gif,image/webp" class="form-control">
                        <label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#DC2626;margin-top:6px;display:none" id="rwClearWrap">
                            <input type="checkbox" name="clear_image" value="1" id="rwClearImg"> Remove current image
                        </label>
                        <div class="form-hint">PNG / JPG / GIF / WebP · max 4 MB · 16:9 looks best on cards</div>
                    </div>

                    <div class="form-group"><label>Title *</label><input type="text" name="title" id="ruleTitle" class="form-control" required maxlength="255"></div>

                    <div class="form-group"><label>Threshold (USD) *</label><input type="number" name="threshold_usd" id="ruleThreshold" step="0.01" min="0.01" class="form-control" required></div>

                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label>Reward Kind *</label>
                            <select name="kind" id="ruleKind" class="form-control">
                                <option value="cash">Cash payout</option>
                                <option value="bonus_credit">Bonus credit</option>
                                <option value="voucher">Voucher / code</option>
                                <option value="product">Product / gift</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Visibility</label>
                            <select name="visibility" id="ruleVisibility" class="form-control">
                                <option value="public">Public — everyone</option>
                                <option value="affiliate">Affiliate Only</option>
                                <option value="vip">VIP Only</option>
                                <option value="private">Private (admin draft)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row cols-2">
                        <div class="form-group"><label>Value Amount</label><input type="number" name="value_amount" id="ruleAmount" step="0.01" min="0" class="form-control" placeholder="e.g. 50.00"></div>
                        <div class="form-group"><label>Value Text</label><input type="text" name="value_text" id="ruleText" class="form-control" maxlength="255" placeholder="e.g. Bluetooth headphones"></div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <!-- Hidden textarea that holds the HTML value submitted with the form -->
                        <textarea name="description" id="ruleDesc" style="display:none"></textarea>
                        <!-- Quill toolbar + editor container -->
                        <div id="ruleDescQuillWrap" style="border:1px solid #D1D5DB;border-radius:6px;overflow:hidden;background:#fff">
                            <div id="ruleDescToolbar" style="border-bottom:1px solid #E5E5EB">
                                <span class="ql-formats">
                                    <select class="ql-header"><option value="1">H1</option><option value="2">H2</option><option value="3">H3</option><option value="">Normal</option></select>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-bold" title="Bold"></button>
                                    <button class="ql-italic" title="Italic"></button>
                                    <button class="ql-underline" title="Underline"></button>
                                    <button class="ql-strike" title="Strikethrough"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-list" value="ordered" title="Ordered list"></button>
                                    <button class="ql-list" value="bullet" title="Bullet list"></button>
                                </span>
                                <span class="ql-formats">
                                    <button class="ql-link" title="Link"></button>
                                    <button class="ql-blockquote" title="Blockquote"></button>
                                    <button class="ql-clean" title="Clear formatting"></button>
                                </span>
                                <span class="ql-formats">
                                    <select class="ql-color" title="Text colour"></select>
                                    <select class="ql-background" title="Background colour"></select>
                                </span>
                                <span class="ql-formats">
                                    <select class="ql-align"></select>
                                </span>
                            </div>
                            <div id="ruleDescEditor" style="min-height:120px;font-size:13.5px"></div>
                        </div>
                    </div>

                    <!-- Badge picker -->
                    <div class="form-group">
                        <label>Badge</label>
                        <div class="rw-badge-row" id="rwBadgeRow">
                            <button type="button" class="rw-badge-pick active" data-label="" data-color="">
                                <span class="rw-badge-chip" style="background:#94A3B8">None</span>
                            </button>
                            <?php foreach ($badges as $b): ?>
                            <button type="button" class="rw-badge-pick"
                                    data-label="<?= Helpers::e($b['label']) ?>"
                                    data-color="<?= Helpers::e($b['color']) ?>">
                                <span class="rw-badge-chip" style="background:<?= Helpers::e($b['color']) ?>"><?= Helpers::e($b['label']) ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-row cols-2" style="margin-top:8px">
                            <input type="text" name="badge_label" id="ruleBadgeLabel" class="form-control" placeholder="Badge label (override)" maxlength="60">
                            <input type="color" name="badge_color" id="ruleBadgeColor" class="form-control" value="#4F46E5" style="height:34px;padding:2px 4px">
                        </div>
                        <div class="form-hint">Pick a preset above or type a custom label + colour.</div>
                    </div>

                    <!-- Scheduling -->
                    <div class="form-row cols-2">
                        <div class="form-group"><label>Publish at <span style="font-weight:400;color:#94A3B8">(optional)</span></label><input type="datetime-local" name="publish_at" id="rulePublishAt" class="form-control"></div>
                        <div class="form-group"><label>Expires at <span style="font-weight:400;color:#94A3B8">(optional)</span></label><input type="datetime-local" name="expires_at" id="ruleExpiresAt" class="form-control"></div>
                    </div>

                    <!-- Embed code (sanitised at render time) -->
                    <div class="form-group">
                        <label>Embed Code (HTML) <span style="font-weight:400;color:#94A3B8">— optional</span></label>
                        <textarea name="embed_code" id="ruleEmbed" class="form-control" rows="4" maxlength="20000" placeholder='<iframe src="https://www.youtube.com/embed/..."></iframe>'></textarea>
                        <div class="form-hint">Rendered inside a sandboxed iframe on the affiliate side — scripts inside cannot reach your dashboard. 20 000-char max.</div>
                    </div>

                    <div class="form-row cols-2">
                        <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" id="ruleSortOrder" class="form-control" value="0"></div>
                        <div class="form-group" style="align-self:center"><label style="display:flex;align-items:center;gap:8px;margin-top:24px;font-weight:600"><input type="checkbox" name="active" id="ruleActive" value="1" checked> Active</label></div>
                    </div>

                    <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap">
                        <button class="btn btn-primary" type="submit">Save Reward</button>
                        <button class="btn btn-secondary" type="reset" onclick="resetForm()">Clear form</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Badge manager -->
        <div class="card mt-3">
            <div class="card-header"><span class="card-title">Custom Badges</span></div>
            <div class="card-body">
                <form method="POST" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="submit_type" value="save_badge">
                    <input type="text"  name="badge_new_label" class="form-control" placeholder="Badge label" maxlength="60" required style="flex:1 1 140px;min-width:0">
                    <input type="color" name="badge_new_color" class="form-control" value="#4F46E5" style="height:34px;width:48px;padding:2px 4px">
                    <button class="btn btn-secondary btn-sm" type="submit">+ Add badge</button>
                </form>
                <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:6px">
                    <?php foreach ($badges as $b): ?>
                    <span class="rw-badge-chip" style="background:<?= Helpers::e($b['color']) ?>;position:relative">
                        <?= Helpers::e($b['label']) ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Remove badge \'<?= Helpers::e($b['label']) ?>\'? Existing rewards keep the label as a snapshot.')">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="submit_type" value="delete_badge">
                            <input type="hidden" name="label" value="<?= Helpers::e($b['label']) ?>">
                            <button type="submit" style="background:transparent;border:none;color:rgba(255,255,255,.85);cursor:pointer;font-size:13px;padding:0 0 0 4px;line-height:1">&times;</button>
                        </form>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ─────── Right column: existing rules table ─────── -->
    <div>
        <form class="rw-filter" method="GET">
            <input type="hidden" name="tab" value="rules">
            <input type="text" name="q" class="grow" placeholder="Search title / description / badge…" value="<?= Helpers::e($filters['search']) ?>">
            <select name="kind">
                <option value="">All kinds</option>
                <?php foreach (['cash','bonus_credit','voucher','product'] as $k): ?>
                <option value="<?= $k ?>" <?= $filters['kind']===$k?'selected':'' ?>><?= ucfirst(str_replace('_',' ', $k)) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="vis">
                <option value="">All visibility</option>
                <?php foreach (['public','affiliate','vip','private'] as $v): ?>
                <option value="<?= $v ?>" <?= $filters['visibility']===$v?'selected':'' ?>><?= ucfirst($v) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="active">
                <option value="">Any status</option>
                <option value="1" <?= $filters['active']==='1'?'selected':'' ?>>Active</option>
                <option value="0" <?= $filters['active']==='0'?'selected':'' ?>>Disabled</option>
            </select>
            <button class="btn btn-primary btn-sm" type="submit">Filter</button>
            <?php if ($filters['search']||$filters['kind']||$filters['visibility']||$filters['active']!==''): ?>
            <a href="/admin/rewards" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </form>

        <form method="POST" id="rwOrderForm">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="save_order">
            <div class="rw-table-wrap">
                <table class="rw-table">
                    <thead>
                        <tr>
                            <th style="width:30px"></th>
                            <th>Reward</th>
                            <th>Visibility</th>
                            <th>Threshold</th>
                            <th>Reward Kind</th>
                            <th>Active</th>
                            <th style="white-space:nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rwSortBody">
                    <?php foreach ($rules as $r):
                        $valLabel = ($r['kind'] === 'cash' || $r['kind'] === 'bonus_credit')
                            ? '$' . number_format((float)$r['value_amount'], 2)
                            : (string)$r['value_text'];
                        $bg = $r['badge_color'] ?: '#94A3B8';
                        $cleanDesc = trim(strip_tags(html_entity_decode((string)($r['description'] ?? ''))));
                    ?>
                    <tr data-rid="<?= (int)$r['id'] ?>">
                        <td class="rw-td-top">
                            <span class="rw-handle" title="Drag to reorder">≡</span>
                            <input type="hidden" name="order[]" value="<?= (int)$r['id'] ?>">
                            <div class="rw-thumb"<?= !empty($r['image_path']) ? ' style="background-image:url(\'' . Helpers::e($r['image_path']) . '\')"' : '' ?>>
                                <?= empty($r['image_path']) ? '🎁' : '' ?>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div style="font-weight:700;line-height:1.25;color:#0F172A"><?= Helpers::e($r['title']) ?></div>
                                <?php if ($cleanDesc !== ''): ?>
                                <div style="font-size:11.5px;color:#64748B;margin-top:2px"><?= Helpers::e(mb_strimwidth($cleanDesc, 0, 80, '…')) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($r['badge_label'])): ?>
                                <span class="rw-badge-chip" style="background:<?= Helpers::e($bg) ?>;margin-top:4px"><?= Helpers::e($r['badge_label']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td data-label="Visibility"><span class="rw-vis-tag rw-vis-<?= Helpers::e($r['visibility'] ?? 'public') ?>"><?= Helpers::e($r['visibility'] ?? 'public') ?></span></td>
                        <td data-label="Threshold">$<?= number_format((float)$r['threshold_usd'], 2) ?></td>
                        <td data-label="Reward">
                            <div>
                                <span class="badge badge-info"><?= Helpers::e($r['kind']) ?></span>
                                <span style="font-size:11.5px;color:#64748B;margin-left:4px"><?= Helpers::e($valLabel) ?></span>
                            </div>
                        </td>
                        <td data-label="Active">
                            <form method="POST" style="display:inline">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="submit_type" value="toggle_active">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <label class="rw-toggle">
                                    <input type="checkbox" <?= (int)$r['active']===1?'checked':'' ?> onchange="this.form.submit()">
                                    <span class="t"><span class="k"></span></span>
                                </label>
                            </form>
                        </td>
                        <td data-label="Actions" class="rw-mini-actions" style="white-space:nowrap">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="editRule(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">Edit</button>
                            <form method="POST">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="submit_type" value="duplicate_rule">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="btn btn-secondary btn-sm" type="submit" title="Duplicate">⧉</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this rule? Existing grants are kept.')">
                                <?= Helpers::csrf() ?>
                                <input type="hidden" name="submit_type" value="delete_rule">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit" title="Delete">×</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rules)): ?>
                    <tr><td colspan="7" class="text-center text-muted" style="padding:32px">No rules match the current filter.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:10px;display:flex;justify-content:flex-end">
                <button class="btn btn-secondary btn-sm" type="submit">Save order</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Editor → populate from row click ────────────────────────────────────
function editRule(r){
    document.getElementById('ruleId').value         = r.id;
    document.getElementById('ruleTitle').value      = r.title || '';
    document.getElementById('ruleThreshold').value  = r.threshold_usd;
    document.getElementById('ruleKind').value       = r.kind;
    document.getElementById('ruleVisibility').value = r.visibility || 'public';
    document.getElementById('ruleAmount').value     = r.value_amount || '';
    document.getElementById('ruleText').value       = r.value_text   || '';
    if (window._rwQuill) { window._rwQuill.root.innerHTML = r.description || ''; document.getElementById('ruleDesc').value = r.description || ''; } else { document.getElementById('ruleDesc').value = r.description || ''; }
    document.getElementById('ruleEmbed').value      = r.embed_code   || '';
    document.getElementById('ruleBadgeLabel').value = r.badge_label  || '';
    document.getElementById('ruleBadgeColor').value = r.badge_color  || '#4F46E5';
    document.getElementById('rulePublishAt').value  = (r.publish_at || '').replace(' ', 'T').slice(0,16);
    document.getElementById('ruleExpiresAt').value  = (r.expires_at || '').replace(' ', 'T').slice(0,16);
    document.getElementById('ruleSortOrder').value  = r.sort_order || 0;
    document.getElementById('ruleActive').checked   = +r.active === 1;
    // Image preview
    var prev = document.getElementById('rwImgPrev');
    var clearWrap = document.getElementById('rwClearWrap');
    if (r.image_path) {
        prev.innerHTML = '<img src="' + r.image_path + '" alt="">';
        clearWrap.style.display = 'inline-flex';
    } else {
        prev.innerHTML = '<div class="rw-empty">No image selected</div>';
        clearWrap.style.display = 'none';
    }
    document.getElementById('rwClearImg').checked = false;
    // Sync the badge-picker row
    syncBadgePick(r.badge_label || '');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function resetForm(){
    document.getElementById('ruleId').value = '';
    document.getElementById('rwImgPrev').innerHTML = '<div class="rw-empty">No image selected</div>';
    if (window._rwQuill) { window._rwQuill.setText(''); document.getElementById('ruleDesc').value = ''; }
    document.getElementById('rwClearWrap').style.display = 'none';
}

// ── Image preview when picking a new file ──────────────────────────────
document.getElementById('rwImageFile').addEventListener('change', function(e){
    var f = e.target.files && e.target.files[0]; if (!f) return;
    var url = URL.createObjectURL(f);
    document.getElementById('rwImgPrev').innerHTML = '<img src="' + url + '" alt="">';
    document.getElementById('rwClearWrap').style.display = 'inline-flex';
});

// ── Badge picker → fills label + colour ────────────────────────────────
function syncBadgePick(label){
    document.querySelectorAll('.rw-badge-pick').forEach(function(btn){
        btn.classList.toggle('active', btn.dataset.label === (label || ''));
    });
}
document.querySelectorAll('.rw-badge-pick').forEach(function(btn){
    btn.addEventListener('click', function(){
        var l = btn.dataset.label || '';
        var c = btn.dataset.color || '#4F46E5';
        document.getElementById('ruleBadgeLabel').value = l;
        document.getElementById('ruleBadgeColor').value = c || '#4F46E5';
        syncBadgePick(l);
    });
});

// ── Drag-and-drop reordering (no libraries) ────────────────────────────
(function(){
    var tbody = document.getElementById('rwSortBody');
    if (!tbody) return;
    var dragRow = null;
    tbody.querySelectorAll('tr').forEach(function(tr){
        tr.setAttribute('draggable', 'true');
        tr.addEventListener('dragstart', function(){ dragRow = tr; tr.classList.add('dragging'); });
        tr.addEventListener('dragend',   function(){ if (dragRow) dragRow.classList.remove('dragging'); dragRow = null; });
        tr.addEventListener('dragover',  function(e){
            e.preventDefault();
            if (!dragRow || dragRow === tr) return;
            var rect = tr.getBoundingClientRect();
            var after = (e.clientY - rect.top) > rect.height / 2;
            tr.parentNode.insertBefore(dragRow, after ? tr.nextSibling : tr);
        });
    });
})();
</script>

<?php else: /* tab === 'grants' */ ?>

<div class="card">
    <div class="card-header"><span class="card-title">Granted Rewards</span></div>
    <div class="table-wrap" style="overflow-x:auto">
        <table>
            <thead><tr><th>Granted</th><th>Affiliate</th><th>Reward</th><th class="text-right">Threshold</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($grants as $g): ?>
            <tr>
                <td class="text-muted text-sm"><?= Helpers::e(date('M j H:i', strtotime((string)$g['granted_at']))) ?></td>
                <td><?= Helpers::e($g['aff_name'] ?: ('#' . $g['affiliate_id'])) ?></td>
                <td>
                    <div><strong><?= Helpers::e($g['title_snapshot']) ?></strong></div>
                    <div class="text-muted" style="font-size:11px">
                        <?= Helpers::e($g['kind']) ?>
                        <?= $g['value_amount'] !== null ? ' · $' . number_format((float)$g['value_amount'], 2) : '' ?>
                        <?= !empty($g['value_text']) ? ' · ' . Helpers::e($g['value_text']) : '' ?>
                    </div>
                </td>
                <td class="text-right">$<?= number_format((float)$g['lifetime_at_grant'], 2) ?></td>
                <td>
                    <?php $b = ['granted'=>'warning','claimed'=>'info','paid'=>'success','cancelled'=>'danger'][$g['status']] ?? 'muted'; ?>
                    <span class="badge badge-<?= $b ?>"><?= Helpers::e($g['status']) ?></span>
                </td>
                <td>
                    <details>
                        <summary class="btn btn-secondary btn-sm" style="cursor:pointer">Update</summary>
                        <form method="POST" style="margin-top:8px;background:#F8FAFC;padding:10px;border-radius:6px;min-width:240px">
                            <?= Helpers::csrf() ?>
                            <input type="hidden" name="submit_type" value="update_grant">
                            <input type="hidden" name="grant_id" value="<?= (int)$g['id'] ?>">
                            <select name="status" class="form-control" style="margin-bottom:6px">
                                <?php foreach (['granted','claimed','paid','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $g['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="admin_note" class="form-control" rows="2" placeholder="Note" style="margin-bottom:6px"><?= Helpers::e($g['admin_note'] ?? '') ?></textarea>
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                        </form>
                    </details>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($grants)): ?>
            <tr><td colspan="6" class="text-center text-muted" style="padding:32px">No rewards granted yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>

<script>
(function(){
    function initQuill() {
        if (typeof Quill === 'undefined') { setTimeout(initQuill, 50); return; }
        window._rwQuill = new Quill('#ruleDescEditor', {
            theme: 'snow',
            modules: {
                toolbar: '#ruleDescToolbar'
            },
            placeholder: 'Write a description for this reward…'
        });
        // Sync to hidden textarea on every change
        window._rwQuill.on('text-change', function(){
            document.getElementById('ruleDesc').value = window._rwQuill.root.innerHTML;
        });
        // Sync before form submit
        var form = document.getElementById('rwForm');
        if (form) {
            form.addEventListener('submit', function(){
                document.getElementById('ruleDesc').value = window._rwQuill.root.innerHTML;
            });
        }
    }
    // Init after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQuill);
    } else {
        initQuill();
    }
})();
</script>
