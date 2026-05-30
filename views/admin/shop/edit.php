<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<!-- Quill rich-text editor -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>

<style>
/* Quill editor container styling */
#quill-description-wrap {
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}
#quill-description-wrap .ql-toolbar {
    border: none;
    border-bottom: 1px solid #E2E8F0;
    background: #F8FAFC;
    padding: 8px 10px;
}
#quill-description-wrap .ql-container {
    border: none;
    font-size: 13.5px;
    font-family: inherit;
    min-height: 120px;
}
#quill-description-wrap .ql-editor {
    min-height: 120px;
    padding: 10px 14px;
    color: #0F172A;
    line-height: 1.6;
}
#quill-description-wrap .ql-editor.ql-blank::before {
    color: #94A3B8;
    font-style: normal;
}
#quill-description-wrap:focus-within {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
}
.ql-toolbar .ql-formats { margin-right: 10px; }
</style>

<div class="page-header">
    <div><h1><?= $product ? 'Edit Product' : 'New Product' ?></h1></div>
    <a href="/admin/shop" class="btn btn-secondary">← Back</a>
</div>

<div class="card" style="max-width:760px">
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="shopProductForm">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="save_product">
            <?php if ($product): ?>
            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= Helpers::e($product['name'] ?? '') ?>">
            </div>

            <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-group">
                    <label>Price (points) *</label>
                    <input type="number" name="price_points" class="form-control" min="1" required value="<?= Helpers::e((string)($product['price_points'] ?? 100)) ?>">
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" class="form-control" value="<?= Helpers::e((string)($product['stock'] ?? -1)) ?>">
                    <div class="form-hint">-1 = unlimited</div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['active','draft','archived'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($product['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Description — rich text editor (Quill) -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:6px">
                    Description
                    <span style="font-size:11px;color:#94A3B8;font-weight:400;background:#F1F5F9;padding:2px 7px;border-radius:99px">Rich Text</span>
                </label>
                <!-- Hidden textarea that gets submitted -->
                <textarea name="description" id="descriptionHidden" style="display:none"><?= Helpers::e($product['description'] ?? '') ?></textarea>
                <!-- Quill editor mount point -->
                <div id="quill-description-wrap">
                    <div id="quill-editor"></div>
                </div>
                <div class="form-hint" style="margin-top:6px">Supports <strong>bold</strong>, <em>italic</em>, lists, links, and more. Shown on the product card affiliates see.</div>
            </div>

            <div class="form-group">
                <label>Image</label>
                <?php if (!empty($product['image_path'])): ?>
                <div style="margin-bottom:8px"><img src="<?= Helpers::e($product['image_path']) ?>" alt="" style="max-height:80px;border-radius:6px;border:1px solid #E2E8F0"></div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/png,image/jpeg,image/gif,image/webp">
                <div class="form-hint">PNG, JPG, GIF, WebP — max 4 MB. Leave empty to keep existing.</div>
            </div>

            <button class="btn btn-primary" type="submit">Save Product</button>
        </form>
    </div>
</div>

<script>
(function () {
    // Existing saved description (plain text from DB — preserve as-is in editor)
    var savedDesc = <?= json_encode($product['description'] ?? '') ?>;

    // Init Quill with a focused toolbar for product descriptions
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Short summary the affiliate sees on the product card…',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });

    // Load existing content — try as HTML first (if it looks like HTML), else plain text
    if (savedDesc) {
        if (savedDesc.trim().startsWith('<')) {
            quill.root.innerHTML = savedDesc;
        } else {
            quill.setText(savedDesc);
        }
    }

    // On form submit, copy editor HTML into hidden textarea
    document.getElementById('shopProductForm').addEventListener('submit', function () {
        var html = quill.root.innerHTML;
        // If editor is empty Quill returns '<p><br></p>' — clear it
        if (html === '<p><br></p>') html = '';
        document.getElementById('descriptionHidden').value = html;
    });
})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
