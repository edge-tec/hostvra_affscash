<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header" style="margin-bottom:16px">
    <div>
        <h1>&#128172; Live Support</h1>
        <p style="color:var(--text-muted);font-size:13px;margin-top:2px">Reply to affiliate support messages in real time</p>
    </div>
</div>

<script>var _csrfToken = '<?= Auth::generateCsrf() ?>';</script>

<style>
.adm-conv-row{padding:12px 16px;cursor:pointer;border-bottom:1px solid #F1F5F9;transition:background .15s;border-left:3px solid transparent}
.adm-conv-row.active{background:#EEF2FF;border-left-color:#4F46E5}
.adm-conv-row:hover:not(.active){background:#F8FAFC}
.adm-msg-row{display:flex;flex-direction:column;position:relative}
.adm-msg-row.mine{align-items:flex-end}
.adm-msg-bubble{max-width:72%;padding:10px 14px;border-radius:14px 14px 14px 4px;background:#fff;color:#1E293B;font-size:13px;line-height:1.55;box-shadow:0 1px 4px rgba(0,0,0,.08);border:1px solid #E2E8F0;word-wrap:break-word}
.adm-msg-row.mine .adm-msg-bubble{background:linear-gradient(135deg,#4F46E5,#6D28D9);color:#fff;border:none;border-radius:14px 14px 4px 14px}
.adm-msg-actions{position:absolute;top:-6px;display:none;gap:4px;background:#fff;border:1px solid #E2E8F0;border-radius:18px;padding:3px 6px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.adm-msg-row.mine  .adm-msg-actions{right:0}
.adm-msg-row.theirs .adm-msg-actions{left:0}
.adm-msg-row:hover .adm-msg-actions{display:inline-flex}
.adm-msg-action-btn{background:none;border:none;font-size:14px;color:#64748B;cursor:pointer;padding:2px 5px;border-radius:50%}
.adm-msg-action-btn:hover{background:#F1F5F9;color:#0F172A}
.adm-msg-action-btn.danger:hover{background:#FEE2E2;color:#B91C1C}
.adm-att-img{max-width:240px;max-height:240px;border-radius:8px;border:1px solid #E2E8F0;cursor:pointer;display:block;margin-top:4px}
.adm-att-file{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;font-size:12px;color:#1E293B;text-decoration:none;margin-top:4px}
.adm-att-file:hover{background:#EEF2FF}
.adm-att-icon{width:28px;height:28px;background:#EEF2FF;color:#4F46E5;border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0}
.adm-status-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;text-transform:uppercase;letter-spacing:.04em;display:inline-flex;align-items:center;gap:4px}
.adm-status-badge.open  {background:#D1FAE5;color:#065F46}
.adm-status-badge.closed{background:#FEE2E2;color:#991B1B}
.adm-filter-tab{flex:1;padding:8px 0;text-align:center;font-size:12px;font-weight:600;cursor:pointer;border-bottom:2px solid transparent;color:#64748B}
.adm-filter-tab.active{color:#4F46E5;border-bottom-color:#4F46E5}
.adm-edited{font-size:10px;color:#94A3B8;font-style:italic;margin-left:6px}
.adm-msg-row.mine .adm-edited{color:#E0E7FF}
@media (max-width:780px){
    #adm-support-grid{grid-template-columns:1fr !important;height:auto !important}
    #adm-support-grid .card{height:60vh}
}
</style>

<div id="adm-support-grid" style="display:grid;grid-template-columns:300px 1fr;gap:16px;height:calc(100vh - 210px);min-height:520px">

    <!-- Left: Conversation List -->
    <div class="card" style="display:flex;flex-direction:column;overflow:hidden;padding:0">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border);font-weight:700;font-size:13px;background:#F8FAFC;display:flex;align-items:center;justify-content:space-between">
            <span>Conversations</span>
            <span id="inbox-unread-badge" style="display:none;background:#EF4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px">0</span>
        </div>
        <!-- Affiliate / Advertiser owner-type switcher -->
        <div style="display:flex;border-bottom:1px solid var(--border);background:#F8FAFC">
            <div id="tab-affiliate"   class="adm-filter-tab active" onclick="setOwnerType('affiliate')">Affiliates</div>
            <div id="tab-advertiser"  class="adm-filter-tab"        onclick="setOwnerType('advertiser')">Advertisers</div>
        </div>
        <!-- Open / Closed filter tabs — closed ones are kept for archive/audit. -->
        <div style="display:flex;border-bottom:1px solid var(--border);background:#fff">
            <div id="tab-open"   class="adm-filter-tab active" onclick="setFilter('open')">Open</div>
            <div id="tab-closed" class="adm-filter-tab"        onclick="setFilter('closed')">Closed</div>
        </div>
        <div style="padding:8px 12px;border-bottom:1px solid var(--border);background:#fff">
            <input type="text" id="conv-search" placeholder="Search affiliate..." oninput="filterConversations(this.value)"
                style="width:100%;padding:7px 10px;border:1px solid #E5E7EB;border-radius:6px;font-size:12px;outline:none">
        </div>
        <div id="conv-list" style="flex:1;overflow-y:auto">
            <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px">Loading…</div>
        </div>
    </div>

    <!-- Right: Chat Panel -->
    <div class="card" style="display:flex;flex-direction:column;overflow:hidden;padding:0" id="chat-panel">

        <!-- Empty state -->
        <div id="chat-empty" style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:14px;color:var(--text-muted)">
            <div style="width:72px;height:72px;background:#EEF2FF;border-radius:50%;display:flex;align-items:center;justify-content:center">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#4F46E5" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div style="text-align:center">
                <div style="font-weight:600;font-size:14px;color:#374151">Select a conversation</div>
                <div style="font-size:12px;margin-top:4px">Choose an affiliate from the left to start chatting</div>
            </div>
        </div>

        <!-- Chat Header -->
        <div id="chat-header" style="display:none;padding:14px 20px;border-bottom:1px solid var(--border);align-items:center;gap:12px;background:#F8FAFC;flex-wrap:wrap">
            <div id="chat-avatar" style="width:40px;height:40px;background:linear-gradient(135deg,#4F46E5,#7C3AED);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;flex-shrink:0"></div>
            <div style="flex:1;min-width:120px">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <div id="chat-name" style="font-weight:700;font-size:14px;color:#111827"></div>
                    <span id="chat-status-badge" class="adm-status-badge open" style="display:none">Open</span>
                </div>
                <div id="chat-code" style="font-size:11px;color:var(--text-muted)"></div>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                <div style="display:flex;align-items:center;background:#fff;border:1px solid #CBD5E1;border-radius:6px;overflow:hidden;height:28px;margin-right:8px">
                    <select id="chat-read-translate-lang" style="background:none;border:none;outline:none;font-size:11px;padding:0 4px;color:#475569;font-weight:600;cursor:pointer">
                        <option value="en">EN</option>
                        <option value="bn">BN</option>
                        <option value="hi">HI</option>
                        <option value="ur">UR</option>
                        <option value="es">ES</option>
                        <option value="fr">FR</option>
                        <option value="ar">AR</option>
                        <option value="ru">RU</option>
                    </select>
                    <button type="button" id="chat-read-translate-btn" onclick="translateAllMessages()" style="background:#F1F5F9;border:none;border-left:1px solid #CBD5E1;height:100%;padding:0 8px;font-size:11px;color:#0F172A;cursor:pointer;font-weight:600" title="Translate all messages">Translate</button>
                </div>
                <button id="btn-close-conv"   class="btn btn-sm btn-secondary" onclick="closeConv()"  style="display:none">&#10003; Mark Solved / Close</button>
                <button id="btn-reopen-conv"  class="btn btn-sm btn-secondary" onclick="reopenConv()" style="display:none">&#8635; Reopen</button>
                <button class="btn btn-sm btn-secondary" onclick="clearHistory()">&#128465; Clear History</button>
                <a id="chat-view-profile" href="#" class="btn btn-sm btn-secondary">Profile &#8594;</a>
            </div>
        </div>

        <!-- Messages area -->
        <div id="chat-messages" style="display:none;flex:1;overflow-y:auto;padding:20px;flex-direction:column;gap:12px;background:#FAFBFC"></div>

        <!-- Input -->
        <div id="chat-input-area" style="display:none;padding:14px 20px;border-top:1px solid var(--border);background:#fff">
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

                <textarea id="chat-input" class="form-control" rows="2" placeholder="Type your reply…"
                    style="flex:1;resize:none;font-size:13px;line-height:1.5"
                    onkeydown="chatKeyDown(event)"></textarea>
                <button onclick="sendMessage()" class="btn btn-primary" style="height:52px;padding:0 22px;display:flex;align-items:center;gap:7px">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Send
                </button>
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:5px">Press <kbd style="background:#F1F5F9;border:1px solid #E2E8F0;border-radius:3px;padding:1px 5px;font-size:10px">Ctrl+Enter</kbd> to send · Closed conversations are archived; affiliates open a new ticket on their next message.</div>
        </div>
    </div>
</div>

<script>
var _selAffId    = <?= (int)$selAffId ?>;
var _selConvId   = 0;
var _selStatus   = 'open';
var _selName     = '';
var _selCode     = '';
var _lastId      = 0;
var _convData    = [];
var _filter      = 'open';
var _ownerType   = 'affiliate';   // 'affiliate' | 'advertiser'
var _msgPoll     = null;
var _convPoll    = null;
var _attPending  = null;   // {attachment_id, attachment_name, attachment_size}

function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function fmtBytes(b){ b=+b||0; if(b<1024)return b+' B'; if(b<1048576)return (b/1024).toFixed(1)+' KB'; return (b/1048576).toFixed(1)+' MB'; }

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
        return '<a href="'+dl+'" target="_blank"><img class="adm-att-img" src="'+dl+'" alt="'+name+'" onerror="this.style.display=\'none\';this.insertAdjacentHTML(\'afterend\',\'<span style=\\\'font-size:11px;color:#94A3B8\\\'>Preview unavailable</span>\');"></a>';
    }
    var ext = attExtLabel(m);
    var sizeTxt = m.attachment_size ? fmtBytes(m.attachment_size) : '';
    return '<a class="adm-att-file" href="'+dl+'" target="_blank" download>'+
           '<span class="adm-att-icon">'+escHtml(ext)+'</span>'+
           '<span>'+name+(sizeTxt?'<span style="display:block;font-size:11px;color:#94A3B8">'+escHtml(sizeTxt)+'</span>':'')+'</span></a>';
}

function renderMsg(m) {
    var isMine = (m.sender_role === 'admin' || m.sender_role === 'affiliate_manager');
    var time   = new Date(m.created_at.replace(' ','T')).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    var div    = document.createElement('div');
    div.className = 'adm-msg-row ' + (isMine ? 'mine' : 'theirs');
    div.dataset.id = m.id;
    var edited = m.edited_at ? '<span class="adm-edited">(edited)</span>' : '';
    var actions = '';
    // Admin can delete any message. Admin can also edit their own messages.
    actions += '<button class="adm-msg-action-btn danger" title="Delete message" onclick="deleteMsg('+m.id+')">&#128465;</button>';
    if (isMine && (m.message||'').trim() !== '') {
        actions += '<button class="adm-msg-action-btn" title="Edit message" onclick="editMsg('+m.id+')">&#9998;</button>';
    }

    div.innerHTML =
        '<div style="font-size:10px;color:#94A3B8;margin-bottom:3px">'+
            escHtml(m.sender_name)+' · '+time+ edited +
        '</div>'+
        '<div class="adm-msg-bubble" id="bubble-'+m.id+'">'+
            (m.message ? '<div class="adm-msg-text" data-orig="'+escHtml(m.message)+'">'+escHtml(m.message).replace(/\n/g,'<br>')+'</div>' : '')+
            attachmentHtml(m)+
        '</div>'+
        '<div class="adm-msg-actions">'+actions+'</div>';
    return div;
}

function setOwnerType(t){
    _ownerType = t;
    _selAffId  = 0;
    _selConvId = 0;
    document.getElementById('tab-affiliate').classList.toggle('active',  t === 'affiliate');
    document.getElementById('tab-advertiser').classList.toggle('active', t === 'advertiser');
    // Update search placeholder
    var srch = document.getElementById('conv-search');
    if (srch) srch.placeholder = t === 'advertiser' ? 'Search advertiser...' : 'Search affiliate...';
    // Reset chat panel to empty state
    document.getElementById('chat-empty').style.display      = 'flex';
    document.getElementById('chat-header').style.display     = 'none';
    document.getElementById('chat-messages').style.display   = 'none';
    document.getElementById('chat-input-area').style.display = 'none';
    if (_msgPoll) { clearInterval(_msgPoll); _msgPoll = null; }
    loadConversations();
}

function setFilter(f){
    _filter = f;
    document.getElementById('tab-open').classList.toggle('active',   f === 'open');
    document.getElementById('tab-closed').classList.toggle('active', f === 'closed');
    loadConversations();
}

function loadConversations() {
    fetch('/api/chat?action=conversations&status='+_filter+'&owner_type='+_ownerType)
    .then(function(r){return r.json();})
    .then(function(data){
        _convData = data.conversations || [];
        renderConvList(_convData);
    });
}

function renderConvList(convs) {
    var list = document.getElementById('conv-list');
    if (!convs.length) {
        list.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px">No '+_filter+' conversations</div>';
        document.getElementById('inbox-unread-badge').style.display = 'none';
        return;
    }
    var totalUnread = 0;
    list.innerHTML = '';
    convs.forEach(function(c){
        totalUnread += (c.unread|0);
        var isActive = (_selAffId == c.affiliate_id);
        var el = document.createElement('div');
        el.dataset.affId = c.affiliate_id;
        el.dataset.name  = (c.name||'').toLowerCase();
        el.className = 'adm-conv-row' + (isActive ? ' active' : '');
        el.onclick = function(){ selectConversation(c.affiliate_id, c.name, c.affiliate_code, c.conversation_id, c.status); };
        var lastAt = c.last_message_at ? new Date(c.last_message_at.replace(' ','T')).toLocaleDateString([],{month:'short',day:'numeric'}) : '';
        var statusBadge = c.status === 'closed'
            ? '<span class="adm-status-badge closed" style="margin-left:4px">Closed</span>'
            : '';
        el.innerHTML =
            '<div style="display:flex;justify-content:space-between;align-items:center;gap:6px">' +
                '<div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1">'+escHtml(c.name)+'</div>'+
                '<div style="display:flex;align-items:center;gap:5px;flex-shrink:0">'+
                    (c.unread>0 ? '<span style="background:#EF4444;color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;font-weight:700">'+c.unread+'</span>' : '')+
                    '<span style="font-size:10px;color:#CBD5E1">'+lastAt+'</span>'+
                '</div>'+
            '</div>'+
            '<div style="font-size:11px;color:#94A3B8;margin-top:1px">'+escHtml(c.affiliate_code)+statusBadge+'</div>'+
            (c.last_msg ? '<div style="font-size:12px;color:var(--text-muted);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:230px">'+escHtml(c.last_msg)+'</div>' : '');
        list.appendChild(el);
    });
    var badge = document.getElementById('inbox-unread-badge');
    badge.textContent = totalUnread;
    badge.style.display = totalUnread > 0 ? 'inline' : 'none';
}

function filterConversations(q) {
    q = q.toLowerCase();
    if (!q) { renderConvList(_convData); return; }
    renderConvList(_convData.filter(function(c){ return (c.name||'').toLowerCase().includes(q) || (c.affiliate_code||'').toLowerCase().includes(q); }));
}

function selectConversation(affId, name, code, convId, status) {
    _selAffId  = affId;
    _selName   = name;
    _selCode   = code;
    _selConvId = convId || 0;
    _selStatus = status || 'open';
    _lastId    = 0;
    if (_msgPoll) clearInterval(_msgPoll);

    document.getElementById('chat-empty').style.display      = 'none';
    document.getElementById('chat-header').style.display     = 'flex';
    document.getElementById('chat-messages').style.display   = 'flex';
    document.getElementById('chat-input-area').style.display = 'block';

    document.getElementById('chat-avatar').textContent = (name||'?').charAt(0).toUpperCase();
    document.getElementById('chat-name').textContent   = name;
    document.getElementById('chat-code').textContent   = code;
    document.getElementById('chat-view-profile').href  = _ownerType === 'advertiser'
        ? '/admin/advertisers?search='+encodeURIComponent(code)
        : '/admin/affiliates?search='+encodeURIComponent(code);

    var badge = document.getElementById('chat-status-badge');
    badge.style.display = _selConvId ? 'inline-flex' : 'none';
    badge.classList.remove('open','closed');
    badge.classList.add(_selStatus === 'closed' ? 'closed' : 'open');
    badge.textContent = _selStatus === 'closed' ? '✕ Closed' : '● Open';
    document.getElementById('btn-close-conv').style.display  = (_selStatus === 'open'   && _selConvId) ? '' : 'none';
    document.getElementById('btn-reopen-conv').style.display = (_selStatus === 'closed' && _selConvId) ? '' : 'none';

    document.getElementById('chat-messages').innerHTML = '';
    loadMessages(0);
    _msgPoll = setInterval(function(){ loadMessages(_lastId); }, 5000);
    renderConvList(_convData);
}

function loadMessages(since) {
    if (!_selAffId) return;
    var url = '/api/chat?action=messages&affiliate_id='+_selAffId+'&owner_type='+_ownerType;
    if (_selConvId) url += '&conversation_id='+_selConvId;
    if (since)      url += '&since='+since;
    fetch(url)
    .then(function(r){return r.json();})
    .then(function(data){
        var box = document.getElementById('chat-messages');
        if (data.conversation) {
            _selStatus = data.conversation.status;
            var badge = document.getElementById('chat-status-badge');
            badge.classList.remove('open','closed');
            badge.classList.add(_selStatus === 'closed' ? 'closed' : 'open');
            badge.textContent = _selStatus === 'closed' ? '✕ Closed' : '● Open';
            document.getElementById('btn-close-conv').style.display  = (_selStatus === 'open')   ? '' : 'none';
            document.getElementById('btn-reopen-conv').style.display = (_selStatus === 'closed') ? '' : 'none';
        }
        if (!since) box.innerHTML = '';
        var atBottom = box.scrollHeight - box.scrollTop <= box.clientHeight + 80;
        (data.messages||[]).forEach(function(m){
            // If this message id already on screen, replace it (catches edits).
            var existing = box.querySelector('[data-id="'+m.id+'"]');
            if (existing) existing.replaceWith(renderMsg(m));
            else          box.appendChild(renderMsg(m));
            if (m.id > _lastId) _lastId = m.id;
        });
        if (!since || atBottom) box.scrollTop = box.scrollHeight;
        if (!since) loadConversations();
    });
}

function prepareUpload(input){
    var file = input.files && input.files[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) { alert('File is larger than 10 MB.'); input.value=''; return; }

    var fd = new FormData();
    fd.append('_token', _csrfToken);
    fd.append('affiliate_id', _selAffId);
    fd.append('owner_type', _ownerType);
    fd.append('file', file);

    var prev = document.getElementById('att-preview');
    prev.style.display = 'block';
    prev.textContent = 'Uploading ' + file.name + '…';

    fetch('/api/chat?action=upload', { method:'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.error) { prev.textContent = 'Upload failed: ' + d.error; prev.style.background='#FEE2E2'; prev.style.color='#991B1B'; return; }
        _attPending = d;
        prev.innerHTML = '&#128206; <strong>' + escHtml(d.attachment_name) + '</strong> · ' + fmtBytes(d.attachment_size) +
            ' &nbsp;<a href="#" onclick="cancelAttachment();return false" style="color:#DC2626;text-decoration:none">×</a>';
        prev.style.background='#EEF2FF'; prev.style.color='#3730A3';
    })
    .catch(function(){ prev.textContent = 'Upload failed.'; });
    input.value = '';
}

function cancelAttachment(){
    _attPending = null;
    var prev = document.getElementById('att-preview');
    prev.style.display='none'; prev.textContent='';
}

function sendMessage() {
    var inp = document.getElementById('chat-input');
    var msg = inp.value.trim();
    if (!msg && !_attPending) return;
    if (!_selAffId) return;

    var fd = new FormData();
    fd.append('_token', _csrfToken);
    fd.append('affiliate_id', _selAffId);
    fd.append('owner_type', _ownerType);
    fd.append('message', msg);
    if (_attPending && _attPending.attachment_id) fd.append('attachment_id', _attPending.attachment_id);

    inp.value=''; inp.style.height='';
    cancelAttachment();

    fetch('/api/chat?action=send', { method:'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(){ loadMessages(_lastId); });
}

function editMsg(id) {
    var bubbleEl = document.getElementById('bubble-'+id);
    if (!bubbleEl) return;
    var textEl = bubbleEl.querySelector('.adm-msg-text');
    var current = textEl ? textEl.innerText : '';
    var next = prompt('Edit message:', current);
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
        loadMessages(0);   // full reload so the (edited) badge shows up
    });
}

function deleteMsg(id) {
    if (!confirm('Delete this message permanently? This cannot be undone.')) return;
    var fd = new FormData();
    fd.append('_token', _csrfToken);
    fd.append('message_id', id);
    fetch('/api/chat?action=delete_message', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(d){
        if (d.error) { alert(d.error); return; }
        var row = document.querySelector('[data-id="'+id+'"]');
        if (row) row.remove();
    });
}

function clearHistory() {
    if (!_selConvId) { alert('Open a conversation first.'); return; }
    if (!confirm('Clear ALL messages in this conversation? Every message and attachment will be removed. This cannot be undone.')) return;
    var fd = new FormData();
    fd.append('_token', _csrfToken);
    fd.append('conversation_id', _selConvId);
    fetch('/api/chat?action=clear_history', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(d){
        if (d.error) { alert(d.error); return; }
        document.getElementById('chat-messages').innerHTML = '';
        _lastId = 0;
        loadConversations();
    });
}

function closeConv() {
    if (!_selConvId) return;
    if (!confirm('Mark this conversation as solved and close it?\nThe affiliate\'s next message will open a brand-new ticket.')) return;
    var fd = new FormData();
    fd.append('_token', _csrfToken);
    fd.append('conversation_id', _selConvId);
    fetch('/api/chat?action=close_conversation', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(d){
        if (d.error) { alert(d.error); return; }
        _selStatus = 'closed';
        loadMessages(0);
        loadConversations();
    });
}

function reopenConv() {
    if (!_selConvId) return;
    if (!confirm('Reopen this closed conversation?')) return;
    var fd = new FormData();
    fd.append('_token', _csrfToken);
    fd.append('conversation_id', _selConvId);
    fetch('/api/chat?action=reopen_conversation', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(d){
        if (d.error) { alert(d.error); return; }
        _selStatus = 'open';
        loadMessages(0);
        loadConversations();
    });
}

function chatKeyDown(e) {
    if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); sendMessage(); }
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

function translateAllMessages() {
    var target = document.getElementById('chat-read-translate-lang').value;
    var msgs = document.querySelectorAll('.adm-msg-text');
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

document.getElementById('chat-input').addEventListener('input', function(){
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

loadConversations();
_convPoll = setInterval(loadConversations, 12000);

<?php if ($selAffId): ?>
(function(){
    <?php
    // Detect if the deep-link is for an advertiser (owner_type=advertiser in URL)
    $deepOwnerType = (Helpers::get('owner_type') === 'advertiser') ? 'advertiser' : 'affiliate';
    ?>
    _ownerType = '<?= $deepOwnerType ?>';
    document.getElementById('tab-affiliate').classList.toggle('active',  _ownerType === 'affiliate');
    document.getElementById('tab-advertiser').classList.toggle('active', _ownerType === 'advertiser');
    fetch('/api/chat?action=conversations&status=open&owner_type='+_ownerType)
    .then(function(r){return r.json();})
    .then(function(data){
        _convData = data.conversations || [];
        var c = _convData.find(function(x){ return x.affiliate_id == <?= $selAffId ?>; });
        if (c) selectConversation(c.affiliate_id, c.name, c.affiliate_code, c.conversation_id, c.status);
        else renderConvList(_convData);
    });
})();
<?php endif; ?>
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
