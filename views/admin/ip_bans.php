<?php require BASE_PATH . '/views/layouts/admin.php'; ?>
<style>
.ipban-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px; margin-bottom:22px; }
.ipban-add-card { background:#fff; border:1px solid #E5E7EB; border-radius:14px; padding:24px 28px; margin-bottom:24px; }
.ipban-add-card h3 { font-size:15px; font-weight:700; color:#111827; margin:0 0 16px; display:flex; align-items:center; gap:8px; }
.ipban-form-row { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
.ipban-form-row .form-group { margin:0; flex:1; min-width:180px; }
.ipban-form-row label { font-size:12px; font-weight:600; color:#6B7280; margin-bottom:5px; display:block; text-transform:uppercase; letter-spacing:.04em; }
.ipban-form-row input { width:100%; padding:9px 12px; border:1.5px solid #E5E7EB; border-radius:8px; font-size:13px; color:#111827; outline:none; transition:.15s; box-sizing:border-box; }
.ipban-form-row input:focus { border-color:#4F46E5; box-shadow:0 0 0 3px rgba(79,70,229,.08); }
.btn-add-ban { padding:9px 22px; background:#EF4444; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; white-space:nowrap; transition:.15s; }
.btn-add-ban:hover { background:#DC2626; }

.ipban-filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
.ipban-filter-bar input { padding:8px 12px; border:1.5px solid #E5E7EB; border-radius:8px; font-size:13px; width:240px; outline:none; }
.ipban-filter-bar input:focus { border-color:#4F46E5; }

.ipban-table-wrap { overflow-x:auto; }
.ipban-tbl { width:100%; border-collapse:collapse; min-width:700px; }
.ipban-tbl thead th { padding:11px 14px; background:#F9FAFB; font-size:11px; font-weight:700; color:#6B7280; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #E5E7EB; text-align:left; white-space:nowrap; }
.ipban-tbl tbody td { padding:12px 14px; border-bottom:1px solid #F3F4F6; font-size:13px; color:#374151; vertical-align:middle; }
.ipban-tbl tbody tr:hover { background:#FAFBFF; }
.ipban-tbl tbody tr.ban-disabled { opacity:.6; background:#FAFAFA; }
.ipban-empty { text-align:center; padding:48px 20px; color:#9CA3AF; }
.ipban-empty-icon { font-size:40px; margin-bottom:10px; }

.btn-unban  { padding:5px 13px; background:#FEE2E2; color:#DC2626; border:none; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; transition:.15s; }
.btn-unban:hover  { background:#EF4444; color:#fff; }
.btn-enable  { padding:5px 13px; background:#D1FAE5; color:#065F46; border:none; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; transition:.15s; }
.btn-enable:hover  { background:#10B981; color:#fff; }
.btn-disable { padding:5px 13px; background:#FEF3C7; color:#92400E; border:none; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; transition:.15s; }
.btn-disable:hover { background:#F59E0B; color:#fff; }

.ip-code { font-family:monospace; background:#EEF2FF; color:#4F46E5; padding:3px 8px; border-radius:5px; font-size:13px; font-weight:700; }
.ip-code.disabled { background:#F3F4F6; color:#9CA3AF; text-decoration:line-through; }

.badge-active   { display:inline-flex;align-items:center;gap:4px;background:#D1FAE5;color:#065F46;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700; }
.badge-inactive { display:inline-flex;align-items:center;gap:4px;background:#F3F4F6;color:#9CA3AF;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700; }

.ipban-pagination { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:14px 16px; border-top:1px solid #F3F4F6; }
.ipban-page-btn { padding:6px 14px; border:1.5px solid #E5E7EB; border-radius:7px; background:#fff; font-size:12px; font-weight:600; color:#374151; text-decoration:none; transition:.15s; }
.ipban-page-btn:hover { border-color:#4F46E5; color:#4F46E5; }
.ipban-page-btn.active { background:#4F46E5; color:#fff; border-color:#4F46E5; pointer-events:none; }
.ipban-page-btn.disabled { opacity:.45; pointer-events:none; }

.alert-success { background:#D1FAE5; border:1px solid #6EE7B7; color:#065F46; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; }
.alert-error   { background:#FEE2E2; border:1px solid #FCA5A5; color:#991B1B; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; }
</style>

<div class="ipban-header">
    <div>
        <h1 class="page-title" style="margin:0">🚫 Login IP Bans</h1>
        <p style="color:#6B7280;font-size:13px;margin:4px 0 0">Block specific IP addresses from logging in as affiliates.</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <span style="background:#FEE2E2;color:#DC2626;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:700">
            <?= number_format($activeBans) ?> Active Ban<?= $activeBans !== 1 ? 's' : '' ?>
        </span>
        <?php if ($totalRows > $activeBans): ?>
        <span style="background:#F3F4F6;color:#6B7280;border-radius:20px;padding:4px 14px;font-size:12px;font-weight:600">
            <?= number_format($totalRows - $activeBans) ?> Disabled
        </span>
        <?php endif; ?>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert-success">✅ <?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert-error">⚠️ <?= htmlspecialchars($error, ENT_QUOTES) ?></div>
<?php endif; ?>

<!-- ── Add Ban Form ──────────────────────────────────────────────────── -->
<div class="ipban-add-card">
    <h3>🔒 Ban an IP Address</h3>
    <form method="POST" action="/admin/ip-bans">
        <?= Helpers::csrf() ?>
        <input type="hidden" name="action" value="add">
        <div class="ipban-form-row">
            <div class="form-group">
                <label for="ip_address">IP Address or CIDR Range</label>
                <input type="text" id="ip_address" name="ip_address" placeholder="e.g. 192.168.1.100 or 10.0.0.0/8"
                       value="<?= htmlspecialchars($_POST['ip_address'] ?? '', ENT_QUOTES) ?>" autocomplete="off" spellcheck="false">
            </div>
            <div class="form-group">
                <label for="reason">Reason <span style="color:#9CA3AF;font-weight:400">(optional)</span></label>
                <input type="text" id="reason" name="reason" placeholder="e.g. Suspicious login attempts"
                       value="<?= htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES) ?>">
            </div>
            <button type="submit" class="btn-add-ban">🚫 Ban IP</button>
        </div>
        <p style="margin:10px 0 0;font-size:11px;color:#9CA3AF">
            Accepts IPv4 (e.g. <code>1.2.3.4</code>), IPv6 (e.g. <code>2001:db8::1</code>), or CIDR notation (e.g. <code>192.168.1.0/24</code>). Banned IPs are blocked at the affiliate login page and the tracker login page.
        </p>
    </form>
</div>

<!-- ── Banned IPs Table ──────────────────────────────────────────────── -->
<div class="card" style="overflow:hidden;padding:0">
    <!-- Filter -->
    <div style="padding:16px 20px;border-bottom:1px solid #F3F4F6;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <span style="font-size:14px;font-weight:700;color:#111827">Banned IP List</span>
        <form method="GET" action="/admin/ip-bans" class="ipban-filter-bar" style="margin:0">
            <input type="text" name="search" placeholder="Search IP…"
                   value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">
            <button type="submit" class="btn btn-secondary" style="padding:8px 16px;font-size:13px">Search</button>
            <?php if ($search): ?>
                <a href="/admin/ip-bans" class="btn btn-secondary" style="padding:8px 16px;font-size:13px">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="ipban-table-wrap">
        <table class="ipban-tbl">
            <thead>
                <tr>
                    <th>#</th>
                    <th>IP / CIDR</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Banned By</th>
                    <th>Banned At</th>
                    <th style="text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($bans)): ?>
                <tr>
                    <td colspan="7">
                        <div class="ipban-empty">
                            <div class="ipban-empty-icon">✅</div>
                            <div style="font-weight:600;color:#374151">No banned IPs<?= $search ? ' matching your search' : '' ?></div>
                            <div style="font-size:12px;margin-top:4px">All affiliate logins are currently unrestricted by IP.</div>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($bans as $i => $ban):
                    $isActive = (bool)($ban['is_active'] ?? 1);
                ?>
                    <tr class="<?= $isActive ? '' : 'ban-disabled' ?>">
                        <td style="color:#9CA3AF;font-size:12px"><?= ($offset + $i + 1) ?></td>
                        <td>
                            <span class="ip-code <?= $isActive ? '' : 'disabled' ?>">
                                <?= htmlspecialchars($ban['ip_address'], ENT_QUOTES) ?>
                            </span>
                        </td>
                        <td style="color:#6B7280;max-width:240px">
                            <?= $ban['reason'] ? htmlspecialchars($ban['reason'], ENT_QUOTES) : '<span style="color:#D1D5DB">—</span>' ?>
                        </td>
                        <td>
                            <?php if ($isActive): ?>
                                <span class="badge-active">● Active</span>
                            <?php else: ?>
                                <span class="badge-inactive">○ Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#374151">
                            <?php
                            $byName = trim(($ban['first_name'] ?? '') . ' ' . ($ban['last_name'] ?? ''));
                            echo $byName ? htmlspecialchars($byName, ENT_QUOTES) : '<span style="color:#D1D5DB">—</span>';
                            ?>
                        </td>
                        <td style="color:#6B7280;white-space:nowrap;font-size:12px">
                            <?= date('M j, Y g:i A', strtotime($ban['created_at'])) ?>
                        </td>
                        <td style="text-align:right;white-space:nowrap">
                            <div style="display:inline-flex;gap:6px;align-items:center">
                                <!-- Toggle Enable / Disable -->
                                <form method="POST" action="/admin/ip-bans" style="display:inline">
                                    <?= Helpers::csrf() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$ban['id'] ?>">
                                    <?php if ($isActive): ?>
                                        <button type="submit" class="btn-disable"
                                                onclick="return confirm('Disable ban on <?= htmlspecialchars($ban['ip_address'], ENT_QUOTES) ?>? The IP will be able to log in again.')">
                                            ⏸ Disable
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-enable"
                                                onclick="return confirm('Re-enable ban on <?= htmlspecialchars($ban['ip_address'], ENT_QUOTES) ?>?')">
                                            ▶ Enable
                                        </button>
                                    <?php endif; ?>
                                </form>
                                <!-- Remove ban -->
                                <form method="POST" action="/admin/ip-bans" style="display:inline"
                                      onsubmit="return confirm('Permanently remove ban on <?= htmlspecialchars($ban['ip_address'], ENT_QUOTES) ?>?')">
                                    <?= Helpers::csrf() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$ban['id'] ?>">
                                    <button type="submit" class="btn-unban">🗑 Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="ipban-pagination">
        <span style="font-size:12px;color:#6B7280">
            Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?> of <?= number_format($totalRows) ?>
        </span>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>"
               class="ipban-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">← Prev</a>
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"
                   class="ipban-page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>"
               class="ipban-page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">Next →</a>
        </div>
    </div>
    <?php endif; ?>
</div>
