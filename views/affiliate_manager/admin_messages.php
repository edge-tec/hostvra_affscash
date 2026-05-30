<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<style>
.mm-card{background:#fff;border:1px solid #E2E8F0;border-radius:14px;overflow:hidden;display:flex;flex-direction:column;min-height:600px;}
.mm-head{padding:14px 18px;border-bottom:1px solid #E2E8F0;display:flex;align-items:center;justify-content:space-between;}
.mm-head h2{margin:0;font-size:15px;font-weight:800;display:flex;align-items:center;gap:10px}
.mm-head h2::before{content:"";width:6px;height:18px;border-radius:3px;background:linear-gradient(135deg,#7C3AED,#4F46E5)}
.mm-stream{flex:1;overflow-y:auto;padding:18px;background:#F8FAFC;display:flex;flex-direction:column;gap:10px;max-height:520px}
.mm-bubble{max-width:75%;padding:9px 14px;border-radius:14px;font-size:13.5px;line-height:1.5;box-shadow:0 1px 2px rgba(15,23,42,.04);white-space:pre-wrap;word-break:break-word}
.mm-bubble.manager{align-self:flex-end;background:linear-gradient(135deg,#0F766E,#0891B2);color:#fff;border-bottom-right-radius:4px;}
.mm-bubble.admin{align-self:flex-start;background:#fff;border:1px solid #E2E8F0;color:#0F172A;border-bottom-left-radius:4px;}
.mm-bubble .mm-time{display:block;margin-top:4px;font-size:10.5px;opacity:.65;}
.mm-bubble a.mm-attach{display:inline-flex;align-items:center;gap:6px;margin-top:6px;padding:6px 10px;border-radius:8px;background:rgba(255,255,255,.18);color:inherit;text-decoration:none;font-size:12px}
.mm-bubble.admin a.mm-attach{background:#F1F5F9;color:#0F172A;}
.mm-empty{padding:60px 20px;text-align:center;color:#94A3B8}
.mm-compose{padding:12px;border-top:1px solid #E2E8F0;background:#fff}
.mm-compose textarea{width:100%;border:1px solid #E2E8F0;border-radius:8px;padding:10px 12px;font-size:13.5px;resize:vertical;min-height:80px;font-family:inherit}
.mm-row{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:8px}
</style>

<div class="page-header">
    <div>
        <h1>Messages with Admin</h1>
        <p>Direct line to the admin team. Attach files up to 5 MB.</p>
    </div>
    <a href="/affiliate_manager/dashboard" class="btn btn-secondary btn-sm">← Dashboard</a>
</div>

<div class="mm-card">
    <div class="mm-head"><h2>Admin Inbox</h2></div>
    <div class="mm-stream" id="mmStream">
        <?php if (empty($messages)): ?>
        <div class="mm-empty">
            <div style="font-size:30px;opacity:.55;margin-bottom:6px">📨</div>
            <div>No messages yet — say hello below.</div>
        </div>
        <?php else: foreach ($messages as $m): ?>
        <div class="mm-bubble <?= $m['sender_role'] === 'manager' ? 'manager' : 'admin' ?>">
            <?= nl2br(Helpers::e($m['body'])) ?>
            <?php if (!empty($m['attachment_path'])): ?>
            <a class="mm-attach" href="<?= Helpers::e($m['attachment_path']) ?>" target="_blank" rel="noopener">
                📎 <?= Helpers::e($m['attachment_name'] ?: 'attachment') ?>
            </a>
            <?php endif; ?>
            <span class="mm-time"><?= Helpers::e(date('M j, Y H:i', strtotime($m['created_at']))) ?></span>
        </div>
        <?php endforeach; endif; ?>
    </div>
    <form class="mm-compose" method="POST" enctype="multipart/form-data">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="action" value="send">
        <textarea name="body" placeholder="Type your message to admin…" required></textarea>
        <div class="mm-row">
            <input type="file" name="attachment" accept="image/png,image/jpeg,image/gif,image/webp,application/pdf,text/plain">
            <button class="btn btn-primary btn-sm" type="submit">Send →</button>
        </div>
    </form>
</div>

<script>
(function(){ var s = document.getElementById('mmStream'); if (s) s.scrollTop = s.scrollHeight; })();
</script>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
