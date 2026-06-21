<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div><h1>&#128172; Support Inbox</h1><p>Live chat with your affiliates</p></div>
</div>

<div style="display:grid;grid-template-columns:300px 1fr;gap:16px;height:calc(100vh - 200px);min-height:500px">

    <!-- Left: Conversation List -->
    <div class="card" style="display:flex;flex-direction:column;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--border);font-weight:700;font-size:13px;background:#F8FAFC">
            Conversations
            <span id="inbox-unread-badge" style="display:none;background:#EF4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:6px">0</span>
        </div>
        <div id="conv-list" style="flex:1;overflow-y:auto">
            <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px">Loading...</div>
        </div>
    </div>

    <!-- Right: Chat Panel -->
    <div class="card" style="display:flex;flex-direction:column;overflow:hidden" id="chat-panel">
        <div id="chat-empty" style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:var(--text-muted)">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span>Select a conversation</span>
        </div>

        <!-- Chat Header -->
        <div id="chat-header" style="display:none;padding:14px 20px;border-bottom:1px solid var(--border);align-items:center;gap:12px;background:#F8FAFC;flex-wrap:wrap">
            <div id="chat-avatar" style="width:40px;height:40px;background:linear-gradient(135deg,#0EA5E9,#3B82F6);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;flex-shrink:0"></div>
            <div style="flex:1;min-width:120px">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <div id="chat-name" style="font-weight:700;font-size:14px;color:#111827"></div>
                    <span id="chat-status-badge" class="am-status-badge open" style="display:none">Open</span>
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
            </div>
        </div>

        <!-- Messages -->
        <div id="chat-messages" style="display:none;flex:1;overflow-y:auto;padding:20px;flex-direction:column;gap:12px;background:#FAFBFC"></div>

        <!-- Input -->
        <div id="chat-input-area" style="display:none;padding:14px 20px;border-top:1px solid var(--border);background:#fff">
            <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
                
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

                <textarea id="chat-input" class="form-control" rows="2" placeholder="Type a reply..." style="flex:1;resize:none;font-size:13px" onkeydown="chatKeyDown(event)"></textarea>
                <button onclick="sendMessage()" class="btn btn-primary" style="height:52px;padding:0 20px">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Send
                </button>
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Ctrl+Enter to send</div>
        </div>
    </div>
</div>

<script>
var _selAffId   = <?= (int)$selAffId ?>;
var _lastId     = 0;
var _convPoll   = null;
var _msgPoll    = null;
var _myRole     = '<?= Auth::role() ?>';

function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function renderMsg(m) {
    var isMine = m.sender_role !== 'affiliate';
    var time   = new Date(m.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    var div    = document.createElement('div');
    div.style.cssText = 'display:flex;flex-direction:column;align-items:'+(isMine?'flex-end':'flex-start');
    div.innerHTML =
        '<div style="font-size:10px;color:#94A3B8;margin-bottom:3px;'+(isMine?'text-align:right':'')+'">'+
            escHtml(m.sender_name)+' · '+time+
        '</div>'+
        '<div class="am-msg-bubble" id="bubble-'+m.id+'" style="max-width:72%;padding:10px 14px;border-radius:'+(isMine?'14px 14px 4px 14px':'14px 14px 14px 4px')+';'+
            'background:'+(isMine?'#4F46E5':'#fff')+';color:'+(isMine?'#fff':'#1E293B')+';'+
            'font-size:13px;line-height:1.5;box-shadow:0 1px 3px rgba(0,0,0,.08);border:'+(isMine?'none':'1px solid #E2E8F0')+'">'+
            (m.message ? '<div class="am-msg-text" data-orig="'+escHtml(m.message)+'">'+escHtml(m.message).replace(/\n/g,'<br>')+'</div>' : '')+
        '</div>';
    return div;
}

function loadConversations() {
    fetch('/api/chat?action=conversations')
    .then(function(r){return r.json();})
    .then(function(data){
        var list = document.getElementById('conv-list');
        var convs = data.conversations || [];
        if (!convs.length) { list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px">No conversations yet</div>'; return; }
        var totalUnread = 0;
        list.innerHTML = '';
        convs.forEach(function(c){
            totalUnread += (c.unread|0);
            var el = document.createElement('div');
            el.style.cssText = 'padding:12px 16px;cursor:pointer;border-bottom:1px solid #F1F5F9;transition:background .15s;'+((_selAffId==c.affiliate_id)?'background:#EEF2FF':'');
            el.onmouseenter = function(){ if(_selAffId!=c.affiliate_id) this.style.background='#F8FAFC'; };
            el.onmouseleave = function(){ if(_selAffId!=c.affiliate_id) this.style.background=''; };
            el.onclick = function(){ selectConversation(c.affiliate_id, c.name, c.affiliate_code); };
            el.innerHTML =
                '<div style="display:flex;justify-content:space-between;align-items:center">' +
                    '<div style="font-weight:600;font-size:13px">'+escHtml(c.name)+'</div>'+
                    ((c.unread>0)?'<span style="background:#EF4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px">'+c.unread+'</span>':'')+
                '</div>'+
                '<div style="font-size:11px;color:#94A3B8;margin-top:1px">'+escHtml(c.affiliate_code)+'</div>'+
                (c.last_msg?'<div style="font-size:12px;color:var(--text-muted);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px">'+escHtml(c.last_msg)+'</div>':'');
            list.appendChild(el);
        });
        var badge = document.getElementById('inbox-unread-badge');
        if (totalUnread > 0) { badge.textContent = totalUnread; badge.style.display='inline'; }
        else badge.style.display = 'none';
    });
}

function selectConversation(affId, name, code) {
    _selAffId = affId;
    _lastId   = 0;
    if (_msgPoll) clearInterval(_msgPoll);

    document.getElementById('chat-empty').style.display      = 'none';
    document.getElementById('chat-header').style.display     = 'flex';
    document.getElementById('chat-messages').style.display   = 'flex';
    document.getElementById('chat-input-area').style.display = 'block';

    document.getElementById('chat-avatar').textContent = name.charAt(0).toUpperCase();
    document.getElementById('chat-name').textContent   = name;
    document.getElementById('chat-code').textContent   = code;

    document.getElementById('chat-messages').innerHTML = '';
    loadMessages(0);
    _msgPoll = setInterval(function(){ loadMessages(_lastId); }, 5000);
    loadConversations();
}

function loadMessages(since) {
    if (!_selAffId) return;
    fetch('/api/chat?action=messages&affiliate_id='+_selAffId+(since?'&since='+since:''))
    .then(function(r){return r.json();})
    .then(function(data){
        var box = document.getElementById('chat-messages');
        if (!data.messages || !data.messages.length) return;
        var atBottom = box.scrollHeight - box.scrollTop <= box.clientHeight + 60;
        data.messages.forEach(function(m){
            box.appendChild(renderMsg(m));
            if (m.id > _lastId) _lastId = m.id;
        });
        if (!since || atBottom) box.scrollTop = box.scrollHeight;
    })
    .catch(function(e){
        var loadEl = document.getElementById('chat-loading'); if (loadEl) loadEl.style.display = 'none';
        console.error("Chat load error:", e);
    });
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
    if (!msg || !_selAffId) return;
    inp.value = '';
    fetch('/api/chat?action=send', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=send&affiliate_id='+_selAffId+'&message='+encodeURIComponent(msg)
    }).then(function(){ loadMessages(_lastId); });
}

function translateAllMessages() {
    var target = document.getElementById('chat-read-translate-lang').value;
    var msgs = document.querySelectorAll('.am-msg-text');
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

loadConversations();
_convPoll = setInterval(loadConversations, 15000);
<?php if($selAffId): ?>
(function(){
    fetch('/api/chat?action=conversations')
    .then(function(r){return r.json();}).then(function(data){
        var c = (data.conversations||[]).find(function(x){return x.affiliate_id==<?= $selAffId ?>;});
        if (c) selectConversation(c.affiliate_id, c.name, c.affiliate_code);
    });
})();
<?php endif; ?>
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
