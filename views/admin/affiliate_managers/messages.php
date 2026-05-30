<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<style>
.mm-shell{display:grid;grid-template-columns:300px 1fr;gap:14px;height:calc(100vh - 220px);min-height:560px;}
.mm-list{background:var(--card-bg);border:1px solid var(--border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;}
.mm-list h3{margin:0;padding:14px 16px;font-size:13px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border);}
.mm-list .mm-scroll{flex:1;overflow-y:auto;}
.mm-thread{display:block;padding:12px 14px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);}
.mm-thread:hover{background:var(--bg);}
.mm-thread.active{background:linear-gradient(135deg,#EEF2FF,#F5F3FF);}
.mm-thread .mm-name{font-weight:700;font-size:13px;display:flex;justify-content:space-between;align-items:center;gap:8px}
.mm-thread .mm-prev{font-size:11.5px;color:var(--text-muted);margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.mm-badge{display:inline-block;min-width:18px;padding:1px 7px;border-radius:99px;background:#EF4444;color:#fff;font-size:10px;font-weight:800;text-align:center}
.mm-panel{background:var(--card-bg);border:1px solid var(--border);border-radius:12px;display:flex;flex-direction:column;overflow:hidden;}
.mm-panel-head{padding:14px 18px;border-bottom:1px solid var(--border);font-weight:700;display:flex;justify-content:space-between;align-items:center;}
.mm-panel-head .meta{font-size:12px;color:var(--text-muted);font-weight:500}
.mm-stream{flex:1;overflow-y:auto;padding:16px 18px;background:#F8FAFC;display:flex;flex-direction:column;gap:10px}
.mm-bubble{max-width:75%;padding:9px 14px;border-radius:14px;font-size:13.5px;line-height:1.5;box-shadow:0 1px 2px rgba(15,23,42,.04);white-space:pre-wrap;word-break:break-word}
.mm-bubble.admin{align-self:flex-end;background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;border-bottom-right-radius:4px;}
.mm-bubble.manager{align-self:flex-start;background:#fff;border:1px solid #E2E8F0;color:#0F172A;border-bottom-left-radius:4px;}
.mm-bubble .mm-time{display:block;margin-top:4px;font-size:10.5px;opacity:.65;}
.mm-bubble a.mm-attach{display:inline-flex;align-items:center;gap:6px;margin-top:6px;padding:6px 10px;border-radius:8px;background:rgba(255,255,255,.18);color:inherit;text-decoration:none;font-size:12px}
.mm-bubble.manager a.mm-attach{background:#F1F5F9;color:#0F172A;}
.mm-empty{padding:60px 20px;text-align:center;color:var(--text-muted);}
.mm-compose{padding:12px;border-top:1px solid var(--border);background:#fff}
.mm-compose textarea{width:100%;border:1px solid var(--border);border-radius:8px;padding:10px 12px;font-size:13.5px;resize:vertical;min-height:80px;font-family:inherit;}
.mm-compose .mm-row{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:8px;}
.mm-compose input[type=file]{font-size:12px}
@media(max-width:900px){ .mm-shell{grid-template-columns:1fr;height:auto} .mm-list{max-height:280px} }
</style>

<div class="page-header">
    <div>
        <h1>Affiliate Manager Messages</h1>
        <p>Direct inbox — reply to any manager. Unread counts update on every reload.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="/admin/affiliate-managers/permissions" class="btn btn-secondary btn-sm">Permissions</a>
        <a href="/admin/affiliate-managers/fraud-rejections" class="btn btn-secondary btn-sm">Fraud Rejections</a>
    </div>
</div>

<div class="mm-shell">
    <aside class="mm-list">
        <h3>Threads <?php $total = 0; foreach ($threads as $t) $total += (int)$t['unread']; ?><?php if ($total > 0): ?><span style="float:right;color:#EF4444;font-weight:700"><?= (int)$total ?> new</span><?php endif; ?></h3>
        <div class="mm-scroll">
            <?php if (empty($threads)): ?>
            <div style="padding:20px;color:var(--text-muted);font-size:13px">No managers yet.</div>
            <?php else: foreach ($threads as $t):
                $isActive = (int)$t['manager_id'] === $activeMgr;
                $unread   = (int)$t['unread'];
            ?>
            <a class="mm-thread <?= $isActive ? 'active' : '' ?>" href="/admin/affiliate-managers/messages?manager_id=<?= (int)$t['manager_id'] ?>">
                <div class="mm-name">
                    <span><?= Helpers::e($t['name']) ?></span>
                    <?php if ($unread > 0): ?><span class="mm-badge"><?= $unread ?></span><?php endif; ?>
                </div>
                <div class="mm-prev"><?= Helpers::e($t['last_body'] ?? '— no messages yet —') ?></div>
                <?php if (!empty($t['last_at'])): ?>
                <div style="font-size:10.5px;color:#94A3B8;margin-top:3px"><?= Helpers::e(date('M j H:i', strtotime($t['last_at']))) ?></div>
                <?php endif; ?>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </aside>

    <section class="mm-panel">
        <?php if (!$activeMgr || !$activeMgrMeta): ?>
        <div class="mm-empty">
            <div style="font-size:30px;opacity:.55;margin-bottom:6px">💬</div>
            <div>Select a thread on the left to view the conversation.</div>
        </div>
        <?php else: ?>
        <div class="mm-panel-head">
            <div><?= Helpers::e($activeMgrMeta['name']) ?><span class="meta" style="margin-left:8px"><?= Helpers::e($activeMgrMeta['email']) ?></span></div>
        </div>
        <div class="mm-stream" id="mmStream">
            <?php if (empty($messages)): ?>
            <div class="mm-empty">No messages yet — send the first reply below.</div>
            <?php else: foreach ($messages as $m): ?>
            <div class="mm-bubble <?= $m['sender_role'] === 'admin' ? 'admin' : 'manager' ?>">
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
            <input type="hidden" name="manager_id" value="<?= (int)$activeMgr ?>">
            <textarea name="body" placeholder="Write a reply to <?= Helpers::e($activeMgrMeta['name']) ?>…" required></textarea>
            <div class="mm-row">
                <input type="file" name="attachment" accept="image/png,image/jpeg,image/gif,image/webp,application/pdf,text/plain">
                <button class="btn btn-primary btn-sm" type="submit">Send →</button>
            </div>
        </form>
        <?php endif; ?>
    </section>
</div>

<script>
// Auto-scroll the message stream to the bottom on load + after sending.
(function(){
    var s = document.getElementById('mmStream');
    if (s) s.scrollTop = s.scrollHeight;
})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
