<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div><h1>Promotional Email Blast</h1><p>Send a custom email to your affiliates</p></div>
    <div class="d-flex gap-2">
        <a href="/admin/email?action=templates" class="btn btn-secondary">Templates</a>
        <a href="/admin/email?action=logs"      class="btn btn-secondary">&#128196; Logs</a>
    </div>
</div>

<!-- Tab nav -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #E2E8F0;padding-bottom:0">
    <a href="/admin/email?action=templates" style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:#64748B">Templates</a>
    <a href="/admin/email?action=blast"     style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid #4F46E5;margin-bottom:-2px;color:#4F46E5">Promotional Blast</a>
    <a href="/admin/email?action=logs"      style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;color:#64748B">Logs</a>
</div>

<div style="background:#FEF3C7;border:1px solid #FCD34D;border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#92400E">
    <strong>&#9888; Note:</strong> Emails are sent synchronously. Sending to many affiliates may take a while. Make sure SMTP is configured in Settings → Email.
</div>

<div class="card">
    <div class="card-header"><span class="card-title">Compose Blast Email</span></div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" onsubmit="return confirmBlast()">
            <?= Helpers::csrf() ?>

            <div class="form-group">
                <label>Recipients</label>
                <div style="display:flex;gap:16px;margin-bottom:12px">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="radio" name="targets" value="all" id="targAll" checked onchange="toggleList(false)">
                        <span>All Active Affiliates (<?= count($affiliates) ?>)</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="radio" name="targets" value="selected" id="targSel" onchange="toggleList(true)">
                        <span>Selected Affiliates</span>
                    </label>
                </div>
                <div id="affiliateList" style="display:none;border:1px solid #E2E8F0;border-radius:8px;padding:12px;max-height:220px;overflow-y:auto;background:#F8FAFC">
                    <div style="margin-bottom:8px">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="checkAll(true)">Select All</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="checkAll(false)">Deselect All</button>
                        <input type="text" id="searchAff" placeholder="Search affiliates…" class="form-control" style="display:inline-block;width:200px;margin-left:8px" oninput="filterAff(this.value)">
                    </div>
                    <div id="affCheckboxes">
                    <?php foreach ($affiliates as $aff): ?>
                    <label style="display:flex;align-items:center;gap:8px;padding:4px 0;cursor:pointer;font-size:13px" class="aff-row">
                        <input type="checkbox" name="affiliate_ids[]" value="<?= $aff['id'] ?>" class="aff-cb">
                        <span class="aff-name"><?= Helpers::e($aff['first_name'] . ' ' . $aff['last_name']) ?></span>
                        <span style="color:#94A3B8;font-size:12px"><?= Helpers::e($aff['email']) ?></span>
                    </label>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" class="form-control" required placeholder="Your subject here…">
                <div class="form-hint">You can use <code>{{name}}</code>, <code>{{site_name}}</code>, <code>{{app_url}}</code></div>
            </div>

            <div class="form-group">
                <label>Banner Image (optional)</label>
                <input type="file" name="blast_image" class="form-control" accept="image/*">
                <div class="form-hint">Will be placed above your email content. Max 2MB.</div>
            </div>

            <div class="form-group">
                <label>Email Body (HTML)</label>
                <div style="display:flex;gap:8px;margin-bottom:8px">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="blastPreview()">&#128065; Preview</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertBlastTag('<strong>','</strong>')">B</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertBlastTag('<em>','</em>')">I</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertBlastTag('<a href=\'\'>','</a>')">Link</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertBlastTag('<br>','')">BR</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertBlastTag('<p>','</p>')">P</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertBlastTag('<h2>','</h2>')">H2</button>
                </div>
                <textarea name="html_body" id="blastBody" class="form-control" rows="18"
                          style="font-family:monospace;font-size:13px" required
                          placeholder="<h2>Hello {{name}},</h2>&lt;p&gt;Your message here...&lt;/p&gt;"></textarea>
            </div>

            <div id="blastPreviewPanel" style="display:none;margin-bottom:20px">
                <label style="font-weight:600;margin-bottom:8px;display:block">Preview</label>
                <div id="blastPreviewFrame" style="border:1px solid #E2E8F0;border-radius:8px;padding:24px;background:#fff;min-height:120px"></div>
            </div>

            <button type="submit" class="btn btn-primary" id="sendBtn">&#128231; Send Email Blast</button>
        </form>
    </div>
</div>

<script>
function toggleList(show) {
    document.getElementById('affiliateList').style.display = show ? 'block' : 'none';
}
function checkAll(state) {
    document.querySelectorAll('.aff-cb').forEach(function(cb){ cb.checked = state; });
}
function filterAff(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.aff-row').forEach(function(row){
        var name = row.querySelector('.aff-name').textContent.toLowerCase();
        var email = row.querySelector('span:last-child').textContent.toLowerCase();
        row.style.display = (name.includes(q) || email.includes(q)) ? '' : 'none';
    });
}
function blastPreview() {
    var panel = document.getElementById('blastPreviewPanel');
    var frame = document.getElementById('blastPreviewFrame');
    if (panel.style.display === 'none') {
        frame.innerHTML = document.getElementById('blastBody').value;
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
}
function insertBlastTag(open, close) {
    var el = document.getElementById('blastBody');
    el.focus();
    var s = el.selectionStart, e = el.selectionEnd;
    var sel = el.value.substring(s, e);
    var ins = open + sel + close;
    el.value = el.value.substring(0, s) + ins + el.value.substring(e);
    el.selectionStart = s + open.length;
    el.selectionEnd   = s + open.length + sel.length;
}
function confirmBlast() {
    var isSel = document.getElementById('targSel').checked;
    var cnt = isSel ? document.querySelectorAll('.aff-cb:checked').length : <?= count($affiliates) ?>;
    return confirm('Send this email to ' + cnt + ' affiliate(s)?');
}
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
