<?php require BASE_PATH . '/views/layouts/affiliate_manager.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128229; Request Payout Invoice</h1>
        <p>Submit a payout invoice request to the admin for your commission earnings.</p>
    </div>
    <a href="/affiliate_manager/invoices" class="btn btn-secondary">← Back to Invoices</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error" style="margin-bottom:18px">
    <?php foreach ($errors as $e): ?><div>• <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Balance overview -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
    <div class="stat-card">
        <div class="stat-icon" style="background:#DCFCE7;color:#059669">&#128181;</div>
        <div class="stat-label">Available Balance</div>
        <div class="stat-value" style="color:#059669">$<?= number_format((float)$mgrBalance['balance'], 2) ?></div>
        <div class="stat-sub">Max you can request</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#FEF9C3;color:#CA8A04">&#9203;</div>
        <div class="stat-label">Pending Earnings</div>
        <div class="stat-value" style="color:#CA8A04">$<?= number_format((float)$mgrBalance['pending'], 2) ?></div>
        <div class="stat-sub">Being reviewed</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#F0FDF4;color:#15803D">&#128176;</div>
        <div class="stat-label">Total Paid Out</div>
        <div class="stat-value" style="color:#15803D">$<?= number_format((float)$mgrBalance['paid'], 2) ?></div>
        <div class="stat-sub">All-time settled</div>
    </div>
</div>

<!-- Request form -->
<?php
$hasPending = !empty(array_filter($myRequests, fn($r) => $r['status'] === 'pending'));
?>
<?php if ($hasPending): ?>
<div style="background:linear-gradient(135deg,#FFFBEB,#FEF3C7);border:1px solid #FCD34D;border-radius:10px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:flex-start;gap:14px">
    <div style="font-size:24px">&#9203;</div>
    <div>
        <div style="font-weight:700;color:#92400E;font-size:14px">You have a pending request</div>
        <div style="font-size:13px;color:#78350F;margin-top:4px">
            Your most recent invoice request is awaiting admin review. You can submit a new one once it is approved or rejected.
        </div>
    </div>
</div>
<?php else: ?>
<div class="card mb-3">
    <div class="card-header" style="background:linear-gradient(135deg,#5B21B6,#7C3AED);border-radius:8px 8px 0 0">
        <span class="card-title" style="color:#fff">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-3px;margin-right:6px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            New Invoice Request
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="/affiliate_manager/invoices?action=request_invoice">
            <?= Helpers::csrf() ?>

            <!-- ── Affiliate Account Selector ── -->
            <div class="form-group" style="margin-bottom:18px">
                <label>Affiliate Account <span style="color:#94A3B8;font-weight:400">(optional — select if this request relates to a specific affiliate)</span></label>
                <?php if (empty($managedAffiliatesForRequest)): ?>
                <div style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;font-size:13px;color:#94A3B8">
                    No active managed affiliates found.
                </div>
                <input type="hidden" name="affiliate_id" value="">
                <?php else: ?>
                <select name="affiliate_id" id="affiliateSelect" class="form-control" style="max-width:420px">
                    <option value="" data-balance="">— None (general commission request) —</option>
                    <?php foreach ($managedAffiliatesForRequest as $aff): ?>
                    <option value="<?= (int)$aff['id'] ?>" data-balance="<?= htmlspecialchars(json_encode(['id' => (int)$aff['id']])) ?>">
                        <?= Helpers::e($aff['full_name']) ?> (<?= Helpers::e($aff['affiliate_code']) ?>)<?= $aff['email'] ? ' — ' . Helpers::e($aff['email']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">If your payout request relates to a specific affiliate, select them. When approved, the invoice will be generated for that affiliate and deducted from their balance.</div>
                <?php endif; ?>
            </div>

            <!-- Affiliate balance notice (shown when affiliate is selected) -->
            <div id="affBalanceNotice" style="display:none;padding:12px 16px;background:#F5F3FF;border:1px solid #DDD6FE;border-radius:8px;margin-bottom:16px;font-size:13px;color:#5B21B6">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <strong>Affiliate balance:</strong> <span id="affBalanceAmt">—</span>
                <span style="color:#7C3AED;margin-left:8px;font-size:12px">— the amount you request will be deducted from this affiliate's balance when the admin approves.</span>
            </div>

            <div class="form-row cols-2" style="margin-bottom:16px">
                <div class="form-group" style="margin-bottom:0">
                    <label>Request Amount ($) <span style="color:#EF4444">*</span></label>
                    <input type="number" name="amount" id="requestAmount" class="form-control" step="0.01" min="0.01"
                           max="<?= number_format((float)$mgrBalance['balance'], 2, '.', '') ?>"
                           value="<?= number_format((float)$mgrBalance['balance'], 2, '.', '') ?>"
                           placeholder="0.00" required>
                    <div class="form-hint" id="amountHint">Your available balance: <strong>$<?= number_format((float)$mgrBalance['balance'], 2) ?></strong></div>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <!-- Spacer for layout -->
                </div>
            </div>

            <div class="form-row cols-2" style="margin-bottom:16px">
                <div class="form-group" style="margin-bottom:0">
                    <label>Period Start <span style="color:#EF4444">*</span></label>
                    <input type="date" name="period_start" class="form-control"
                           value="<?= date('Y-m-01') ?>" required>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label>Period End <span style="color:#EF4444">*</span></label>
                    <input type="date" name="period_end" class="form-control"
                           value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:20px">
                <label>Notes <span style="color:#94A3B8;font-weight:400">(optional)</span></label>
                <textarea name="notes" class="form-control" rows="3"
                          placeholder="Payment method preference, bank details, or any other instructions..."></textarea>
            </div>

            <div style="padding:12px 16px;background:rgba(124,58,237,0.06);border-radius:8px;font-size:13px;color:#5B21B6;margin-bottom:20px;border:1px solid #DDD6FE">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                The admin will review your request. <strong>If an affiliate is selected</strong>, the invoice will be generated for that affiliate and the amount deducted from their balance. For general (no affiliate) requests, your commission balance will be used.
            </div>

            <button type="submit" class="btn btn-primary" style="min-width:220px;height:42px;background:#7C3AED;border-color:#7C3AED">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:6px"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Submit Invoice Request
            </button>
        </form>
        <script>
        (function(){
            var mgrBalance = <?= json_encode((float)$mgrBalance['balance']) ?>;
            var sel        = document.getElementById('affiliateSelect');
            var notice     = document.getElementById('affBalanceNotice');
            var amtEl      = document.getElementById('requestAmount');
            var hintEl     = document.getElementById('amountHint');
            var affAmtEl   = document.getElementById('affBalanceAmt');
            if (!sel) return;

            sel.addEventListener('change', function(){
                var affId = parseInt(sel.value, 10);
                if (!affId) {
                    notice.style.display = 'none';
                    amtEl.max   = mgrBalance.toFixed(2);
                    amtEl.value = mgrBalance.toFixed(2);
                    hintEl.innerHTML = 'Your available balance: <strong>$' + mgrBalance.toFixed(2) + '</strong>';
                    return;
                }
                // Fetch affiliate balance via existing admin AJAX (fallback: hide notice)
                fetch('/admin/invoices?action=get_affiliate_info&affiliate_id=' + affId)
                    .then(function(r){ return r.json(); })
                    .then(function(d){
                        if (d && typeof d.balance !== 'undefined') {
                            var b = parseFloat(d.balance).toFixed(2);
                            affAmtEl.textContent   = '$' + b;
                            notice.style.display   = 'block';
                            amtEl.max   = b;
                            amtEl.value = b;
                            hintEl.innerHTML = 'Affiliate available balance: <strong>$' + b + '</strong>';
                        }
                    })
                    .catch(function(){
                        notice.style.display = 'none';
                    });
            });
        })();
        </script>
    </div>
</div>
<?php endif; ?>

<!-- My past requests -->
<div class="card">
    <div class="card-header">
        <span class="card-title">My Invoice Requests</span>
        <span class="text-muted text-sm"><?= count($myRequests) ?> total</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Submitted</th>
                    <th>Affiliate Account</th>
                    <th>Amount</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th>Admin Note</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($myRequests)): ?>
            <tr><td colspan="7" style="text-align:center;padding:36px;color:var(--text-muted)">
                No requests submitted yet.
            </td></tr>
            <?php else: ?>
            <?php foreach ($myRequests as $r): ?>
            <?php
                $stMap = [
                    'pending'  => ['badge-warning', '&#9203; Pending'],
                    'approved' => ['badge-success', '&#10003; Approved'],
                    'rejected' => ['badge-danger',  '&#10005; Rejected'],
                ];
                [$badgeCls, $badgeTxt] = $stMap[$r['status']] ?? ['badge-muted', ucfirst($r['status'])];
            ?>
            <tr>
                <td class="text-sm text-muted"><?= date('M j, Y H:i', strtotime($r['created_at'])) ?></td>
                <td>
                    <?php if ($r['affiliate_id'] && $r['aff_name']): ?>
                    <div class="fw-bold text-sm"><?= Helpers::e($r['aff_name']) ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($r['affiliate_code']) ?></div>
                    <?php else: ?>
                    <span class="text-muted text-sm">—</span>
                    <?php endif; ?>
                </td>
                <td class="fw-bold">$<?= number_format((float)$r['amount'], 2) ?></td>
                <td class="text-sm text-muted">
                    <?= date('M j, Y', strtotime($r['period_start'])) ?> –
                    <?= date('M j, Y', strtotime($r['period_end'])) ?>
                </td>
                <td><span class="badge <?= $badgeCls ?>"><?= $badgeTxt ?></span></td>
                <td style="font-size:12px;color:#64748B;max-width:220px">
                    <?= $r['admin_note'] ? Helpers::e($r['admin_note']) : '—' ?>
                </td>
                <td>
                    <?php if ($r['status'] === 'approved' && $r['invoice_id']): ?>
                    <?php if ($r['affiliate_id']): ?>
                    <a href="/affiliate_manager/invoices?action=view&id=<?= (int)$r['invoice_id'] ?>" class="btn btn-secondary btn-sm">View Invoice</a>
                    <?php else: ?>
                    <a href="/affiliate_manager/invoices?action=my_invoice_view&id=<?= (int)$r['invoice_id'] ?>" class="btn btn-secondary btn-sm">View Invoice</a>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted text-sm">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/affiliate_manager_footer.php'; ?>
