<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>Edit Template: <?= Helpers::e($tpl['label']) ?></h1>
        <p>Event: <code><?= Helpers::e($tpl['event_type']) ?></code></p>
    </div>
    <a href="/admin/email?action=templates" class="btn btn-secondary">← Back</a>
</div>

<div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#1E40AF">
    <strong>Placeholders:</strong>
    <?php
    $placeholders = [
        'affiliate_created'       => ['name','email','site_name','app_url','affiliate_code'],
        'affiliate_approved'      => ['name','email','site_name','app_url'],
        'affiliate_rejected'      => ['name','email','site_name','app_url'],
        'affiliate_suspended'     => ['name','email','site_name','app_url'],
        'invoice_created'         => ['name','email','site_name','app_url','invoice_number','total','due_date'],
        'invoice_paid'            => ['name','email','site_name','app_url','invoice_number','total'],
        'offer_approval_requested'=> ['name','email','site_name','app_url','offer_name','affiliate_id'],
        'offer_approved'          => ['name','email','site_name','app_url','offer_name'],
    ];
    $available = $placeholders[$tpl['event_type']] ?? ['name','email','site_name','app_url'];
    foreach ($available as $ph):
    ?><code style="background:#DBEAFE;padding:2px 6px;border-radius:4px;margin:0 3px;cursor:pointer" onclick="insertPh('{{<?= $ph ?>}}')">{{<?= $ph ?>}}</code><?php endforeach; ?>
    <span style="margin-left:10px;color:#64748B">Click to insert at cursor</span>
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Template Editor</span></div>
    <div class="card-body">
        <form method="POST" id="tplForm">
            <?= Helpers::csrf() ?>

            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" class="form-control" id="subjectInput"
                       value="<?= Helpers::e($tpl['subject']) ?>" required>
                <div class="form-hint">Use placeholders like <code>{{name}}</code>, <code>{{site_name}}</code></div>
            </div>

            <div class="form-group">
                <label>HTML Body</label>
                <div style="display:flex;gap:8px;margin-bottom:8px">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="togglePreview()">&#128065; Preview</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertTag('<strong>','</strong>')">B</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertTag('<em>','</em>')">I</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertTag('<a href=\'\'>','</a>')">Link</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertTag('<br>','')">BR</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertTag('<p>','</p>')">P</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertTag('<h2>','</h2>')">H2</button>
                </div>
                <textarea name="html_body" id="htmlBodyInput" class="form-control" rows="20"
                          style="font-family:monospace;font-size:13px"><?= Helpers::e($tpl['html_body']) ?></textarea>
            </div>

            <div id="previewPanel" style="display:none;margin-bottom:20px">
                <label style="font-weight:600;margin-bottom:8px;display:block">Preview</label>
                <div id="previewFrame" style="border:1px solid #E2E8F0;border-radius:8px;padding:24px;background:#fff;min-height:120px"></div>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="is_active" value="1" <?= $tpl['is_active'] ? 'checked' : '' ?>>
                    <span>Template Active (emails will be sent for this event)</span>
                </label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Template</button>
                <a href="/admin/email?action=templates" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
var lastFocus = null;

document.getElementById('subjectInput').addEventListener('focus', function(){ lastFocus = this; });
document.getElementById('htmlBodyInput').addEventListener('focus', function(){ lastFocus = this; });

function insertPh(ph) {
    var el = lastFocus || document.getElementById('htmlBodyInput');
    var s = el.selectionStart, e = el.selectionEnd;
    el.value = el.value.substring(0, s) + ph + el.value.substring(e);
    el.selectionStart = el.selectionEnd = s + ph.length;
    el.focus();
}

function insertTag(open, close) {
    var el = document.getElementById('htmlBodyInput');
    el.focus();
    var s = el.selectionStart, e = el.selectionEnd;
    var sel = el.value.substring(s, e);
    var ins = open + sel + close;
    el.value = el.value.substring(0, s) + ins + el.value.substring(e);
    el.selectionStart = s + open.length;
    el.selectionEnd   = s + open.length + sel.length;
}

function togglePreview() {
    var panel = document.getElementById('previewPanel');
    var frame = document.getElementById('previewFrame');
    if (panel.style.display === 'none') {
        frame.innerHTML = document.getElementById('htmlBodyInput').value;
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
