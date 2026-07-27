<?php require BASE_PATH . '/views/layouts/affiliate.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128172; Live Support</h1>
        <p><?php if($managerInfo): ?>Chat with <?= Helpers::e($managerInfo['first_name'].' '.$managerInfo['last_name']) ?><?php else: ?>Chat with Support Team<?php endif; ?></p>
    </div>
</div>

<script>var _csrfToken = '<?= Auth::generateCsrf() ?>';</script>

<style>
/* ── Chat Container ───────────────────────────────────────────────── */
.sc-chat-wrap{display:flex;flex-direction:column;height:calc(100vh - 200px);min-height:420px;max-height:780px;overflow:hidden}
.sc-chat-header{display:flex;justify-content:flex-end;padding:10px 16px;border-bottom:1px solid var(--border);background:#F8FAFC;flex-shrink:0;flex-wrap:wrap;gap:8px}

/* ── Messages Area ────────────────────────────────────────────────── */
.sc-messages{flex:1;overflow-y:auto;overflow-x:hidden;padding:16px;display:flex;flex-direction:column;gap:4px;background:linear-gradient(180deg,#F8FAFC 0%,#F1F5F9 100%);scroll-behavior:smooth}

/* ── Date Separator ───────────────────────────────────────────────── */
.sc-date-sep{display:flex;align-items:center;gap:12px;margin:16px 0 8px;user-select:none}
.sc-date-sep::before,.sc-date-sep::after{content:'';flex:1;height:1px;background:#E2E8F0}
.sc-date-sep span{font-size:11px;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;padding:4px 12px;background:#F1F5F9;border-radius:12px;border:1px solid #E2E8F0}

/* ── Message Row ──────────────────────────────────────────────────── */
.sc-msg-row{display:flex;flex-direction:column;max-width:100%;position:relative;padding:2px 0}
.sc-msg-row.mine{align-items:flex-end}
.sc-msg-row.theirs{align-items:flex-start}

/* ── Bubble ───────────────────────────────────────────────────────── */
.sc-bubble{max-width:min(75%,420px);padding:10px 14px;border-radius:18px 18px 18px 6px;background:#fff;color:#1E293B;font-size:13.5px;line-height:1.55;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #E2E8F0;word-wrap:break-word;overflow-wrap:break-word;overflow:hidden}
.sc-msg-row.mine .sc-bubble{background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;border:none;border-radius:18px 18px 6px 18px;box-shadow:0 2px 8px rgba(79,70,229,.25)}

/* ── Timestamp / Sender Label ─────────────────────────────────────── */
.sc-meta{font-size:10px;color:#94A3B8;margin-bottom:2px;padding:0 4px}
.sc-msg-row.mine .sc-meta{text-align:right}
.sc-edited{font-size:10px;color:#94A3B8;font-style:italic;margin-left:5px}
.sc-msg-row.mine .sc-edited{color:rgba(255,255,255,.6)}

/* ── Actions (edit) ───────────────────────────────────────────────── */
.sc-actions{display:none;gap:4px;background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:2px 6px;box-shadow:0 2px 6px rgba(0,0,0,.06);margin-top:2px}
.sc-msg-row:hover .sc-actions{display:inline-flex}
.sc-action-btn{background:none;border:none;font-size:13px;color:#64748B;cursor:pointer;padding:2px 5px;border-radius:50%;transition:all .15s}
.sc-action-btn:hover{background:#F1F5F9;color:#0F172A}

/* ── Attachments ──────────────────────────────────────────────────── */
.sc-att-img{max-width:100%;width:auto;max-height:280px;border-radius:10px;display:block;margin-top:6px;cursor:pointer;object-fit:contain}
.sc-att-file{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-radius:10px;font-size:12px;color:#fff;text-decoration:none;margin-top:6px;transition:background .15s;max-width:100%;overflow:hidden}
.sc-att-file:hover{background:rgba(255,255,255,.25)}
.sc-msg-row.theirs .sc-att-file{background:#F1F5F9;border-color:#E2E8F0;color:#1E293B}
.sc-msg-row.theirs .sc-att-file:hover{background:#EEF2FF}
.sc-att-icon{width:30px;height:30px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:10px;flex-shrink:0;text-transform:uppercase}
.sc-msg-row.theirs .sc-att-icon{background:#EEF2FF;color:#4F46E5}
.sc-att-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px}

/* ── Status Banner ────────────────────────────────────────────────── */
.sc-status-banner{padding:10px 16px;border-radius:10px;font-size:12px;font-weight:600;margin:8px 0;text-align:center}
.sc-status-banner.closed{background:#FEF3C7;color:#92400E;border:1px solid #FDE68A}

/* ── Input Area ───────────────────────────────────────────────────── */
.sc-input-wrap{padding:12px 16px;border-top:1px solid var(--border);background:#fff;flex-shrink:0}
.sc-input-row{display:flex;gap:8px;align-items:flex-end}
.sc-input-row textarea{flex:1;resize:none;font-size:13px;line-height:1.5;border-radius:12px;border:1px solid #E2E8F0;padding:12px 14px;outline:none;transition:border-color .2s;min-height:44px;max-height:120px}
.sc-input-row textarea:focus{border-color:#4F46E5;box-shadow:0 0 0 3px rgba(79,70,229,.08)}
.sc-send-btn{height:44px;padding:0 20px;border-radius:12px;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;border:none;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:transform .1s,box-shadow .15s;white-space:nowrap}
.sc-send-btn:hover{box-shadow:0 4px 12px rgba(79,70,229,.3);}
.sc-send-btn:active{}
.sc-file-btn{height:44px;width:44px;border-radius:12px;background:#F1F5F9;border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s;flex-shrink:0}
.sc-file-btn:hover{background:#EEF2FF;border-color:#C7D2FE}
.sc-translate-group{display:flex;align-items:center;background:#F1F5F9;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;height:44px;flex-shrink:0}
.sc-translate-group select{background:none;border:none;outline:none;font-size:11px;padding:0 6px;color:#475569;font-weight:600;cursor:pointer}
.sc-translate-group button{background:none;border:none;border-left:1px solid #E2E8F0;height:100%;padding:0 10px;font-size:12px;color:#0F172A;cursor:pointer;font-weight:600;white-space:nowrap}
.sc-translate-group button:hover{background:#E2E8F0}
.sc-att-preview{font-size:12px;color:#475569;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:10px;padding:8px 12px;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.sc-input-hint{font-size:11px;color:var(--text-muted);margin-top:6px}

/* ── Responsive ───────────────────────────────────────────────────── */
@media(max-width:640px){
    .sc-chat-wrap{height:calc(100vh - 160px);max-height:none;min-height:300px}
    .sc-bubble{max-width:min(85%,320px);font-size:13px;padding:9px 12px}
    .sc-att-img{max-height:200px}
    .sc-input-row{flex-wrap:wrap}
    .sc-translate-group{order:3;width:100%}
    .sc-input-row textarea{min-width:0}
    .sc-input-hint{display:none}
    .sc-chat-header{padding:8px 12px}
    .sc-messages{padding:12px 8px}
}
@media(max-width:400px){
    .sc-bubble{max-width:90%;padding:8px 10px;border-radius:14px 14px 14px 4px}
    .sc-msg-row.mine .sc-bubble{border-radius:14px 14px 4px 14px}
    .sc-send-btn span{display:none}
    .sc-send-btn{width:44px;padding:0;justify-content:center}
}
</style>

<div class="card sc-chat-wrap">
    <!-- Chat Header -->
    <div class="sc-chat-header" id="chat-header">
        <div style="display:flex;gap:8px;align-items:center">
            <span style="font-size:11px;color:#64748B;font-weight:600;text-transform:uppercase;letter-spacing:0.5px">Translate Chat:</span>
            <div class="sc-translate-group">
                <select id="chat-read-translate-lang">
                    <option value="en">EN</option>
                    <option value="bn">BN</option>
                    <option value="hi">HI</option>
                    <option value="ur">UR</option>
                    <option value="es">ES</option>
                    <option value="fr">FR</option>
                    <option value="ar">AR</option>
                    <option value="ru">RU</option>
                </select>
                <button type="button" id="chat-read-translate-btn" onclick="translateAllMessages()" title="Translate all messages">Translate</button>
            </div>
        </div>
    </div>

    <!-- Messages Area -->
    <div id="chat-messages" class="sc-messages">
        <div id="chat-loading" style="text-align:center;color:var(--text-muted);padding:40px;font-size:13px">Loading messages...</div>
    </div>

    <!-- Input -->
    <div class="sc-input-wrap">
        <div id="att-preview" style="display:none" class="sc-att-preview"></div>
        <div class="sc-input-row">
            <label class="sc-file-btn" title="Attach file (JPG, PNG, WEBP, PDF, CSV — max 10 MB)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                <input type="file" id="chat-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.csv,image/jpeg,image/png,image/webp,application/pdf,text/csv" style="display:none" onchange="prepareUpload(this)">
            </label>

            <div class="sc-translate-group">
                <select id="chat-translate-lang">
                    <option value="en">EN</option>
                    <option value="bn">BN</option>
                    <option value="hi">HI</option>
                    <option value="ur">UR</option>
                    <option value="es">ES</option>
                    <option value="fr">FR</option>
                    <option value="ar">AR</option>
                    <option value="ru">RU</option>
                </select>
                <button type="button" id="chat-translate-btn" onclick="translateText()" title="Translate text">A&rarr;あ</button>
            </div>

            <textarea id="chat-input" rows="1" placeholder="Type your message..." onkeydown="chatKeyDown(event)" oninput="autoResize(this)"></textarea>
            <button onclick="sendMessage()" class="sc-send-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <span>Send</span>
            </button>
        </div>
        <div class="sc-input-hint">Press Ctrl+Enter to send · Attachments: JPG, PNG, WEBP, PDF, CSV (max 10 MB)</div>
    </div>
</div>

<script>
var _affId    = <?= (int)$affId ?>;
var _lastId   = 0;
var _polling  = null;
var _myUserId = 0;
var _attPending = null;
var _renderedIds = {};   // track rendered message IDs to prevent duplicates
var _lastDateLabel = ''; // track last rendered date separator

function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmtBytes(b){ b=+b||0; if(b<1024)return b+' B'; if(b<1048576)return (b/1024).toFixed(1)+' KB'; return (b/1048576).toFixed(1)+' MB'; }

function autoResize(el){
    el.style.height='auto';
    el.style.height=Math.min(el.scrollHeight,120)+'px';
}

function attExtLabel(m){
    var nm  = String(m.attachment_name || '');
    var dot = nm.lastIndexOf('.');
    if (dot > -1 && dot < nm.length - 1) return nm.substring(dot + 1).toUpperCase().substr(0, 4);
    var t = String(m.attachment_type || '');
    if (t.indexOf('pdf') > -1)     return 'PDF';
    if (t.indexOf('csv') > -1)     return 'CSV';
    if (t.indexOf('image/') === 0) return (t.split('/').pop() || 'IMG').toUpperCase().substr(0,4);
    if (t.indexOf('plain') > -1)   return 'TXT';
    return 'FILE';
}

function attachmentHtml(m){
    if (!m.attachment_path) return '';
    var name = escHtml(m.attachment_name || 'file');
    var dl   = '/api/chat?action=download&id=' + m.id;
    if ((m.attachment_type||'').indexOf('image/') === 0) {
        return '<a href="'+dl+'" target="_blank"><img class="sc-att-img" src="'+dl+'" alt="'+name+'" loading="lazy" onerror="this.style.display=\'none\';this.insertAdjacentHTML(\'afterend\',\'<span style=\\\'font-size:11px;opacity:.7\\\'>Preview unavailable</span>\');"></a>';
    }
    var ext = attExtLabel(m);
    var sizeTxt = m.attachment_size ? fmtBytes(m.attachment_size) : '';
    return '<a class="sc-att-file" href="'+dl+'" target="_blank" download>'+
           '<span class="sc-att-icon">'+escHtml(ext)+'</span>'+
           '<span><span class="sc-att-name">'+name+'</span>'+(sizeTxt?'<span style="display:block;font-size:11px;opacity:.7">'+escHtml(sizeTxt)+'</span>':'')+'</span></a>';
}

/** Returns a human-readable date label for grouping messages */
function dateLabelFor(dateStr){
    var d = new Date(dateStr.replace(' ','T'));
    var now = new Date();
    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var msgDay = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    var diff = (today - msgDay) / 86400000;
    if (diff < 1) return 'Today';
    if (diff < 2) return 'Yesterday';
    return d.toLocaleDateString([], {weekday:'short', month:'short', day:'numeric', year: d.getFullYear()!==now.getFullYear()?'numeric':undefined});
}

function renderDateSep(label){
    var el = document.createElement('div');
    el.className = 'sc-date-sep';
    el.innerHTML = '<span>'+escHtml(label)+'</span>';
    return el;
}

function renderMsg(m) {
    var isMine = m.sender_role === 'affiliate';
    var time   = new Date(m.created_at.replace(' ','T')).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    var div    = document.createElement('div');
    div.className = 'sc-msg-row ' + (isMine ? 'mine' : 'theirs');
    div.dataset.id = m.id;
    div.dataset.date = m.created_at;
    var edited = m.edited_at ? '<span class="sc-edited">(edited)</span>' : '';
    var canEdit = isMine && (m.message||'').trim() !== '';
    div.innerHTML =
        '<div class="sc-meta">'+
            (isMine ? 'You' : escHtml(m.sender_name))+' · '+time+ edited +
        '</div>'+
        '<div class="sc-bubble" id="bubble-'+m.id+'">'+
            (m.message ? '<div class="sc-msg-text" data-orig="'+escHtml(m.message)+'">'+escHtml(m.message).replace(/\n/g,'<br>')+'</div>' : '')+
            attachmentHtml(m)+
        '</div>'+
        (canEdit
            ? '<div class="sc-actions"><button class="sc-action-btn" title="Edit" onclick="editMsg('+m.id+')">&#9998;</button></div>'
            : '');
    return div;
}

function scrollToBottom(smooth){
    var box = document.getElementById('chat-messages');
    if (smooth) {
        box.scrollTo({top: box.scrollHeight, behavior:'smooth'});
    } else {
        box.scrollTop = box.scrollHeight;
    }
}

function loadMessages(since) {
    fetch('/api/chat?action=messages&affiliate_id='+_affId+(since?'&since='+since:'')+'&_t='+Date.now())
    .then(function(r){return r.json();})
    .then(function(data){
        var box = document.getElementById('chat-messages');
        var loadEl = document.getElementById('chat-loading'); if (loadEl) loadEl.remove();
        if (data.my_user_id) _myUserId = data.my_user_id;

        if (!since && data.conversation && data.conversation.status === 'closed') {
            box.insertAdjacentHTML('afterbegin',
                '<div class="sc-status-banner closed">&#10003; This support conversation has been marked as Solved. '+
                'Send a new message below to start a new ticket.</div>');
        }

        if (!data.messages || !data.messages.length) {
            if (!_lastId && !box.querySelector('.empty-chat-msg')) {
                box.insertAdjacentHTML('beforeend',
                    '<div class="sc-msg-row theirs empty-chat-msg">'+
                    '<div class="sc-meta">Support Team</div>'+
                    '<div class="sc-bubble">Hello <?= Helpers::e(Auth::currentUser()['first_name'] ?? 'Affiliate') ?>,<br><br>Welcome to AffsCash Live Chat Support. We\'re here to help you.<br>How can I assist you today?</div>'+
                    '</div>'
                );
            }
            return;
        }

        // Remove empty chat placeholder if real messages arrived
        var emptyEl = box.querySelector('.empty-chat-msg');
        if (emptyEl) emptyEl.remove();

        var atBottom = box.scrollHeight - box.scrollTop <= box.clientHeight + 80;

        // If full reload, reset tracking
        if (!since) {
            _renderedIds = {};
            _lastDateLabel = '';
        }

        data.messages.forEach(function(m){
            // Deduplicate
            if (_renderedIds[m.id]) {
                // Update in place if it already exists (for edits)
                var existing = box.querySelector('[data-id="'+m.id+'"]');
                if (existing) existing.replaceWith(renderMsg(m));
                return;
            }

            // Date separator
            var label = dateLabelFor(m.created_at);
            if (label !== _lastDateLabel) {
                // Check if this separator already exists for full reloads
                box.appendChild(renderDateSep(label));
                _lastDateLabel = label;
            }

            box.appendChild(renderMsg(m));
            _renderedIds[m.id] = true;
            if (m.id > _lastId) _lastId = m.id;
        });

        if (!since || atBottom) {
            scrollToBottom(!since ? false : true);
        }
    })
    .catch(function(e){
        var loadEl = document.getElementById('chat-loading'); if (loadEl) loadEl.remove();
        console.error("Chat load error:", e);
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
    prev.style.display='flex';
    prev.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" style="flex-shrink:0;animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Uploading ' + escHtml(file.name) + '…';
    fetch('/api/chat?action=upload', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(d){
        if (d.error) { prev.innerHTML = '⚠ Upload failed: ' + escHtml(d.error); prev.style.background='#FEE2E2'; prev.style.borderColor='#FECACA'; return; }
        _attPending = d;
        prev.innerHTML = '&#128206; <strong>' + escHtml(d.attachment_name) + '</strong> · ' + fmtBytes(d.attachment_size) +
            ' &nbsp;<a href="#" onclick="cancelAttachment();return false" style="color:#DC2626;text-decoration:none;font-weight:700">×</a>';
    })
    .catch(function(){ prev.innerHTML='⚠ Upload failed.'; });
    input.value='';
}
function cancelAttachment(){
    _attPending=null;
    var p=document.getElementById('att-preview'); p.style.display='none'; p.innerHTML='';
}

function translateText() {
    var ta = document.getElementById('chat-input');
    var text = ta.value.trim();
    if (!text) return;
    var target = document.getElementById('chat-translate-lang').value;
    var url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=" + target + "&dt=t&q=" + encodeURIComponent(text);
    var btn = document.getElementById('chat-translate-btn');
    var oldHtml = btn.innerHTML;
    btn.innerHTML = '...'; btn.disabled = true;
    fetch(url)
        .then(function(r){ return r.json(); })
        .then(function(data){
            var translated = "";
            if (data && data[0]) { for (var i = 0; i < data[0].length; i++) translated += data[0][i][0]; ta.value = translated; }
        })
        .catch(function(){ alert('Translation failed.'); })
        .finally(function(){ btn.innerHTML = oldHtml; btn.disabled = false; ta.focus(); });
}

function sendMessage() {
    var inp = document.getElementById('chat-input');
    var msg = inp.value.trim();
    if (!msg && !_attPending) return;
    var fd = new FormData();
    fd.append('_token', _csrfToken);
    fd.append('message', msg);
    if (_attPending && _attPending.attachment_id) fd.append('attachment_id', _attPending.attachment_id);
    inp.value=''; inp.style.height=''; cancelAttachment();
    fetch('/api/chat?action=send', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(){
        // Full reload to flush banners / pick up new conversation
        var box = document.getElementById('chat-messages');
        box.innerHTML = '';
        _lastId = 0;
        _renderedIds = {};
        _lastDateLabel = '';
        loadMessages(0);
    });
}

function editMsg(id) {
    var bubbleEl = document.getElementById('bubble-'+id);
    if (!bubbleEl) return;
    var textEl = bubbleEl.querySelector('.sc-msg-text');
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
        _lastId = 0; _renderedIds = {}; _lastDateLabel = '';
        loadMessages(0);
    });
}

function translateAllMessages() {
    var target = document.getElementById('chat-read-translate-lang').value;
    var msgs = document.querySelectorAll('.sc-msg-text');
    var btn = document.getElementById('chat-read-translate-btn');
    if (!msgs.length) return;
    var oldText = btn.innerHTML;
    btn.innerHTML = '...'; btn.disabled = true;
    var promises = [];
    msgs.forEach(function(el) {
        var text = el.getAttribute('data-orig');
        if (!text || !text.trim()) return;
        var url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=" + target + "&dt=t&q=" + encodeURIComponent(text);
        var p = fetch(url).then(function(r){return r.json();}).then(function(data){
            if (data && data[0]) { var t=""; for(var i=0;i<data[0].length;i++) t+=data[0][i][0]; el.innerHTML=escHtml(t).replace(/\n/g,'<br>'); }
        }).catch(function(){});
        promises.push(p);
    });
    Promise.all(promises).finally(function(){ btn.innerHTML = oldText; btn.disabled = false; });
}

function chatKeyDown(e) {
    if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); sendMessage(); }
}

loadMessages(0);
_polling = setInterval(function(){ loadMessages(_lastId); }, 5000);
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_footer.php'; ?>
