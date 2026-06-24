<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header" style="margin-bottom:16px">
    <div>
        <h1>&#128172; Live Support</h1>
        <p style="color:var(--text-muted);font-size:13px;margin-top:2px">Reply to affiliate support messages in real time</p>
    </div>
</div>

<script>var _csrfToken = '<?= Auth::generateCsrf() ?>';</script>

<style>
/* ── Conversation List ────────────────────────────────────────────── */
.ac-conv-row{padding:12px 16px;cursor:pointer;border-bottom:1px solid #F1F5F9;transition:background .15s;border-left:3px solid transparent}
.ac-conv-row.active{background:#EEF2FF;border-left-color:#4F46E5}
.ac-conv-row:hover:not(.active){background:#F8FAFC}

/* ── Date Separator ───────────────────────────────────────────────── */
.ac-date-sep{display:flex;align-items:center;gap:12px;margin:16px 0 8px;user-select:none}
.ac-date-sep::before,.ac-date-sep::after{content:'';flex:1;height:1px;background:#E2E8F0}
.ac-date-sep span{font-size:11px;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;padding:4px 12px;background:#F1F5F9;border-radius:12px;border:1px solid #E2E8F0}

/* ── Message Row ──────────────────────────────────────────────────── */
.ac-msg-row{display:flex;flex-direction:column;position:relative;max-width:100%;padding:2px 0}
.ac-msg-row.mine{align-items:flex-end}
.ac-msg-row.theirs{align-items:flex-start}

/* ── Bubble ───────────────────────────────────────────────────────── */
.ac-bubble{max-width:min(72%,440px);padding:10px 14px;border-radius:18px 18px 18px 6px;background:#fff;color:#1E293B;font-size:13.5px;line-height:1.55;box-shadow:0 1px 3px rgba(0,0,0,.06);border:1px solid #E2E8F0;word-wrap:break-word;overflow-wrap:break-word;overflow:hidden}
.ac-msg-row.mine .ac-bubble{background:linear-gradient(135deg,#4F46E5,#6D28D9);color:#fff;border:none;border-radius:18px 18px 6px 18px;box-shadow:0 2px 8px rgba(79,70,229,.25)}

/* ── Meta / Timestamp ─────────────────────────────────────────────── */
.ac-meta{font-size:10px;color:#94A3B8;margin-bottom:2px;padding:0 4px}
.ac-msg-row.mine .ac-meta{text-align:right}
.ac-edited{font-size:10px;color:#94A3B8;font-style:italic;margin-left:5px}
.ac-msg-row.mine .ac-edited{color:rgba(255,255,255,.6)}

/* ── Actions (edit/delete) ────────────────────────────────────────── */
.ac-actions{position:absolute;top:-6px;display:none;gap:4px;background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:2px 6px;box-shadow:0 2px 8px rgba(0,0,0,.06);z-index:5}
.ac-msg-row.mine .ac-actions{right:0}
.ac-msg-row.theirs .ac-actions{left:0}
.ac-msg-row:hover .ac-actions{display:inline-flex}
.ac-action-btn{background:none;border:none;font-size:13px;color:#64748B;cursor:pointer;padding:2px 5px;border-radius:50%;transition:all .15s}
.ac-action-btn:hover{background:#F1F5F9;color:#0F172A}
.ac-action-btn.danger:hover{background:#FEE2E2;color:#B91C1C}

/* ── Attachments ──────────────────────────────────────────────────── */
.ac-att-img{max-width:100%;width:auto;max-height:280px;border-radius:10px;display:block;margin-top:6px;cursor:pointer;object-fit:contain}
.ac-att-file{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;background:#F1F5F9;border:1px solid #E2E8F0;border-radius:10px;font-size:12px;color:#1E293B;text-decoration:none;margin-top:6px;transition:background .15s;max-width:100%;overflow:hidden}
.ac-att-file:hover{background:#EEF2FF}
.ac-att-icon{width:30px;height:30px;background:#EEF2FF;color:#4F46E5;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:10px;flex-shrink:0;text-transform:uppercase}
.ac-att-name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px}

/* ── Status Badge ─────────────────────────────────────────────────── */
.ac-status-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;text-transform:uppercase;letter-spacing:.04em;display:inline-flex;align-items:center;gap:4px}
.ac-status-badge.open{background:#D1FAE5;color:#065F46}
.ac-status-badge.closed{background:#FEE2E2;color:#991B1B}
.ac-filter-tab{flex:1;padding:8px 0;text-align:center;font-size:12px;font-weight:600;cursor:pointer;border-bottom:2px solid transparent;color:#64748B;transition:all .15s}
.ac-filter-tab.active{color:#4F46E5;border-bottom-color:#4F46E5}
.ac-filter-tab:hover:not(.active){color:#334155}

/* ── Input Area ───────────────────────────────────────────────────── */
.ac-input-row{display:flex;gap:8px;align-items:flex-end}
.ac-input-row textarea{flex:1;resize:none;font-size:13px;line-height:1.5;border-radius:12px;border:1px solid #E2E8F0;padding:12px 14px;outline:none;transition:border-color .2s;min-height:44px;max-height:120px}
.ac-input-row textarea:focus{border-color:#4F46E5;box-shadow:0 0 0 3px rgba(79,70,229,.08)}
.ac-send-btn{height:44px;padding:0 20px;border-radius:12px;background:linear-gradient(135deg,#4F46E5,#6366F1);color:#fff;border:none;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:transform .1s,box-shadow .15s;white-space:nowrap}
.ac-send-btn:hover{box-shadow:0 4px 12px rgba(79,70,229,.3);transform:translateY(-1px)}
.ac-send-btn:active{transform:translateY(0)}
.ac-file-btn{height:44px;width:44px;border-radius:12px;background:#F1F5F9;border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s;flex-shrink:0}
.ac-file-btn:hover{background:#EEF2FF;border-color:#C7D2FE}
.ac-translate-group{display:flex;align-items:center;background:#F1F5F9;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;height:44px;flex-shrink:0}
.ac-translate-group select{background:none;border:none;outline:none;font-size:11px;padding:0 6px;color:#475569;font-weight:600;cursor:pointer}
.ac-translate-group button{background:none;border:none;border-left:1px solid #E2E8F0;height:100%;padding:0 10px;font-size:12px;color:#0F172A;cursor:pointer;font-weight:600;white-space:nowrap}
.ac-translate-group button:hover{background:#E2E8F0}
.ac-att-preview{font-size:12px;color:#475569;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:10px;padding:8px 12px;margin-bottom:8px;display:flex;align-items:center;gap:8px}

/* ── Messages Area ────────────────────────────────────────────────── */
.ac-messages{flex:1;overflow-y:auto;overflow-x:hidden;padding:16px 20px;display:flex;flex-direction:column;gap:4px;background:linear-gradient(180deg,#F8FAFC 0%,#F1F5F9 100%);scroll-behavior:smooth}

/* ── Mobile Back Button ───────────────────────────────────────────── */
.ac-back-btn{display:none;background:none;border:none;cursor:pointer;font-size:13px;color:#4F46E5;font-weight:600;padding:4px 8px;border-radius:6px}
.ac-back-btn:hover{background:#EEF2FF}

/* ── Responsive ───────────────────────────────────────────────────── */
@media(max-width:780px){
    #ac-support-grid{grid-template-columns:1fr !important;height:auto !important}
    #ac-support-grid .card{min-height:0}
    #ac-conv-list-card{height:50vh !important}
    #chat-panel{height:70vh !important}
    #chat-panel.ac-panel-active{display:flex !important}
    .ac-back-btn{display:inline-flex !important}
    .ac-bubble{max-width:min(85%,320px)}
    .ac-input-row{flex-wrap:wrap}
    .ac-translate-group{order:3;width:100%}
}
@media(max-width:480px){
    .ac-bubble{max-width:90%;padding:8px 10px;font-size:13px}
    .ac-send-btn span{display:none}
    .ac-send-btn{width:44px;padding:0;justify-content:center}
    .ac-att-img{max-height:200px}
}
</style>

<div id="ac-support-grid" style="display:grid;grid-template-columns:300px 1fr;gap:16px;height:calc(100vh - 210px);min-height:520px">

    <!-- Left: Conversation List -->
    <div class="card" id="ac-conv-list-card" style="display:flex;flex-direction:column;overflow:hidden;padding:0">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border);font-weight:700;font-size:13px;background:#F8FAFC;display:flex;align-items:center;justify-content:space-between">
            <span>Conversations</span>
            <span id="inbox-unread-badge" style="display:none;background:#EF4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px">0</span>
        </div>
        <!-- Affiliate / Advertiser owner-type switcher -->
        <div style="display:flex;border-bottom:1px solid var(--border);background:#F8FAFC">
            <div id="tab-affiliate"   class="ac-filter-tab active" onclick="setOwnerType('affiliate')">Affiliates</div>
            <div id="tab-advertiser"  class="ac-filter-tab"        onclick="setOwnerType('advertiser')">Advertisers</div>
        </div>
        <!-- Open / Closed filter tabs -->
        <div style="display:flex;border-bottom:1px solid var(--border);background:#fff">
            <div id="tab-open"   class="ac-filter-tab active" onclick="setFilter('open')">Open</div>
            <div id="tab-closed" class="ac-filter-tab"        onclick="setFilter('closed')">Closed</div>
        </div>
        <div style="padding:8px 12px;border-bottom:1px solid var(--border);background:#fff">
            <input type="text" id="conv-search" placeholder="Search affiliate..." oninput="filterConversations(this.value)"
                style="width:100%;padding:7px 10px;border:1px solid #E5E7EB;border-radius:8px;font-size:12px;outline:none">
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
        <div id="chat-header" style="display:none;padding:12px 16px;border-bottom:1px solid var(--border);align-items:center;gap:10px;background:#F8FAFC;flex-wrap:wrap;flex-shrink:0">
            <button class="ac-back-btn" onclick="showConvList()" title="Back to conversations">&larr; Back</button>
            <div id="chat-avatar" style="width:36px;height:36px;background:linear-gradient(135deg,#4F46E5,#7C3AED);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;flex-shrink:0"></div>
            <div style="flex:1;min-width:100px">
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                    <div id="chat-name" style="font-weight:700;font-size:13px;color:#111827"></div>
                    <span id="chat-status-badge" class="ac-status-badge open" style="display:none">Open</span>
                </div>
                <div id="chat-code" style="font-size:11px;color:var(--text-muted)"></div>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                <div class="ac-translate-group" style="height:28px">
                    <select id="chat-read-translate-lang" style="font-size:11px;padding:0 4px">
                        <option value="en">EN</option><option value="bn">BN</option><option value="hi">HI</option><option value="ur">UR</option>
                        <option value="es">ES</option><option value="fr">FR</option><option value="ar">AR</option><option value="ru">RU</option>
                    </select>
                    <button type="button" id="chat-read-translate-btn" onclick="translateAllMessages()" style="font-size:11px;padding:0 8px" title="Translate">Translate</button>
                </div>
                <button id="btn-close-conv"   class="btn btn-sm btn-secondary" onclick="closeConv()"  style="display:none">&#10003; Solved</button>
                <button id="btn-reopen-conv"  class="btn btn-sm btn-secondary" onclick="reopenConv()" style="display:none">&#8635; Reopen</button>
                <button class="btn btn-sm btn-secondary" onclick="clearHistory()">&#128465; Clear</button>
                <a id="chat-view-profile" href="#" class="btn btn-sm btn-secondary">Profile &rarr;</a>
            </div>
        </div>

        <!-- Messages area -->
        <div id="chat-messages" class="ac-messages" style="display:none"></div>

        <!-- Input -->
        <div id="chat-input-area" style="display:none;padding:12px 16px;border-top:1px solid var(--border);background:#fff;flex-shrink:0">
            <div id="att-preview" style="display:none" class="ac-att-preview"></div>
            <div class="ac-input-row">
                <label class="ac-file-btn" title="Attach file (JPG, PNG, WEBP, PDF, CSV — max 10 MB)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    <input type="file" id="chat-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.csv,image/jpeg,image/png,image/webp,application/pdf,text/csv" style="display:none" onchange="prepareUpload(this)">
                </label>

                <div class="ac-translate-group">
                    <select id="chat-translate-lang">
                        <option value="en">EN</option><option value="bn">BN</option><option value="hi">HI</option><option value="ur">UR</option>
                        <option value="es">ES</option><option value="fr">FR</option><option value="ar">AR</option><option value="ru">RU</option>
                    </select>
                    <button type="button" id="chat-translate-btn" onclick="translateText()" title="Translate text">A&rarr;あ</button>
                </div>

                <textarea id="chat-input" rows="1" placeholder="Type your reply…"
                    onkeydown="chatKeyDown(event)" oninput="autoResize(this)"></textarea>
                <button onclick="sendMessage()" class="ac-send-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    <span>Send</span>
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
var _ownerType   = 'affiliate';
var _msgPoll     = null;
var _convPoll    = null;
var _attPending  = null;
var _renderedIds = {};
var _lastDateLabel = '';

function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmtBytes(b){ b=+b||0; if(b<1024)return b+' B'; if(b<1048576)return (b/1024).toFixed(1)+' KB'; return (b/1048576).toFixed(1)+' MB'; }
function autoResize(el){ el.style.height='auto'; el.style.height=Math.min(el.scrollHeight,120)+'px'; }

function attExtLabel(m){
    var nm = String(m.attachment_name || '');
    var dot = nm.lastIndexOf('.');
    if (dot > -1 && dot < nm.length - 1) return nm.substring(dot+1).toUpperCase().substr(0,4);
    var t = String(m.attachment_type || '');
    if (t.indexOf('pdf')>-1) return 'PDF'; if (t.indexOf('csv')>-1) return 'CSV';
    if (t.indexOf('image/')===0) return (t.split('/').pop()||'IMG').toUpperCase().substr(0,4);
    if (t.indexOf('plain')>-1) return 'TXT'; return 'FILE';
}
function attachmentHtml(m){
    if (!m.attachment_path) return '';
    var name = escHtml(m.attachment_name || 'file');
    var dl   = '/api/chat?action=download&id=' + m.id;
    if ((m.attachment_type||'').indexOf('image/') === 0) {
        return '<a href="'+dl+'" target="_blank"><img class="ac-att-img" src="'+dl+'" alt="'+name+'" loading="lazy" onerror="this.style.display=\'none\';this.insertAdjacentHTML(\'afterend\',\'<span style=\\\'font-size:11px;color:#94A3B8\\\'>Preview unavailable</span>\');"></a>';
    }
    var ext = attExtLabel(m);
    var sizeTxt = m.attachment_size ? fmtBytes(m.attachment_size) : '';
    return '<a class="ac-att-file" href="'+dl+'" target="_blank" download>'+
           '<span class="ac-att-icon">'+escHtml(ext)+'</span>'+
           '<span><span class="ac-att-name">'+name+'</span>'+(sizeTxt?'<span style="display:block;font-size:11px;color:#94A3B8">'+escHtml(sizeTxt)+'</span>':'')+'</span></a>';
}

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
    el.className = 'ac-date-sep';
    el.innerHTML = '<span>'+escHtml(label)+'</span>';
    return el;
}

function renderMsg(m) {
    var isMine = (m.sender_role === 'admin' || m.sender_role === 'affiliate_manager');
    var time   = new Date(m.created_at.replace(' ','T')).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    var div    = document.createElement('div');
    div.className = 'ac-msg-row ' + (isMine ? 'mine' : 'theirs');
    div.dataset.id = m.id;
    div.dataset.date = m.created_at;
    var edited = m.edited_at ? '<span class="ac-edited">(edited)</span>' : '';
    var actions = '';
    actions += '<button class="ac-action-btn danger" title="Delete message" onclick="deleteMsg('+m.id+')">&#128465;</button>';
    if (isMine && (m.message||'').trim() !== '') {
        actions += '<button class="ac-action-btn" title="Edit message" onclick="editMsg('+m.id+')">&#9998;</button>';
    }
    div.innerHTML =
        '<div class="ac-meta">'+escHtml(m.sender_name)+' · '+time+ edited +'</div>'+
        '<div class="ac-bubble" id="bubble-'+m.id+'">'+
            (m.message ? '<div class="ac-msg-text" data-orig="'+escHtml(m.message)+'">'+escHtml(m.message).replace(/\n/g,'<br>')+'</div>' : '')+
            attachmentHtml(m)+
        '</div>'+
        '<div class="ac-actions">'+actions+'</div>';
    return div;
}

function scrollToBottom(smooth){
    var box = document.getElementById('chat-messages');
    if (smooth) box.scrollTo({top:box.scrollHeight,behavior:'smooth'});
    else box.scrollTop = box.scrollHeight;
}

function setOwnerType(t){
    _ownerType = t; _selAffId = 0; _selConvId = 0;
    document.getElementById('tab-affiliate').classList.toggle('active',  t === 'affiliate');
    document.getElementById('tab-advertiser').classList.toggle('active', t === 'advertiser');
    var srch = document.getElementById('conv-search');
    if (srch) srch.placeholder = t === 'advertiser' ? 'Search advertiser...' : 'Search affiliate...';
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
        el.className = 'ac-conv-row' + (isActive ? ' active' : '');
        el.onclick = function(){ selectConversation(c.affiliate_id, c.name, c.affiliate_code, c.conversation_id, c.status); };
        var lastAt = c.last_message_at ? new Date(c.last_message_at.replace(' ','T')).toLocaleDateString([],{month:'short',day:'numeric'}) : '';
        var statusBadge = c.status === 'closed' ? '<span class="ac-status-badge closed" style="margin-left:4px">Closed</span>' : '';
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

function showConvList(){
    // Mobile: show conversation list, hide chat panel
    document.getElementById('ac-conv-list-card').style.display='flex';
    document.getElementById('chat-panel').style.display='none';
    document.getElementById('chat-panel').classList.remove('ac-panel-active');
}

function selectConversation(affId, name, code, convId, status) {
    _selAffId  = affId; _selName = name; _selCode = code;
    _selConvId = convId || 0; _selStatus = status || 'open';
    _lastId = 0; _renderedIds = {}; _lastDateLabel = '';
    if (_msgPoll) clearInterval(_msgPoll);

    document.getElementById('chat-empty').style.display      = 'none';
    document.getElementById('chat-header').style.display     = 'flex';
    document.getElementById('chat-messages').style.display   = 'flex';
    document.getElementById('chat-input-area').style.display = 'block';

    // Mobile: hide conv list, show chat
    if (window.innerWidth <= 780) {
        document.getElementById('ac-conv-list-card').style.display='none';
        document.getElementById('chat-panel').style.display='flex';
        document.getElementById('chat-panel').classList.add('ac-panel-active');
    }

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
    url += '&_t=' + Date.now();
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
        if (!since) { box.innerHTML = ''; _renderedIds = {}; _lastDateLabel = ''; }
        var atBottom = box.scrollHeight - box.scrollTop <= box.clientHeight + 80;
        (data.messages||[]).forEach(function(m){
            if (_renderedIds[m.id]) {
                var existing = box.querySelector('[data-id="'+m.id+'"]');
                if (existing) existing.replaceWith(renderMsg(m));
                return;
            }
            var label = dateLabelFor(m.created_at);
            if (label !== _lastDateLabel) { box.appendChild(renderDateSep(label)); _lastDateLabel = label; }
            box.appendChild(renderMsg(m));
            _renderedIds[m.id] = true;
            if (m.id > _lastId) _lastId = m.id;
        });
        if (!since || atBottom) scrollToBottom(!since ? false : true);
        if (!since) loadConversations();
    });
}

function prepareUpload(input){
    var file = input.files && input.files[0]; if (!file) return;
    if (file.size > 10*1024*1024) { alert('File is larger than 10 MB.'); input.value=''; return; }
    var fd = new FormData();
    fd.append('_token', _csrfToken); fd.append('affiliate_id', _selAffId); fd.append('owner_type', _ownerType); fd.append('file', file);
    var prev = document.getElementById('att-preview');
    prev.style.display='flex'; prev.innerHTML='Uploading '+escHtml(file.name)+'…';
    fetch('/api/chat?action=upload', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(d){
        if (d.error) { prev.innerHTML='⚠ '+escHtml(d.error); prev.style.background='#FEE2E2'; return; }
        _attPending = d;
        prev.innerHTML = '&#128206; <strong>'+escHtml(d.attachment_name)+'</strong> · '+fmtBytes(d.attachment_size)+
            ' &nbsp;<a href="#" onclick="cancelAttachment();return false" style="color:#DC2626;text-decoration:none;font-weight:700">×</a>';
    })
    .catch(function(){ prev.innerHTML='⚠ Upload failed.'; });
    input.value='';
}
function cancelAttachment(){ _attPending=null; var p=document.getElementById('att-preview'); p.style.display='none'; p.innerHTML=''; }

function sendMessage() {
    var inp = document.getElementById('chat-input');
    var msg = inp.value.trim();
    if (!msg && !_attPending) return; if (!_selAffId) return;
    var fd = new FormData();
    fd.append('_token', _csrfToken); fd.append('affiliate_id', _selAffId); fd.append('owner_type', _ownerType); fd.append('message', msg);
    if (_attPending && _attPending.attachment_id) fd.append('attachment_id', _attPending.attachment_id);
    inp.value=''; inp.style.height=''; cancelAttachment();
    fetch('/api/chat?action=send', { method:'POST', body: fd })
    .then(function(r){return r.json();})
    .then(function(){ loadMessages(_lastId); });
}

function editMsg(id) {
    var bubbleEl = document.getElementById('bubble-'+id); if (!bubbleEl) return;
    var textEl = bubbleEl.querySelector('.ac-msg-text');
    var current = textEl ? textEl.innerText : '';
    var next = prompt('Edit message:', current);
    if (next == null) return; next = String(next).trim();
    if (!next || next === current) return;
    var fd = new FormData(); fd.append('_token', _csrfToken); fd.append('message_id', id); fd.append('message', next);
    fetch('/api/chat?action=edit_message', { method:'POST', body: fd })
    .then(function(r){return r.json();}).then(function(d){
        if (d.error) { alert(d.error); return; }
        _lastId=0; _renderedIds={}; _lastDateLabel=''; loadMessages(0);
    });
}

function deleteMsg(id) {
    if (!confirm('Delete this message permanently?')) return;
    var fd = new FormData(); fd.append('_token', _csrfToken); fd.append('message_id', id);
    fetch('/api/chat?action=delete_message', { method:'POST', body: fd })
    .then(function(r){return r.json();}).then(function(d){
        if (d.error) { alert(d.error); return; }
        var row = document.querySelector('[data-id="'+id+'"]'); if (row) row.remove();
        delete _renderedIds[id];
    });
}

function clearHistory() {
    if (!_selConvId) { alert('Open a conversation first.'); return; }
    if (!confirm('Clear ALL messages in this conversation? This cannot be undone.')) return;
    var fd = new FormData(); fd.append('_token', _csrfToken); fd.append('conversation_id', _selConvId);
    fetch('/api/chat?action=clear_history', { method:'POST', body: fd })
    .then(function(r){return r.json();}).then(function(d){
        if (d.error) { alert(d.error); return; }
        document.getElementById('chat-messages').innerHTML=''; _lastId=0; _renderedIds={}; _lastDateLabel='';
        loadConversations();
    });
}

function closeConv() {
    if (!_selConvId) return;
    if (!confirm('Mark this conversation as solved and close it?')) return;
    var fd = new FormData(); fd.append('_token', _csrfToken); fd.append('conversation_id', _selConvId);
    fetch('/api/chat?action=close_conversation', { method:'POST', body: fd })
    .then(function(r){return r.json();}).then(function(d){
        if (d.error) { alert(d.error); return; }
        _selStatus='closed'; _lastId=0; _renderedIds={}; _lastDateLabel='';
        loadMessages(0); loadConversations();
    });
}

function reopenConv() {
    if (!_selConvId) return;
    if (!confirm('Reopen this closed conversation?')) return;
    var fd = new FormData(); fd.append('_token', _csrfToken); fd.append('conversation_id', _selConvId);
    fetch('/api/chat?action=reopen_conversation', { method:'POST', body: fd })
    .then(function(r){return r.json();}).then(function(d){
        if (d.error) { alert(d.error); return; }
        _selStatus='open'; _lastId=0; _renderedIds={}; _lastDateLabel='';
        loadMessages(0); loadConversations();
    });
}

function chatKeyDown(e) { if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); sendMessage(); } }

function translateText() {
    var ta = document.getElementById('chat-input'); var text = ta.value.trim(); if (!text) return;
    var target = document.getElementById('chat-translate-lang').value;
    var url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=" + target + "&dt=t&q=" + encodeURIComponent(text);
    var btn = document.getElementById('chat-translate-btn'); var oldHtml = btn.innerHTML;
    btn.innerHTML = '...'; btn.disabled = true;
    fetch(url).then(function(r){return r.json();}).then(function(data){
        var t=""; if (data&&data[0]) { for(var i=0;i<data[0].length;i++) t+=data[0][i][0]; ta.value=t; }
    }).catch(function(){alert('Translation failed.');}).finally(function(){btn.innerHTML=oldHtml;btn.disabled=false;ta.focus();});
}

function translateAllMessages() {
    var target = document.getElementById('chat-read-translate-lang').value;
    var msgs = document.querySelectorAll('.ac-msg-text');
    var btn = document.getElementById('chat-read-translate-btn');
    if (!msgs.length) return;
    var oldText = btn.innerHTML; btn.innerHTML = '...'; btn.disabled = true;
    var promises = [];
    msgs.forEach(function(el) {
        var text = el.getAttribute('data-orig'); if (!text || !text.trim()) return;
        var url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=" + target + "&dt=t&q=" + encodeURIComponent(text);
        var p = fetch(url).then(function(r){return r.json();}).then(function(data){
            if (data&&data[0]) { var t=""; for(var i=0;i<data[0].length;i++) t+=data[0][i][0]; el.innerHTML=escHtml(t).replace(/\n/g,'<br>'); }
        }).catch(function(){});
        promises.push(p);
    });
    Promise.all(promises).finally(function(){btn.innerHTML=oldText;btn.disabled=false;});
}

loadConversations();
_convPoll = setInterval(loadConversations, 12000);

<?php if ($selAffId): ?>
(function(){
    <?php $deepOwnerType = (Helpers::get('owner_type') === 'advertiser') ? 'advertiser' : 'affiliate'; ?>
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
