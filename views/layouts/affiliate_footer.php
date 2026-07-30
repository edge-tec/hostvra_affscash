</main>
</div>
</div>
<?php 
$copyright = Config::get('config','app.footer_copyright'); 
$siteLogo = Config::get('config','app.logo');
$favIcon = Config::get('config','app.favicon');
$defaultIcon = '<svg style="width:12px;height:12px;margin-right:4px;vertical-align:middle;color:#94A3B8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
$iconHtml = $favIcon ? '<img src="'.Helpers::e($favIcon).'" style="width:12px;height:12px;margin-right:4px;vertical-align:middle;object-fit:contain">' : $defaultIcon;
?>
<style>
@media (min-width: 769px) { .mobile-footer-logo { display: none !important; } }
.footer-link { color:#64748B; text-decoration:none; display:inline-flex; align-items:center; }
.footer-link:hover { color:#475569; }
</style>
<footer style="text-align:center;padding:20px;font-size:12px;color:#94A3B8;border-top:1px solid #1E293B;background:#0F172A;position:relative;overflow:hidden;">
    <canvas class="footer-canvas" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0"></canvas>
    <script>
    (function() {
        var footer = document.querySelector('footer');
        var canvas = footer ? footer.querySelector('.footer-canvas') : null;
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var w, h;
        function resize() {
            w = canvas.width = canvas.offsetWidth;
            h = canvas.height = canvas.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);
        
        var bg = window.getComputedStyle(footer).backgroundColor;
        var isDark = true;
        if (bg) {
            var rgb = bg.match(/\d+/g);
            if (rgb && rgb.length >= 3) {
                var brightness = (parseInt(rgb[0])*299 + parseInt(rgb[1])*587 + parseInt(rgb[2])*114) / 1000;
                if (brightness > 128) isDark = false;
            }
        }
        var colorNode = isDark ? 'rgba(232, 25, 122, 0.4)' : 'rgba(124, 58, 237, 0.4)';
        var colorLine = isDark ? 'rgba(232, 25, 122, 0.12)' : 'rgba(124, 58, 237, 0.12)';
        
        var nodes = [];
        var density = Math.min(25, Math.floor(w / 50));
        for (var i = 0; i < density; i++) {
            nodes.push({
                x: Math.random() * w,
                y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.3,
                vy: (Math.random() - 0.5) * 0.3,
                r: Math.random() * 2 + 1
            });
        }
        function animate() {
            if (!canvas.offsetParent) {
                requestAnimationFrame(animate);
                return;
            }
            ctx.clearRect(0, 0, w, h);
            ctx.fillStyle = colorNode;
            nodes.forEach(function(n) {
                n.x += n.vx;
                n.y += n.vy;
                if (n.x < 0 || n.x > w) n.vx *= -1;
                if (n.y < 0 || n.y > h) n.vy *= -1;
                ctx.beginPath();
                ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
                ctx.fill();
            });
            ctx.lineWidth = 0.8;
            for (var i = 0; i < nodes.length; i++) {
                for (var j = i + 1; j < nodes.length; j++) {
                    var dx = nodes[i].x - nodes[j].x;
                    var dy = nodes[i].y - nodes[j].y;
                    var dist = Math.hypot(dx, dy);
                    if (dist < 100) {
                        ctx.strokeStyle = colorLine;
                        ctx.beginPath();
                        ctx.moveTo(nodes[i].x, nodes[i].y);
                        ctx.lineTo(nodes[j].x, nodes[j].y);
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(animate);
        }
        animate();
    })();
    </script>
    <div class="mobile-footer-logo" style="margin-bottom:16px;">
        <?php if ($siteLogo): ?>
        <img src="<?= Helpers::e($siteLogo) ?>" alt="Logo" style="max-height:36px; max-width:140px; object-fit:contain; filter:brightness(0) invert(1);">
        <?php else: ?>
        <span style="font-weight:bold; color:#94A3B8; font-size:16px;"><?= Helpers::e(Config::get('config','app.name') ?? 'AffTracker') ?></span>
        <?php endif; ?>
    </div>
    <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:16px; margin-bottom:12px;">
        <a href="/terms-of-service" target="_blank" class="footer-link"><?= $iconHtml ?>Terms & Conditions</a>
        <a href="/privacy-policy" target="_blank" class="footer-link"><?= $iconHtml ?>Privacy Policy</a>
        <a href="/affiliate-agreement" target="_blank" class="footer-link"><?= $iconHtml ?>Affiliate Agreement</a>
        <a href="/anti-fraud-policy" target="_blank" class="footer-link"><?= $iconHtml ?>Anti-Fraud Policy</a>
        <a href="/gdpr-compliance-policy" target="_blank" class="footer-link"><?= $iconHtml ?>GDPR Compliance</a>
        <a href="/refund-payment-policy" target="_blank" class="footer-link"><?= $iconHtml ?>Refund Policy</a>
        <a href="/cookie-policy" target="_blank" class="footer-link"><?= $iconHtml ?>Cookie Policy</a>
        <a href="/dashboard-disclaimers" target="_blank" class="footer-link"><?= $iconHtml ?>Dashboard Disclaimers</a>
    </div>
    <?php if ($copyright): ?>
    <div><?= Helpers::e($copyright) ?></div>
    <?php endif; ?>
</footer>

<?php
// ── Live Chat Widget (skip on the dedicated /affiliate/support page) ───────
$_isOnSupportPage = str_contains($_SERVER['REQUEST_URI'] ?? '', '/affiliate/support');
if (!$_isOnSupportPage):
?>
<style>
.lcw-launcher{position:fixed;bottom:20px;right:20px;width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;border:none;cursor:pointer;box-shadow:0 8px 24px rgba(79,70,229,.4);display:flex;align-items:center;justify-content:center;z-index:9998;transition:transform .15s}
.lcw-launcher:hover{}
.lcw-launcher svg{width:26px;height:26px}
.lcw-badge{position:absolute;top:-2px;right:-2px;background:#EF4444;color:#fff;border-radius:10px;min-width:20px;height:20px;font-size:11px;font-weight:700;display:none;align-items:center;justify-content:center;padding:0 5px;border:2px solid #fff}
.lcw-panel{position:fixed;bottom:88px;right:20px;width:340px;max-width:calc(100vw - 40px);height:480px;max-height:calc(100vh - 120px);background:#fff;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.25);display:none;flex-direction:column;overflow:hidden;z-index:9998;border:1px solid #E2E8F0}
.lcw-panel.open{display:flex}
.lcw-head{background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between}
.lcw-head h4{margin:0;font-size:15px;font-weight:700}
.lcw-status{font-size:11px;margin-top:3px;display:inline-flex;align-items:center;gap:6px;font-weight:600}
.lcw-status .dot{width:7px;height:7px;border-radius:50%;display:inline-block}
.lcw-status.online .dot{background:#4ADE80;box-shadow:0 0 0 3px rgba(74,222,128,.25);animation:lcw-pulse 1.6s infinite}
.lcw-status.offline .dot{background:#94A3B8}
@keyframes lcw-pulse{0%,100%{box-shadow:0 0 0 3px rgba(74,222,128,.25)}50%{box-shadow:0 0 0 6px rgba(74,222,128,.05)}}
.lcw-close{background:none;border:none;color:#fff;cursor:pointer;font-size:22px;line-height:1;padding:0 4px;opacity:.85}
.lcw-close:hover{opacity:1}
.lcw-body{flex:1;overflow-y:auto;padding:14px;background:#F8FAFC;display:flex;flex-direction:column;gap:8px}
.lcw-msg{max-width:80%;padding:8px 12px;border-radius:12px;font-size:13px;line-height:1.4;word-wrap:break-word;white-space:pre-wrap}
.lcw-msg.them{background:#fff;border:1px solid #E2E8F0;color:#1E293B;align-self:flex-start;border-bottom-left-radius:4px}
.lcw-msg.me{background:#4F46E5;color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.lcw-msg.welcome{background:#F5F3FF;border:1px solid #DDD6FE;color:#4338CA;align-self:flex-start;border-bottom-left-radius:4px;max-width:90%}
.lcw-meta{font-size:10px;color:#94A3B8;margin-top:2px}
.lcw-msg.me + .lcw-meta{align-self:flex-end}
.lcw-empty{text-align:center;color:#94A3B8;font-size:12px;padding:32px 16px}
.lcw-input{display:flex;gap:6px;padding:10px;border-top:1px solid #E2E8F0;background:#fff;align-items:flex-end}
.lcw-input textarea{flex:1;border:1px solid #CBD5E1;border-radius:8px;padding:8px 10px;font-size:13px;resize:none;font-family:inherit;outline:none;height:38px;max-height:80px}
.lcw-input textarea:focus{border-color:#4F46E5}
.lcw-send{background:#4F46E5;color:#fff;border:none;border-radius:8px;padding:0 14px;font-weight:600;font-size:13px;cursor:pointer;height:38px}
.lcw-send:disabled{opacity:.5;cursor:not-allowed}
/* Attachment paperclip button */
.lcw-attach{background:#F1F5F9;border:1px solid #CBD5E1;border-radius:8px;width:38px;height:38px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#475569;flex-shrink:0;transition:background .15s}
.lcw-attach:hover{background:#E2E8F0;color:#0F172A}
.lcw-attach svg{width:18px;height:18px}
.lcw-attach-prev{display:none;font-size:12px;color:#475569;background:#EEF2FF;border:1px solid #C7D2FE;border-radius:8px;padding:6px 10px;margin:0 10px 8px;color:#3730A3}
.lcw-attach-prev .x{color:#DC2626;text-decoration:none;margin-left:6px;cursor:pointer}
/* Edit hover button on own messages */
.lcw-msg-wrap{display:flex;flex-direction:column;align-items:flex-end;position:relative}
.lcw-msg-wrap.them{align-items:flex-start}
.lcw-edit{display:none;position:absolute;top:-4px;right:-4px;background:#fff;border:1px solid #CBD5E1;border-radius:50%;width:22px;height:22px;cursor:pointer;font-size:11px;color:#475569;line-height:1;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,.1)}
.lcw-edit:hover{color:#4F46E5;border-color:#4F46E5}
.lcw-msg-wrap.me:hover .lcw-edit{display:flex}
/* Inline attachment rendering inside a bubble */
.lcw-att-img{display:block;max-width:200px;max-height:180px;border-radius:8px;margin-top:4px}
.lcw-att-file{display:inline-flex;align-items:center;gap:6px;margin-top:4px;padding:5px 9px;background:rgba(255,255,255,.2);border-radius:6px;color:inherit;text-decoration:none;font-size:11px}
.lcw-msg.them .lcw-att-file{background:#F1F5F9;color:#1E293B}
@media (max-width:480px){.lcw-panel{right:10px;left:10px;width:auto;bottom:80px}.lcw-launcher{right:14px;bottom:14px}}
</style>

<button id="lcw-launcher" class="lcw-launcher" type="button" aria-label="Open live chat" onclick="lcwToggle()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    <span id="lcw-badge" class="lcw-badge">0</span>
</button>

<div id="lcw-panel" class="lcw-panel" role="dialog" aria-label="Live chat with support">
    <div class="lcw-head">
        <div style="flex:1">
            <h4>Live Support</h4>
            <span id="lcw-status" class="lcw-status offline"><span class="dot"></span><span id="lcw-status-text">Support Offline</span></span>
        </div>
        <div style="display:flex;gap:4px;align-items:center;margin-right:12px">
            <select id="lcw-read-lang" style="background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:11px;border-radius:4px;padding:2px 4px;outline:none;cursor:pointer">
                <option value="en" style="color:#000">EN</option>
                <option value="bn" style="color:#000">BN</option>
                <option value="hi" style="color:#000">HI</option>
                <option value="ur" style="color:#000">UR</option>
                <option value="es" style="color:#000">ES</option>
                <option value="fr" style="color:#000">FR</option>
                <option value="ar" style="color:#000">AR</option>
                <option value="ru" style="color:#000">RU</option>
            </select>
            <button type="button" id="lcw-read-btn" onclick="lcwTranslateAll()" style="background:rgba(255,255,255,0.2);border:none;color:#fff;font-size:11px;border-radius:4px;padding:3px 6px;cursor:pointer;font-weight:600" title="Translate all messages">Translate</button>
        </div>
        <button class="lcw-close" type="button" onclick="lcwToggle()" aria-label="Close">&times;</button>
    </div>
    <div id="lcw-body" class="lcw-body"><div class="lcw-empty">Loading messages…</div></div>
    <div id="lcw-attach-prev" class="lcw-attach-prev"></div>
    <form class="lcw-input" onsubmit="lcwSend(event)" style="flex-wrap:wrap">
        <label class="lcw-attach" title="Attach file (JPG, PNG, WEBP, PDF, CSV — max 10 MB)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            <input type="file" id="lcw-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.csv,image/jpeg,image/png,image/webp,application/pdf,text/csv" style="display:none" onchange="lcwAttach(this)">
        </label>
        
        <div style="display:flex;align-items:center;background:#F1F5F9;border:1px solid #CBD5E1;border-radius:8px;overflow:hidden;height:38px;margin-right:2px">
            <select id="lcw-translate-lang" style="background:none;border:none;outline:none;font-size:11px;padding:0 4px;color:#475569;font-weight:600;cursor:pointer">
                <option value="en">EN</option>
                <option value="bn">BN</option>
                <option value="hi">HI</option>
                <option value="ur">UR</option>
                <option value="es">ES</option>
                <option value="fr">FR</option>
                <option value="ar">AR</option>
                <option value="ru">RU</option>
            </select>
            <button type="button" id="lcw-translate-btn" onclick="lcwTranslateText()" style="background:#E2E8F0;border:none;border-left:1px solid #CBD5E1;height:100%;padding:0 8px;font-size:12px;color:#0F172A;cursor:pointer;font-weight:600" title="Translate text">A&rarr;あ</button>
        </div>

        <textarea id="lcw-text" placeholder="Type your message…" onkeydown="lcwKey(event)"></textarea>
        <button type="submit" id="lcw-send" class="lcw-send">Send</button>
    </form>
</div>

<script>
(function(){
    <?php
    // The currently logged-in user's first name. Falls back to a friendly default
    // when not available so the welcome message never renders "Hello dear ,".
    $_lcwUser   = Auth::currentUser();
    $_lcwName   = trim((string)($_lcwUser['first_name'] ?? ''));
    if ($_lcwName === '') $_lcwName = 'there';
    ?>
    var _lcwLastId    = 0;
    var _lcwOpen      = false;
    var _lcwLoaded    = false;
    var _lcwToken     = '<?= Auth::generateCsrf() ?>';
    var _lcwUserName  = <?= json_encode($_lcwName) ?>;
    var _lcwPolling   = null;
    var _lcwSupportOnline = false;

    function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function fmtTime(s){ try{ var d=new Date((s||'').replace(' ','T')); return d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}); } catch(e){ return ''; } }

    function welcomeText() {
        return 'Hello ' + _lcwUserName + ',\n\n'
             + 'Welcome to AffsCash Live Chat Support. We’re here to help you.\n'
             + 'How can I assist you today?';
    }

    window.lcwTranslateText = function() {
        var ta = document.getElementById('lcw-text');
        var text = ta.value.trim();
        if (!text) return;
        var target = document.getElementById('lcw-translate-lang').value;
        var url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=" + target + "&dt=t&q=" + encodeURIComponent(text);
        
        var btn = document.getElementById('lcw-translate-btn');
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
    };

    window.lcwTranslateAll = function() {
        var target = document.getElementById('lcw-read-lang').value;
        var msgs = document.querySelectorAll('.lcw-msg-text');
        var btn = document.getElementById('lcw-read-btn');
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
                    el.textContent = translated;
                }
            }).catch(function(e){});
            promises.push(p);
        });
        
        Promise.all(promises).finally(function(){
            btn.innerHTML = oldText;
            btn.disabled = false;
        });
    };

    window.lcwToggle = function(){
        _lcwOpen = !_lcwOpen;
        var p = document.getElementById('lcw-panel');
        if (!p) return;
        p.classList.toggle('open', _lcwOpen);
        if (_lcwOpen) {
            lcwLoadMessages(true);
            lcwUpdateSupportStatus();
            setTimeout(function(){ var t=document.getElementById('lcw-text'); if(t) t.focus(); }, 100);
        }
    };

    window.lcwKey = function(e){
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); lcwSend(e); }
    };

    // Holds a pending attachment_id (from /api/chat?action=upload) until the
    // next call to lcwSend, which links it to the new message and clears it.
    var _lcwAttPending = null;

    window.lcwSend = function(e){
        if (e && e.preventDefault) e.preventDefault();
        var ta  = document.getElementById('lcw-text');
        var btn = document.getElementById('lcw-send');
        if (!ta) return;
        var msg = ta.value.trim();
        if (!msg && !_lcwAttPending) return;
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action','send');
        fd.append('message', msg);
        fd.append('_token', _lcwToken);
        if (_lcwAttPending && _lcwAttPending.attachment_id) {
            fd.append('attachment_id', _lcwAttPending.attachment_id);
        }
        fetch('/api/chat?action=send', { method:'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false;
                if (d && d.ok) {
                    ta.value = '';
                    _lcwAttPending = null;
                    var prev = document.getElementById('lcw-attach-prev');
                    if (prev) { prev.style.display='none'; prev.innerHTML=''; }
                    lcwLoadMessages(false);
                }
                else if (d && d.error) { alert(d.error); }
            })
            .catch(function(){ btn.disabled = false; });
    };

    // Attachment upload — uploads file then stashes attachment_id for the next send.
    window.lcwAttach = function(input){
        var file = input.files && input.files[0];
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) { alert('File is larger than 10 MB.'); input.value=''; return; }
        var fd = new FormData();
        fd.append('_token', _lcwToken);
        fd.append('file', file);
        var prev = document.getElementById('lcw-attach-prev');
        if (prev) { prev.style.display='block'; prev.textContent = 'Uploading ' + file.name + '…'; }
        fetch('/api/chat?action=upload', { method:'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!prev) return;
                if (d && d.error) {
                    prev.innerHTML = '⚠ ' + esc(d.error);
                    prev.style.background='#FEE2E2'; prev.style.color='#991B1B';
                    return;
                }
                _lcwAttPending = d;
                prev.innerHTML = '📎 <strong>' + esc(d.attachment_name) + '</strong> '
                    + '<a class="x" onclick="lcwCancelAttach();return false">×</a>';
                prev.style.background='#EEF2FF'; prev.style.color='#3730A3';
            })
            .catch(function(){
                if (prev) { prev.textContent = 'Upload failed.'; prev.style.background='#FEE2E2'; prev.style.color='#991B1B'; }
            });
        input.value = '';
    };
    window.lcwCancelAttach = function(){
        _lcwAttPending = null;
        var prev = document.getElementById('lcw-attach-prev');
        if (prev) { prev.style.display='none'; prev.innerHTML=''; }
    };

    // Edit an own text message — affiliates can revise typos / wording.
    window.lcwEditMsg = function(id) {
        var bubble = document.getElementById('lcw-bubble-' + id);
        var current = bubble ? (bubble.dataset.text || '') : '';
        var next = prompt('Edit your message:', current);
        if (next == null) return;
        next = String(next).trim();
        if (!next || next === current) return;
        var fd = new FormData();
        fd.append('_token', _lcwToken);
        fd.append('message_id', id);
        fd.append('message', next);
        fetch('/api/chat?action=edit_message', { method:'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d && d.error) { alert(d.error); return; }
                lcwLoadMessages(false);
            });
    };

    // Render an automated welcome bubble (client-side only — never persisted).
    // Shown when the conversation is empty so the very first thing the affiliate
    // sees is a friendly greeting using their first name.
    function appendWelcome(body){
        var bub = document.createElement('div');
        bub.className = 'lcw-msg welcome lcw-msg-text';
        bub.dataset.orig = welcomeText();
        bub.textContent = welcomeText();
        body.appendChild(bub);
        var meta = document.createElement('div');
        meta.className = 'lcw-meta';
        meta.style.alignSelf = 'flex-start';
        meta.textContent = 'Support';
        body.appendChild(meta);
    }

    function lcwLoadMessages(scrollToEnd){
        fetch('/api/chat?action=messages')
            .then(function(r){ return r.json(); })
            .then(function(d){
                var body = document.getElementById('lcw-body');
                if (!body) return;
                var msgs = (d && d.messages) || [];
                body.innerHTML = '';
                // Always lead with the friendly welcome bubble — independent of message history.
                appendWelcome(body);
                // Closed-conversation banner — affiliates open a new ticket on their next message.
                if (d && d.conversation && d.conversation.status === 'closed') {
                    var cl = document.createElement('div');
                    cl.className = 'lcw-msg welcome';
                    cl.style.background = '#FEF3C7'; cl.style.borderColor = '#FDE68A'; cl.style.color = '#92400E';
                    cl.textContent = '✓ This conversation has been marked Solved. Send a new message below to start a new ticket.';
                    body.appendChild(cl);
                }
                msgs.forEach(function(m){
                    var mine = (m.sender_role === 'affiliate');
                    // Wrapper so we can position the edit pencil over the bubble
                    // and align right/left without disturbing the gap spacing.
                    var wrap = document.createElement('div');
                    wrap.className = 'lcw-msg-wrap ' + (mine ? 'me' : 'them');

                    var bub = document.createElement('div');
                    bub.className = 'lcw-msg ' + (mine ? 'me' : 'them');
                    bub.id = 'lcw-bubble-' + m.id;
                    if (m.message) {
                        var textNode = document.createElement('div');
                        textNode.className = 'lcw-msg-text';
                        textNode.dataset.orig = m.message;
                        textNode.textContent = m.message;
                        bub.appendChild(textNode);
                        bub.dataset.text = m.message; // pristine source for the edit prompt
                    }
                    // Inline attachment rendering — images previewed, others
                    // shown as a paperclip + filename + size pill so the bubble
                    // always has visible content (never a blank pill).
                    if (m.attachment_path) {
                        var dl   = '/api/chat?action=download&id=' + m.id;
                        var name = m.attachment_name || 'file';
                        var mt   = (m.attachment_type || '').toLowerCase();
                        if (mt.indexOf('image/') === 0) {
                            var aWrap = document.createElement('a');
                            aWrap.href = dl; aWrap.target = '_blank';
                            aWrap.style.cssText = 'display:block;margin-top:'+(m.message?'6px':'0');
                            var img = document.createElement('img');
                            img.src = dl; img.alt = name; img.className = 'lcw-att-img';
                            // Graceful fallback if preview fails (e.g. permission/server error).
                            img.onerror = function(){
                                this.style.display = 'none';
                                var fb = document.createElement('span');
                                fb.style.cssText = 'font-size:11px;opacity:.7';
                                fb.textContent = '📎 ' + name + ' (preview unavailable)';
                                aWrap.appendChild(fb);
                            };
                            aWrap.appendChild(img);
                            bub.appendChild(aWrap);
                        } else {
                            // Derive a short extension pill from the filename.
                            var dot = name.lastIndexOf('.');
                            var ext = (dot > -1 && dot < name.length - 1) ? name.substring(dot+1).toUpperCase().substr(0,4) : 'FILE';
                            var sizeTxt = '';
                            if (m.attachment_size) {
                                var b = +m.attachment_size || 0;
                                sizeTxt = b < 1024 ? b + ' B' : (b < 1048576 ? (b/1024).toFixed(1)+' KB' : (b/1048576).toFixed(1)+' MB');
                            }
                            var a = document.createElement('a');
                            a.href = dl; a.target = '_blank';
                            a.className = 'lcw-att-file';
                            a.style.marginTop = m.message ? '6px' : '0';
                            a.innerHTML = '<span style="display:inline-block;background:rgba(0,0,0,.08);padding:2px 5px;border-radius:4px;font-weight:700;font-size:10px">'+esc(ext)+'</span>' +
                                          ' <span>'+esc(name)+'</span>' +
                                          (sizeTxt ? ' <span style="opacity:.7">· '+esc(sizeTxt)+'</span>' : '');
                            bub.appendChild(a);
                        }
                    }
                    wrap.appendChild(bub);

                    // Pencil edit button only on own text messages (no edits on attachments).
                    if (mine && m.message && (m.message + '').trim() !== '') {
                        var ed = document.createElement('button');
                        ed.className = 'lcw-edit';
                        ed.type = 'button';
                        ed.title = 'Edit message';
                        ed.textContent = '✎';
                        ed.onclick = function(){ lcwEditMsg(m.id); };
                        wrap.appendChild(ed);
                    }

                    body.appendChild(wrap);

                    var meta = document.createElement('div');
                    meta.className = 'lcw-meta';
                    meta.style.alignSelf = mine ? 'flex-end' : 'flex-start';
                    var editedTag = m.edited_at ? ' · (edited)' : '';
                    meta.textContent = (mine ? 'You' : (m.sender_name || 'Support')) + ' · ' + fmtTime(m.created_at) + editedTag;
                    body.appendChild(meta);
                    if (m.id > _lcwLastId) _lcwLastId = m.id;
                });
                if (scrollToEnd || _lcwOpen) body.scrollTop = body.scrollHeight;
                _lcwLoaded = true;
                lcwUpdateBadge();
            })
            .catch(function(){});
    }

    function lcwUpdateBadge(){
        fetch('/api/chat?action=unread_count')
            .then(function(r){ return r.json(); })
            .then(function(d){
                var b = document.getElementById('lcw-badge');
                if (!b) return;
                var c = (d && d.count) || 0;
                if (c > 0) { b.textContent = c > 9 ? '9+' : c; b.style.display = 'flex'; }
                else b.style.display = 'none';
            })
            .catch(function(){});
    }

    // Refresh the green/grey "Support Online / Support Offline" pill in the header.
    // Backed by /api/chat?action=support_status which checks if any admin has
    // pinged /api/notifications within the last 3 minutes.
    function lcwUpdateSupportStatus(){
        fetch('/api/chat?action=support_status')
            .then(function(r){ return r.json(); })
            .then(function(d){
                var online = !!(d && d.online);
                _lcwSupportOnline = online;
                var box  = document.getElementById('lcw-status');
                var text = document.getElementById('lcw-status-text');
                if (!box || !text) return;
                box.classList.toggle('online',  online);
                box.classList.toggle('offline', !online);
                text.textContent = online ? 'Support Online' : 'Support Offline';
            })
            .catch(function(){});
    }

    // Initial badge + status; poll every 30 s. When panel is open also reload
    // messages and refresh the online indicator.
    lcwUpdateBadge();
    lcwUpdateSupportStatus();
    _lcwPolling = setInterval(function(){
        if (_lcwOpen) { lcwLoadMessages(false); lcwUpdateSupportStatus(); }
        else         { lcwUpdateBadge();        lcwUpdateSupportStatus(); }
    }, 30000);
})();
</script>
<?php endif; ?>

<script src="/assets/js/app.min.js"></script>
<?php $egt = Config::get('config', 'app.enable_gtranslate'); if ($egt === null || $egt == 1): ?>
<!-- GTranslate: https://gtranslate.io/ -->
<style>.gtranslate_wrapper { zoom: 0.75; }</style>
<div class="gtranslate_wrapper"></div>
<script>window.gtranslateSettings = {"default_language":"en","detect_browser_language":true,"languages":["en","fr","de","it","es","pt","nl","hi","ur","bn","ru","pl"],"wrapper_selector":".gtranslate_wrapper","float_switcher_open_direction":"top","float_position":"bottom-left"}</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>
<?php endif; ?>
</body>
</html>
