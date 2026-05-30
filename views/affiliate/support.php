<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128172; Live Support</h1>
        <p><?php if($managerInfo): ?>Chat with <?= Helpers::e($managerInfo['first_name'].' '.$managerInfo['last_name']) ?><?php else: ?>Chat with Support Team<?php endif; ?></p>
    </div>
</div>

<script>var _csrfToken = '<?= Auth::generateCsrf() ?>';</script>

<style>
.aff-msg-row{display:flex;flex-direction:column;position:relative}
.aff-msg-row.mine{align-items:flex-end}
.aff-msg-bubble{max-width:75%;padding:10px 14px;border-radius:14px 14px 14px 4px;background:#fff;color:#1E293B;font-size:13px;line-height:1.5;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #E2E8F0;word-wrap:break-word}
.aff-msg-row.mine .aff-msg-bubble{background:#4F46E5;color:#fff;border:none;border-radius:14px 14px 4px 14px}
.aff-msg-actions{display:none;gap:4px;background:#fff;border:1px solid #E2E8F0;border-radius:18px;padding:3px 6px;box-shadow:0 2px 6px rgba(0,0,0,.06);margin-top:3px}
.aff-msg-row:hover .aff-msg-actions{display:inline-flex}
.aff-msg-action-btn{background:none;border:none;font-size:13px;color:#64748B;cursor:pointer;padding:2px 5px;border-radius:50%}
.aff-msg-action-btn:hover{background:#F1F5F9;color:#0F172A}
.aff-att-img{max-width:240px;max-height:240px;border-radius:8px;border:1px solid #E2E8F0;cursor:pointer;display:block;margin-top:4px}
.aff-att-file{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.24);border-radius:8px;font-size:12px;color:#fff;text-decoration:none;margin-top:4px}
.aff-msg-row.theirs .aff-att-file{background:#F8FAFC;border-color:#E2E8F0;color:#1E293B}
.aff-att-icon{width:28px;height:28px;background:rgba(255,255,255,.18);border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0}
.aff-msg-row.theirs .aff-att-icon{background:#EEF2FF;color:#4F46E5}
.aff-edited{font-size:10px;color:#94A3B8;font-style:italic;margin-left:6px}
.aff-msg-row.mine .aff-edited{color:#E0E7FF}
.aff-status-banner{padding:10px 16px;border-radius:8px;font-size:12px;font-weight:600;margin-bottom:12px;text-align:center}
.aff-status-banner.closed{background:#FEF3C7;color:#92400E;border:1px solid #FDE68A}
@media (max-width:640px){ .aff-att-img{max-width:180px;max-height:180px} }
</style>

<div class="card" style="display:flex;flex-direction:column;height:calc(100vh - 220px);min-height:400px;max-height:700px">
    <!-- Chat Header -->
    <div id="chat-header" style="display:flex;justify-content:flex-end;padding:10px 20px;border-bottom:1px solid var(--border);background:#F8FAFC">
        <div style="display:flex;gap:8px;align-items:center">
            <span style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;letter-spacing:0.5px">Translate Chat:</span>
            <div style="display:flex;align-items:center;background:#fff;border:1px solid #CBD5E1;border-radius:6px;overflow:hidden;height:30px">
                <select id="chat-read-translate-lang" style="background:none;border:none;outline:none;font-size:12px;padding:0 6px;color:#475569;font-weight:600;cursor:pointer">
                    <option value="en">EN</option>
                    <option value="bn">BN</option>
                    <option value="hi">HI</option>
                    <option value="ur">UR</option>
                    <option value="es">ES</option>
                    <option value="fr">FR</option>
                    <option value="ar">AR</option>
                    <option value="ru">RU</option>
                </select>
                <button type="button" id="chat-read-translate-btn" onclick="translateAllMessages()" style="background:#F1F5F9;border:none;border-left:1px solid #CBD5E1;height:100%;padding:0 12px;font-size:12px;color:#0F172A;cursor:pointer;font-weight:600" title="Translate all messages">Translate</button>
            </div>
        </div>
    </div>

    <!-- Messages Area -->
    <div id="chat-messages" style="flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:12px;background:#FAFBFC">
        <div id="chat-loading" style="text-align:center;color:var(--text-muted);padding:40px">Loading messages...</div>
    </div>

    <!-- Input -->
    <div style="padding:14px 20px;border-top:1px solid var(--border);background:#fff;border-radius:0 0 8px 8px">
        <div id="att-preview" style="display:none;font-size:12px;color:#475569;background:#F1F5F9;border:1px solid #E2E8F0;border-radius:8px;padding:6px 10px;margin-bottom:8px"></div>
        <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
            <label class="btn btn-secondary" title="Attach file (JPG, PNG, WEBP, PDF, CSV — max 10 MB)" style="height:52px;padding:0 14px;display:flex;align-items:center;cursor:pointer;margin:0">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                <input type="file" id="chat-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.csv,image/jpeg,image/png,image/webp,application/pdf,text/csv" style="display:none" onchange="prepareUpload(this)">
            </label>
            
            <div style="display:flex;align-items:center;background:#F1F5F9;border:1px solid #CBD5E1;border-radius:8px;overflow:hidden;height:52px">
                <select id="chat-translate-lang" style="background:none;border:none;outline:none;font-size:12px;padding:0 6px;color:#475569;font-weight:600;cursor:pointer">
                    <option value="en">EN</option>
                    <option value="bn">BN</option>
                    <option value="hi">HI</option>
                    <option value="ur">UR</option>
                    <option value="es">ES</option>
                    <option value="fr">FR</option>
                    <option value="ar">AR</option>
                    <option value="ru">RU</option>
                </select>
                <button type="button" id="chat-translate-btn" onclick="translateText()" style="background:#E2E8F0;border:none;border-left:1px solid #CBD5E1;height:100%;padding:0 12px;font-size:13px;color:#0F172A;cursor:pointer;font-weight:600" title="Translate text">A&rarr;あ</button>
            </div>

            <textarea id="chat-input" class="form-control" rows="2" placeholder="Type your message..." style="flex:1;resize:none;font-size:13px;line-height:1.5" onkeydown="chatKeyDown(event)"></textarea>
            <button onclick="sendMessage()" class="btn btn-primary" style="height:52px;padding:0 20px;white-space:nowrap">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Send
            </button>
        </div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Press Ctrl+Enter to send · Attachments: JPG, PNG, WEBP, PDF, CSV (max 10 MB)</div>
    </div>
</div>

<script>
var _affId    = <?= (int)$affId ?>;
var _lastId   = 0;
var _polling  = null;
var _myUserId = 0;
var _attPending = null;

function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmtBytes(b){ b=+b||0; if(b<1024)return b+' B'; if(b<1048576)return (b/1024).toFixed(1)+' KB'; return (b/1048576).toFixed(1)+' MB'; }

// Pick a short, friendly extension label from the filename first (more
// reliable than the mime type, which can be 'application/vnd.ms-excel' etc.),
// falling back to a "FILE" pill so a bubble is never empty when an attachment exists.
function attExtLabel(m){
    var nm  = String(m.attachment_name || '');
    var dot = nm.lastIndexOf('.');
    if (dot > -1 && dot < nm.length - 1) {
        return nm.substring(dot + 1).toUpperCase().substr(0, 4);
    }
    var t = String(m.attachment_type || '');
    if (t.indexOf('pdf') > -1)        return 'PDF';
    if (t.indexOf('csv') > -1)        return 'CSV';
    if (t.indexOf('image/') === 0)    return (t.split('/').pop() || 'IMG').toUpperCase().substr(0,4);
    if (t.indexOf('plain') > -1)      return 'TXT';
    return 'FILE';
}
function attachmentHtml(m){
    if (!m.attachment_path) return '';
    var name = escHtml(m.attachment_name || 'file');
    var dl   = '/api/chat?action=download&id=' + m.id;
    if ((m.attachment_type||'').indexOf('image/') === 0) {
        return '<a href="'+dl+'" target="_blank"><img class="aff-att-img" src="'+dl+'" alt="'+name+'" onerror="this.style.display=\'none\';this.insertAdjacentHTML(\'afterend\',\'<span style=\\\'font-size:11px;opacity:.7\\\'>Preview unavailable</span>\');"></a>';
    }
    var ext = attExtLabel(m);
    var sizeTxt = m.attachment_size ? fmtBytes(m.attachment_size) : '';
    return '<a class="aff-att-file" href="'+dl+'" target="_blank" download>'+
           '<span class="aff-att-icon">'+escHtml(ext)+'</span>'+
           '<span>'+name+(sizeTxt?'<span style="display:block;font-size:11px;opacity:.7">'+escHtml(sizeTxt)+'</span>':'')+'</span></a>';
}

function renderMsg(m) {
    var isMine = m.sender_role === 'affiliate';
    var time   = new Date(m.created_at.replace(' ','T')).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    var div    = document.createElement('div');
    div.className = 'aff-msg-row ' + (isMine ? 'mine' : 'theirs');
    div.dataset.id = m.id;
    var edited = m.edited_at ? '<span class="aff-edited">(edited)</span>' : '';
    // Affiliates can edit their own text messages (not attachments).
    var canEdit = isMine && (m.message||'').trim() !== '';
    div.innerHTML =
        '<div style="font-size:10px;color:#94A3B8;margin-bottom:3px">'+
            (isMine ? 'You' : escHtml(m.sender_name))+' · '+time+ edited +
        '</div>'+
        '<div class="aff-msg-bubble" id="bubble-'+m.id+'">'+
            (m.message ? '<div class="aff-msg-text" data-orig="'+escHtml(m.message)+'">'+escHtml(m.message).replace(/\n/g,'<br>')+'</div>' : '')+
            attachmentHtml(m)+
        '</div>'+
        (canEdit
            ? '<div class="aff-msg-actions"><button class="aff-msg-action-btn" title="Edit" onclick="editMsg('+m.id+')">&#9998;</button></div>'
            : '');
    return div;
}

function loadMessages(since) {
    fetch('/api/chat?action=messages&affiliate_id='+_affId+(since?'&since='+since:''))
    .then(function(r){return r.json();})
    .then(function(data){
        var box = document.getElementById('chat-messages');
        var loadEl = document.getElementById('chat-loading'); if (loadEl) loadEl.style.display = 'none';
        if (data.my_user_id) _myUserId = data.my_user_id;
        if (!since && data.conversation && data.conversation.status === 'closed') {
            // Closed banner. (Affiliates with a closed conversation will get a NEW
            // ticket the moment they send their next message — handled server-side.)
            box.insertAdjacentHTML('afterbegin',
                '<div class="aff-status-banner closed">&#10003; This support conversation has been marked as Solved. ' +
                'Send a new message below to start a new ticket.</div>');
        }
        if (!data.messages || !data.messages.length) {
            if (!_lastId && !box.querySelector('.empty-chat-msg')) {
                box.insertAdjacentHTML('beforeend', 
                    '<div class="aff-msg-row theirs empty-chat-msg" style="margin-bottom:12px">' +
                    '<div style="font-size:10px;color:#94A3B8;margin-bottom:3px">Support Team</div>' +
                    '<div class="aff-msg-bubble">Hello <?= Helpers::e(Auth::currentUser()['first_name'] ?? 'Affiliate') ?>,<br><br>Welcome to AffsCash Live Chat Support. We’re here to help you.<br>How can I assist you today?</div>' +
                    '</div>'
                );
            }
            return;
        }
        var atBottom = box.scrollHeight - box.scrollTop <= box.clientHeight + 60;
        data.messages.forEach(function(m){
            var existing = box.querySelector('[data-id="'+m.id+'"]');
            if (existing) existing.replaceWith(renderMsg(m));
            else          box.appendChild(renderMsg(m));
            if (m.id > _lastId) _lastId = m.id;
        });
        if (!since || atBottom) box.scrollTop = box.scrollHeight;
    });
}

function prepareUpload(input){
    var file = input.files && input.files[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) { alert('File is larger than 10 MB.'); input.value=''; return; }
    var fd = new FormData();
    fd.append('_token', _csrfToken);
    fd.append('file', file);
    var prev = document.getElementById('att-preview');
    prev.style.display='block';
    prev.textContent = 'Uploading ' + file.name + '…';
    fetch('/api/chat?action=upload', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(d){
        if (d.error) { prev.textContent = 'Upload failed: ' + d.error; prev.style.background='#FEE2E2'; prev.style.color='#991B1B'; return; }
        _attPending = d;
        prev.innerHTML = '&#128206; <strong>' + escHtml(d.attachment_name) + '</strong> · ' + fmtBytes(d.attachment_size) +
            ' &nbsp;<a href="#" onclick="cancelAttachment();return false" style="color:#DC2626;text-decoration:none">×</a>';
        prev.style.background='#EEF2FF'; prev.style.color='#3730A3';
    })
    .catch(function(){ prev.textContent='Upload failed.'; });
    input.value='';
}
function cancelAttachment(){
    _attPending=null;
    var p=document.getElementById('att-preview'); p.style.display='none'; p.textContent='';
}

function translateText() {
    var ta = document.getElementById('chat-input');
    var text = ta.value.trim();
    if (!text) return;
    var target = document.getElementById('chat-translate-lang').value;
    var url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=" + target + "&dt=t&q=" + encodeURIComponent(text);
    
    var btn = document.getElementById('chat-translate-btn');
    var oldHtml = btn.innerHTML;
    btn.innerHTML = '...';
    btn.disabled = true;
    
    fetch(url)
        .then(function(r){ return r.json(); })
        .then(function(data){
            var translated = "";
            if (data && data[0]) {
                for (var i = 0; i < data[0].length; i++) {
                    translated += data[0][i][0];
                }
                ta.value = translated;
            }
        })
        .catch(function(){ alert('Translation failed. Please try again.'); })
        .finally(function(){
            btn.innerHTML = oldHtml;
            btn.disabled = false;
            ta.focus();
        });
}

function sendMessage() {
    var inp = document.getElementById('chat-input');
    var msg = inp.value.trim();
    if (!msg && !_attPending) return;
    var fd = new FormData();
    fd.append('_token', _csrfToken);
    fd.append('message', msg);
    if (_attPending && _attPending.attachment_id) fd.append('attachment_id', _attPending.attachment_id);
    inp.value=''; cancelAttachment();
    fetch('/api/chat?action=send', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(){
        // Reload from scratch so a brand-new conversation (post-closure) flushes the banner.
        document.getElementById('chat-messages').innerHTML = '';
        _lastId = 0;
        loadMessages(0);
    });
}

function editMsg(id) {
    var bubbleEl = document.getElementById('bubble-'+id);
    if (!bubbleEl) return;
    var textEl = bubbleEl.querySelector('.aff-msg-text');
    var current = textEl ? textEl.innerText : '';
    var next = prompt('Edit your message:', current);
    if (next == null) return;
    next = String(next).trim();
    if (!next || next === current) return;
    var fd = new FormData();
    fd.append('_token', _csrfToken);
    fd.append('message_id', id);
    fd.append('message', next);
    fetch('/api/chat?action=edit_message', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(d){
        if (d.error) { alert(d.error); return; }
        document.getElementById('chat-messages').innerHTML = '';
        _lastId = 0;
        loadMessages(0);
    });
}

function translateAllMessages() {
    var target = document.getElementById('chat-read-translate-lang').value;
    var msgs = document.querySelectorAll('.aff-msg-text');
    var btn = document.getElementById('chat-read-translate-btn');
    if (!msgs.length) return;
    
    var oldText = btn.innerHTML;
    btn.innerHTML = '...';
    btn.disabled = true;
    
    var promises = [];
    msgs.forEach(function(el) {
        var text = el.getAttribute('data-orig');
        if (!text || !text.trim()) return;
        var url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=" + target + "&dt=t&q=" + encodeURIComponent(text);
        
        var p = fetch(url).then(function(r){return r.json();}).then(function(data){
            if (data && data[0]) {
                var translated = "";
                for (var i = 0; i < data[0].length; i++) {
                    translated += data[0][i][0];
                }
                el.innerHTML = escHtml(translated).replace(/\n/g,'<br>');
            }
        }).catch(function(e){});
        promises.push(p);
    });
    
    Promise.all(promises).finally(function(){
        btn.innerHTML = oldText;
        btn.disabled = false;
    });
}

function chatKeyDown(e) {
    if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); sendMessage(); }
}

loadMessages(0);
_polling = setInterval(function(){ loadMessages(_lastId); }, 5000);
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
