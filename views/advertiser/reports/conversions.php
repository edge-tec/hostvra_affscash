<?php require BASE_PATH . '/views/layouts/advertiser.php'; ?>

<style>
/* ─── Conversion report styles (scoped) ─────────────────────────────────── */
.cr-head{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:16px;}
.cr-head h1{font-size:22px;font-weight:800;margin:0;}
.cr-head p{font-size:13px;color:var(--text-muted);margin:4px 0 0;}
.cr-actions{display:flex;gap:8px;flex-wrap:wrap;}

/* KPI strip */
.cr-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px;}
.cr-stat{background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:14px 16px;position:relative;overflow:hidden;}
.cr-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--cr-tint,#4F46E5);}
.cr-stat .lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);}
.cr-stat .val{font-size:20px;font-weight:800;color:var(--text);margin-top:4px;font-variant-numeric:tabular-nums;}
.cr-stat.total   { --cr-tint:#4F46E5; }
.cr-stat.payout  { --cr-tint:#F59E0B; }
.cr-stat.revenue { --cr-tint:#8B5CF6; }
.cr-stat.approved{ --cr-tint:#10B981; }
.cr-stat.pending { --cr-tint:#64748B; }

/* Filter card */
.cr-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;align-items:end;}
.cr-filters .form-group{margin-bottom:0;}

/* Table */
.cr-tbl-card{background:var(--card-bg);border:1px solid var(--border);border-radius:12px;overflow:hidden;}
.cr-tbl-head{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border);font-size:13px;}
.cr-tbl-wrap{overflow-x:auto;overflow-y:visible;-webkit-overflow-scrolling:touch;}
.cr-tbl{width:100%;border-collapse:separate;border-spacing:0;font-size:12.5px;min-width:2400px;}
.cr-tbl thead th{
    position:sticky;top:0;z-index:3;
    background:var(--bg);color:var(--text-muted);
    padding:10px 12px;
    font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
    white-space:nowrap;border-bottom:2px solid var(--border);text-align:left;
}
.cr-tbl tbody td{
    padding:10px 12px;border-bottom:1px solid var(--border);
    vertical-align:middle;white-space:nowrap;color:var(--text);
}
.cr-tbl tbody tr:hover td{background:var(--bg);}
.cr-tbl tbody td.num{text-align:right;font-variant-numeric:tabular-nums;}
.cr-tbl tbody td.money{text-align:right;font-variant-numeric:tabular-nums;font-weight:600;}
.cr-tbl tbody td.muted{color:var(--text-muted);}
.cr-tbl tbody td .pill{display:inline-block;padding:3px 8px;border-radius:999px;font-size:10.5px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;}
.pill.s-approved   {background:rgba(16,185,129,.13);color:#059669;}
.pill.s-pending    {background:rgba(245,158,11,.14);color:#B45309;}
.pill.s-rejected   {background:rgba(239,68,68,.13);color:#DC2626;}
.pill.s-chargebacked{background:rgba(139,92,246,.13);color:#7C3AED;}
.pill.pb-sent      {background:rgba(16,185,129,.13);color:#059669;}
.pill.pb-pending   {background:rgba(100,116,139,.13);color:#475569;}
.cr-truncate{display:inline-block;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle;}
.cr-id{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11.5px;color:var(--text);}
.cr-id-mini{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:11px;color:var(--text-muted);}
.cr-copy{cursor:pointer;color:var(--text-muted);transition:color .15s;}
.cr-copy:hover{color:var(--primary);}
.cr-copy.copied{color:#10B981;}

/* Sticky first column (Offer) — keeps the user oriented when scrolling
   horizontally. We sticky the visual column, not the row, so vertical
   scrolling is unaffected. */
.cr-tbl thead th.cr-stick,
.cr-tbl tbody td.cr-stick{
    position:sticky;left:0;z-index:2;background:var(--card-bg);
    box-shadow:1px 0 0 var(--border);
}
.cr-tbl thead th.cr-stick{z-index:4;background:var(--bg);}
.cr-tbl tbody tr:hover td.cr-stick{background:var(--bg);}

/* Action column right edge */
.cr-tbl tbody td.cr-act{white-space:nowrap;}
.cr-tbl tbody td.cr-act .btn-mini{
    display:inline-flex;align-items:center;gap:4px;
    padding:5px 9px;border-radius:6px;font-size:11px;font-weight:600;
    background:var(--bg);border:1px solid var(--border);color:var(--text);
    cursor:pointer;transition:background .15s,border-color .15s,color .15s;
}
.cr-tbl tbody td.cr-act .btn-mini:hover{background:var(--primary-light,#EEF2FF);border-color:var(--primary);color:var(--primary);}
.cr-tbl tbody td.cr-act .btn-mini svg{width:13px;height:13px;}

/* Pagination */
.cr-page{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid var(--border);gap:10px;flex-wrap:wrap;}
.cr-page .pages{display:flex;gap:4px;}
.cr-page .pg-btn{padding:5px 10px;border-radius:6px;border:1px solid var(--border);background:var(--card-bg);color:var(--text);font-size:12px;font-weight:600;text-decoration:none;}
.cr-page .pg-btn.active{background:var(--primary);color:#fff;border-color:var(--primary);}
.cr-page .pg-btn.disabled{opacity:.45;pointer-events:none;}

/* Empty state */
.cr-empty{padding:48px 20px;text-align:center;color:var(--text-muted);}
.cr-empty .em-ic{font-size:34px;opacity:.55;margin-bottom:6px;}

/* Modal */
.cr-modal{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;z-index:9999;padding:18px;}
.cr-modal.open{display:flex;}
.cr-modal-box{background:var(--card-bg);border-radius:12px;max-width:580px;width:100%;max-height:90vh;overflow:auto;border:1px solid var(--border);}
.cr-modal-head{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--border);}
.cr-modal-head h3{margin:0;font-size:15px;font-weight:700;}
.cr-modal-close{background:transparent;border:none;font-size:22px;line-height:1;color:var(--text-muted);cursor:pointer;}
.cr-modal-body{padding:16px 18px;font-size:13px;}
.cr-modal-body dl{display:grid;grid-template-columns:140px 1fr;gap:10px 18px;margin:0;}
.cr-modal-body dt{color:var(--text-muted);font-weight:600;}
.cr-modal-body dd{margin:0;color:var(--text);word-break:break-all;}

/* Responsive ladder */
@media(max-width:1100px){ .cr-stats{grid-template-columns:repeat(3,1fr);} }
@media(max-width:768px){
    .cr-stats{grid-template-columns:repeat(2,1fr);gap:8px;}
    .cr-stat .val{font-size:18px;}
    .cr-head h1{font-size:19px;}
    .cr-tbl thead th, .cr-tbl tbody td{padding:8px 10px;}
}
@media(max-width:480px){
    .cr-stats{grid-template-columns:1fr 1fr;}
    .cr-modal-body dl{grid-template-columns:1fr;gap:6px;}
    .cr-modal-body dt{margin-top:6px;}
}
</style>

<div class="cr-head">
    <div>
        <h1>Conversion Report</h1>
        <p>Every conversion against your offers — with goal, geo, device, postback status and per-segment CR/CTR.</p>
    </div>
    <div class="cr-actions">
        <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>" class="btn btn-secondary">Export CSV</a>
    </div>
</div>

<!-- ── KPI strip ─────────────────────────────────────────────────────── -->
<div class="cr-stats">
    <div class="cr-stat total">
        <div class="lbl">Conversions</div>
        <div class="val"><?= number_format($total) ?></div>
    </div>
    <div class="cr-stat approved">
        <div class="lbl">Approved</div>
        <div class="val"><?= number_format((int)($sumRow['approved'] ?? 0)) ?></div>
    </div>
    <div class="cr-stat pending">
        <div class="lbl">Pending</div>
        <div class="val"><?= number_format((int)($sumRow['pending'] ?? 0)) ?></div>
    </div>
    <div class="cr-stat revenue">
        <div class="lbl">Revenue</div>
        <div class="val">$<?= number_format((float)($sumRow['revenue'] ?? 0), 2) ?></div>
    </div>
    <div class="cr-stat payout">
        <div class="lbl">Payout</div>
        <div class="val">$<?= number_format((float)($sumRow['payout'] ?? 0), 2) ?></div>
    </div>
</div>

<!-- ── Filters ──────────────────────────────────────────────────────── -->
<div class="card mb-3 filter-card" id="adv-cr-filter-card">
    <button type="button" class="filter-toggle-btn" onclick="this.closest('.filter-card').classList.toggle('filter-open')">
        <span>🔍 Filters</span>
        <span class="filter-toggle-icon">▼</span>
    </button>
    <div class="card-body">
        <form method="GET" class="cr-filters">
            <div class="form-group"><label>From</label><input type="date" name="from" class="form-control" value="<?= Helpers::e($from) ?>"></div>
            <div class="form-group"><label>To</label><input type="date" name="to" class="form-control" value="<?= Helpers::e($to) ?>"></div>
            <div class="form-group"><label>Offer</label>
                <select name="offer_id" class="form-control">
                    <option value="">All offers</option>
                    <?php foreach ($myOffers as $o): ?>
                    <option value="<?= (int)$o['id'] ?>" <?= $offerId === (int)$o['id'] ? 'selected' : '' ?>><?= Helpers::e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Affiliate</label>
                <select name="affiliate_id" class="form-control">
                    <option value="">All affiliates</option>
                    <?php foreach ($myAffiliates as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= $affId === (int)$a['id'] ? 'selected' : '' ?>>
                        <?= Helpers::e($a['name']) ?> (<?= Helpers::e($a['affiliate_code']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Country</label>
                <select name="country" class="form-control">
                    <option value="">All</option>
                    <?php foreach ($countries as $c): ?>
                    <option value="<?= Helpers::e($c['country']) ?>" <?= $country === $c['country'] ? 'selected' : '' ?>><?= Helpers::e($c['country']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <?php foreach (['approved','pending','chargebacked'] as $s): ?>
                    <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Goal</label>
                <select name="goal" class="form-control">
                    <option value="">Any</option>
                    <?php foreach ($goalOptions as $g): ?>
                    <option value="<?= Helpers::e($g['goal_name']) ?>" <?= $goal===$g['goal_name']?'selected':'' ?>><?= Helpers::e($g['goal_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Txn ID</label><input type="text" name="txn_id" class="form-control" value="<?= Helpers::e($txnIdQ) ?>" placeholder="prefix"></div>
            <div class="form-group"><label>Click ID</label><input type="text" name="click_id" class="form-control" value="<?= Helpers::e($clickIdQ) ?>" placeholder="prefix"></div>
            <div class="form-group"><label>Conversion ID</label><input type="text" name="conversion_id" class="form-control" value="<?= Helpers::e($convIdQ) ?>" placeholder="prefix"></div>
            <div class="form-group"><button class="btn btn-primary" style="width:100%">Apply</button></div>
        </form>
    </div>
</div>

<!-- ── Table ────────────────────────────────────────────────────────── -->
<div class="cr-tbl-card">
    <div class="cr-tbl-head">
        <span style="font-weight:700"><?= number_format($total) ?> conversion<?= $total===1?'':'s' ?></span>
        <small class="text-muted">Page <?= $page ?> / <?= $pages ?></small>
    </div>

    <?php if (empty($rows)): ?>
        <div class="cr-empty">
            <div class="em-ic">&#128202;</div>
            <div>No conversions match the current filters.</div>
            <div style="font-size:12px;margin-top:6px">Adjust the date range or remove filters to widen the search.</div>
        </div>
    <?php else: ?>
    <div class="cr-tbl-wrap">
        <table class="cr-tbl">
            <thead>
                <tr>
                    <th class="cr-stick">Offer</th>
                    <th>Affiliate</th>
                    <th>Click ID</th>
                    <th>Conversion ID</th>
                    <th>Aff Click ID</th>
                    <th>Aff Sub 2</th>
                    <th>Status</th>
                    <th class="num">Payout</th>
                    <th class="num">Revenue</th>
                    <th>Goal</th>
                    <th>Txn ID</th>
                    <th>Country</th>
                    <th>OS</th>
                    <th>Browser</th>
                    <th>Conv IP</th>
                    <th>User Agent</th>
                    <th>Device Brand</th>
                    <th>Device Model</th>
                    <th>Category</th>
                    <th>Preland</th>
                    <th>LP Name</th>
                    <th>Offer Page</th>
                    <th>Flow ID</th>
                    <th class="num">CR (Visit)</th>
                    <th class="num">CR (Click)</th>
                    <th class="num">CR (Unique)</th>
                    <th class="num">CTR</th>
                    <th>Postback</th>
                    <th>Converted At</th>
                    <th class="cr-act">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $agg     = $aggMap[$r['offer_id'].':'.$r['affiliate_id']] ?? [];
                    $visits  = (int)($agg['visits']  ?? 0);
                    $clicksN = (int)($agg['clicks']  ?? 0);
                    $uniq    = (int)($agg['unique_clicks'] ?? 0);
                    $convN   = (int)($agg['conversions']   ?? 0);
                    $crVisit = $visits  > 0 ? round($convN / $visits  * 100, 2) : 0;
                    $crClick = $clicksN > 0 ? round($convN / $clicksN * 100, 2) : 0;
                    $crUniq  = $uniq    > 0 ? round($convN / $uniq    * 100, 2) : 0;
                    $ctr     = $visits  > 0 ? round($clicksN / $visits * 100, 2) : 0;
                    $lpName  = $resolveLpName($r['landing_pages_json'] ?? null, $r['landing_page_idx'] ?? null);
                    $offerPage = (string)($r['offer_page'] ?? '');
                ?>
                <tr>
                    <td class="cr-stick"><strong><?= Helpers::e($r['offer_name']) ?></strong></td>
                    <td>
                        <?= Helpers::e($r['aff_name'] ?? '—') ?>
                        <?php if (!empty($r['affiliate_code'])): ?>
                            <span class="cr-id-mini">(<?= Helpers::e($r['affiliate_code']) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="cr-id"><?= Helpers::e(substr($r['click_id'],0,8)) ?>…</span>
                        <span class="cr-copy" data-copy="<?= Helpers::e($r['click_id']) ?>" title="Copy full ID">&#128203;</span></td>
                    <td><span class="cr-id"><?= Helpers::e(substr($r['conversion_id'],0,8)) ?>…</span>
                        <span class="cr-copy" data-copy="<?= Helpers::e($r['conversion_id']) ?>" title="Copy full ID">&#128203;</span></td>
                    <td class="muted"><?= Helpers::e($r['aff_click_id'] ?? '') ?: '—' ?></td>
                    <td class="muted"><?= Helpers::e($r['aff_sub2']     ?? '') ?: '—' ?></td>
                    <td><span class="pill s-<?= Helpers::e($r['conv_status']) ?>"><?= Helpers::e($r['conv_status']) ?></span></td>
                    <td class="money">$<?= number_format((float)$r['payout'], 2) ?></td>
                    <td class="money">$<?= number_format((float)$r['revenue'], 2) ?></td>
                    <td class="muted"><?= Helpers::e($r['goal_name'] ?? '') ?: '—' ?></td>
                    <td class="muted"><?= Helpers::e($r['txn_id'] ?? '') ?: '—' ?></td>
                    <td><?= Helpers::e($r['click_country'] ?? '') ?: '—' ?></td>
                    <td><?= Helpers::e($r['os'] ?? '') ?: '—' ?></td>
                    <td><?= Helpers::e($r['browser'] ?? '') ?: '—' ?></td>
                    <td class="muted"><?= Helpers::e($r['conv_ip'] ?? '') ?: '—' ?></td>
                    <td title="<?= Helpers::e($r['user_agent'] ?? '') ?>"><span class="cr-truncate"><?= Helpers::e($r['user_agent'] ?? '') ?: '—' ?></span></td>
                    <td><?= Helpers::e($r['device_brand'] ?? '') ?: '—' ?></td>
                    <td><?= Helpers::e($r['device_model'] ?? '') ?: '—' ?></td>
                    <td><?= Helpers::e($r['offer_category'] ?? '') ?: '—' ?></td>
                    <td class="muted"><?= $r['landing_page_idx'] === null ? '—' : ('LP '.((int)$r['landing_page_idx']+1)) ?></td>
                    <td><?= Helpers::e($lpName) ?: '—' ?></td>
                    <td title="<?= Helpers::e($offerPage) ?>"><span class="cr-truncate"><?= Helpers::e($offerPage) ?: '—' ?></span></td>
                    <td class="muted"><?= Helpers::e($r['flow_id'] ?? '') ?: '—' ?></td>
                    <td class="num"><?= number_format($crVisit, 2) ?>%</td>
                    <td class="num"><?= number_format($crClick, 2) ?>%</td>
                    <td class="num"><?= number_format($crUniq,  2) ?>%</td>
                    <td class="num"><?= number_format($ctr,     2) ?>%</td>
                    <td>
                        <?php if ((int)$r['postback_sent'] === 1): ?>
                            <span class="pill pb-sent">Sent</span>
                        <?php else: ?>
                            <span class="pill pb-pending">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="muted"><?= Helpers::e(date('Y-m-d H:i', strtotime($r['converted_at']))) ?></td>
                    <td class="cr-act">
                        <button class="btn-mini" data-detail='<?= htmlspecialchars(json_encode([
                            'Conversion ID'  => $r['conversion_id'],
                            'Click ID'       => $r['click_id'],
                            'Offer'          => $r['offer_name'],
                            'Affiliate'      => ($r['aff_name'] ?? '—') . ($r['affiliate_code']?' ('.$r['affiliate_code'].')':''),
                            'Status'         => $r['conv_status'],
                            'Payout'         => '$' . number_format((float)$r['payout'], 2),
                            'Revenue'        => '$' . number_format((float)$r['revenue'], 2),
                            'Goal'           => $r['goal_name'] ?? '',
                            'Txn ID'         => $r['txn_id']    ?? '',
                            'Country'        => $r['click_country'] ?? '',
                            'OS / Browser'   => trim(($r['os'] ?? '') . ' / ' . ($r['browser'] ?? ''), ' /'),
                            'Device'         => trim(($r['device_brand'] ?? '') . ' ' . ($r['device_model'] ?? ''), ' '),
                            'Conv IP'        => $r['conv_ip'] ?? '',
                            'User Agent'     => $r['user_agent'] ?? '',
                            'Preland / LP'   => ($r['landing_page_idx']===null ? '' : ('LP '.((int)$r['landing_page_idx']+1))) . ($lpName ? ' · '.$lpName : ''),
                            'Offer Page'     => $offerPage,
                            'Flow ID'        => $r['flow_id'] ?? '',
                            'Postback'       => $r['postback_sent'] ? 'Sent at ' . ($r['postback_sent_at'] ?? '') : 'Pending',
                            'Converted At'   => $r['converted_at'] ?? '',
                        ], JSON_UNESCAPED_SLASHES), ENT_QUOTES) ?>'>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            View
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="cr-page">
        <div class="text-muted" style="font-size:12px">Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $total)) ?> of <?= number_format($total) ?></div>
        <div class="pages">
            <?php
            $qsBase = $_GET; unset($qsBase['page']);
            $mkUrl = function($p) use ($qsBase){ return '?'.http_build_query(array_merge($qsBase, ['page'=>$p])); };
            $start = max(1, $page-2);
            $end   = min($pages, $page+2);
            ?>
            <a class="pg-btn <?= $page<=1?'disabled':'' ?>" href="<?= $mkUrl(max(1,$page-1)) ?>">‹ Prev</a>
            <?php if ($start > 1): ?>
                <a class="pg-btn" href="<?= $mkUrl(1) ?>">1</a>
                <?php if ($start > 2): ?><span class="pg-btn disabled">…</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($i=$start; $i<=$end; $i++): ?>
                <a class="pg-btn <?= $i===$page?'active':'' ?>" href="<?= $mkUrl($i) ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($end < $pages): ?>
                <?php if ($end < $pages-1): ?><span class="pg-btn disabled">…</span><?php endif; ?>
                <a class="pg-btn" href="<?= $mkUrl($pages) ?>"><?= $pages ?></a>
            <?php endif; ?>
            <a class="pg-btn <?= $page>=$pages?'disabled':'' ?>" href="<?= $mkUrl(min($pages,$page+1)) ?>">Next ›</a>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- ── Detail modal ─────────────────────────────────────────────────── -->
<div class="cr-modal" id="cr-modal">
    <div class="cr-modal-box">
        <div class="cr-modal-head">
            <h3>Conversion Detail</h3>
            <button class="cr-modal-close" type="button" data-close>&times;</button>
        </div>
        <div class="cr-modal-body">
            <dl id="cr-detail"></dl>
        </div>
    </div>
</div>

<script>
(function(){
    // Copy-to-clipboard with success flash
    document.querySelectorAll('.cr-copy').forEach(el => {
        el.addEventListener('click', async () => {
            const val = el.dataset.copy || '';
            try {
                await navigator.clipboard.writeText(val);
                el.classList.add('copied');
                const orig = el.textContent;
                el.textContent = '✓';
                setTimeout(() => { el.classList.remove('copied'); el.textContent = orig; }, 1200);
            } catch(_) {}
        });
    });

    // Detail modal
    const modal  = document.getElementById('cr-modal');
    const target = document.getElementById('cr-detail');
    function openModal(rec){
        target.innerHTML = '';
        Object.entries(rec).forEach(([k,v]) => {
            if (v === '' || v === null || v === undefined) return;
            const dt = document.createElement('dt'); dt.textContent = k;
            const dd = document.createElement('dd'); dd.textContent = String(v);
            target.appendChild(dt); target.appendChild(dd);
        });
        modal.classList.add('open');
    }
    function closeModal(){ modal.classList.remove('open'); }
    document.querySelectorAll('[data-detail]').forEach(b => {
        b.addEventListener('click', () => {
            try { openModal(JSON.parse(b.dataset.detail)); } catch(_) {}
        });
    });
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    modal.querySelector('[data-close]').addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
})();
</script>
