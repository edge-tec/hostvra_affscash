<?php
$pageTitle = 'Manage: ' . $manager['first_name'] . ' ' . $manager['last_name'];
require BASE_PATH . '/views/layouts/admin.php';
$managerPerms = json_decode($manager['permissions'] ?? '[]', true) ?: [];
$permLabels   = [
    'view_affiliates'    => 'View Affiliates',
    'approve_affiliates' => 'Approve Affiliates',
    'view_conversions'   => 'View Conversions',
    'view_reports'       => 'View Reports',
    'manage_offers'      => 'Manage Offers',
    'manage_payments'    => 'Manage Payments',
];
?>

<div class="page-header">
    <div>
        <h1><?= Helpers::e($manager['first_name'] . ' ' . $manager['last_name']) ?></h1>
        <p><?= Helpers::e($manager['email']) ?></p>
    </div>
    <div class="d-flex gap-2" style="flex-wrap:wrap">
        <a href="/admin/affiliate-managers?action=impersonate&user_id=<?= $manager['id'] ?>"
           class="btn btn-primary" onclick="return confirm('Login as this manager?')">&#128064; Login As</a>

        <?php if (!empty($manager['google2fa_enabled'])): ?>
        <form method="POST" action="/admin/users/2fa-reset" style="display:inline" onsubmit="return confirm('Reset Google Authenticator 2FA for this manager?\nThey will need to re-enable it from their account.');">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="user_id" value="<?= (int)$manager['id'] ?>">
            <input type="hidden" name="redirect_back" value="/admin/affiliate-managers/<?= (int)$manager['mgr_id'] ?>">
            <button class="btn btn-secondary" title="2FA enabled — click to reset" style="background:#FEF3C7;color:#92400E;border-color:#FDE68A">
                &#128274; 2FA <span style="font-weight:700">ON</span> &nbsp;<span style="opacity:.85">· Reset</span>
            </button>
        </form>
        <?php else: ?>
        <span class="btn" style="background:#F1F5F9;color:#475569;border-color:#E2E8F0;cursor:default" title="Manager has not enabled 2FA">&#128275; 2FA Off</span>
        <?php endif; ?>

        <a href="/admin/affiliate-managers" class="btn btn-secondary">← Back</a>
    </div>
</div>

<!-- ── Commission Balance ───────────────────────────────────────────────────── -->
<div class="card mb-3" style="border-left:4px solid #059669">
    <div class="card-body" style="padding:18px 20px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
            <div style="display:flex;gap:32px;flex-wrap:wrap;align-items:center">
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;margin-bottom:2px">Available Balance</div>
                    <div style="font-size:26px;font-weight:800;color:#059669">$<?= number_format((float)$mgrBalance['balance'], 2) ?></div>
                </div>
                <div style="width:1px;background:#E2E8F0;height:40px"></div>
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;margin-bottom:2px">Pending</div>
                    <div style="font-size:18px;font-weight:700;color:#CA8A04">$<?= number_format((float)$mgrBalance['pending'], 2) ?></div>
                </div>
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;margin-bottom:2px">Approved</div>
                    <div style="font-size:18px;font-weight:700;color:#4F46E5">$<?= number_format((float)$mgrBalance['approved'], 2) ?></div>
                </div>
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;margin-bottom:2px">Total Paid Out</div>
                    <div style="font-size:18px;font-weight:700;color:#15803D">$<?= number_format((float)$mgrBalance['paid'], 2) ?></div>
                </div>
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;margin-bottom:2px">All-Time Earned</div>
                    <div style="font-size:18px;font-weight:700;color:#1E293B">$<?= number_format((float)$mgrBalance['total_earned'], 2) ?></div>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <!-- Recalculate: processes all existing approved conversions not yet commission-recorded -->
                <button type="button" class="btn btn-secondary btn-sm" style="border-color:#059669;color:#059669"
                        onclick="document.getElementById('recalc-panel').style.display=document.getElementById('recalc-panel').style.display==='none'?'block':'none'">
                    &#9881; Recalculate Commissions
                </button>
                <?php if ((float)$mgrBalance['pending'] > 0): ?>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Approve all $<?= number_format((float)$mgrBalance['pending'], 2) ?> in pending commissions?')">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="submit_type" value="approve_commissions">
                    <button class="btn btn-secondary btn-sm" style="border-color:#4F46E5;color:#4F46E5">
                        &#10003; Approve Pending ($<?= number_format((float)$mgrBalance['pending'], 2) ?>)
                    </button>
                </form>
                <?php endif; ?>
                <?php if ((float)$mgrBalance['balance'] > 0): ?>
                <a href="/admin/affiliate-managers?action=generate_invoice&id=<?= (int)$manager['mgr_id'] ?>"
                   class="btn btn-primary btn-sm">
                    &#128196; Generate Invoice
                </a>
                <?php else: ?>
                <button class="btn btn-secondary btn-sm" disabled title="No balance available">&#128196; Generate Invoice</button>
                <?php endif; ?>
                <a href="/admin/affiliate-managers?action=commission_report&id=<?= (int)$manager['mgr_id'] ?>"
                   class="btn btn-secondary btn-sm">&#128200; Full Report</a>
            </div>
        </div>
        <div style="margin-top:10px;font-size:12px;color:#94A3B8;display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap">
            <span>
                <strong>Formula (admin-only):</strong>
                Net Profit = Advertiser Revenue &minus; Affiliate Payout &rarr;
                Commission = Net Profit &times; <?= number_format((float)$manager['commission_rate'], 2) ?>%
                &nbsp;|&nbsp; When offer has no revenue set: Commission = Payout &times; <?= number_format((float)$manager['commission_rate'], 2) ?>%
                &mdash; Never shown to manager.
            </span>
            <?php if ((float)$manager['commission_rate'] > 0 && (float)$mgrBalance['total_earned'] == 0): ?>
            <span style="background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;border-radius:6px;padding:4px 12px;font-size:11px;font-weight:700;cursor:pointer"
                  onclick="document.getElementById('recalc-panel').style.display='block';document.getElementById('recalc-panel').scrollIntoView({behavior:'smooth'})">
                &#9888; $0 balance &mdash; click here to run Recalculate Commissions now
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recalculate Commission Panel — auto-open when balance is $0 and rate is set -->
<div id="recalc-panel" style="display:<?= ((float)($manager['commission_rate'] ?? 0) > 0 && (float)$mgrBalance['total_earned'] == 0) ? 'block' : 'none' ?>;margin-bottom:16px">
<div class="card" style="border-left:4px solid #059669">
    <div class="card-header" style="background:#F0FDF4">
        <span class="card-title" style="color:#059669">&#9881; Recalculate Commissions</span>
        <span style="font-size:12px;color:#6B7280">Process approved conversions that were not yet commission-recorded</span>
    </div>
    <div class="card-body">
        <div style="background:#EFF6FF;border:1px solid #93C5FD;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px;color:#1E40AF">
            <strong>How commission is calculated:</strong><br>
            &bull; <strong>Net Profit mode</strong> (when offer has Advertiser Revenue &gt; Affiliate Payout):<br>
            &nbsp;&nbsp;Net Profit = Revenue &minus; Payout &rarr; Commission = Net Profit &times; <?= number_format((float)$manager['commission_rate'], 2) ?>%<br>
            &bull; <strong>Payout mode</strong> (when offer has no revenue configured — the default):<br>
            &nbsp;&nbsp;Commission = Affiliate Payout &times; <?= number_format((float)$manager['commission_rate'], 2) ?>%<br>
            Already-recorded conversions are skipped automatically. Safe to run multiple times.
        </div>
        <?php if (!empty($commissionDiag)): ?>
        <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:13px">
            <strong style="color:#334155">&#128270; Diagnostics</strong>
            <table style="margin-top:8px;border-collapse:collapse;width:100%;max-width:520px">
            <?php
            $diagItems = [
                ['Commission Rate',           number_format($commissionDiag['commission_rate'] ?? 0, 2) . '%',
                 ($commissionDiag['commission_rate'] ?? 0) > 0 ? 'ok' : 'warn',
                 ($commissionDiag['commission_rate'] ?? 0) <= 0 ? 'Set a commission rate > 0% above to enable commissions' : ''],
                ['Assigned Affiliates',        $commissionDiag['assigned_affiliates'] ?? 0,
                 ($commissionDiag['assigned_affiliates'] ?? 0) > 0 ? 'ok' : 'warn',
                 ($commissionDiag['assigned_affiliates'] ?? 0) == 0 ? 'No affiliates assigned — assign affiliates below to track commissions' : ''],
                ['Approved Conversions (total)',$commissionDiag['approved_conversions'] ?? 0,
                 ($commissionDiag['approved_conversions'] ?? 0) > 0 ? 'ok' : 'warn',
                 ($commissionDiag['approved_conversions'] ?? 0) == 0 ? (($commissionDiag['pending_conversions'] ?? 0) > 0 ? 'All conversions are Pending — admin must approve them first' : 'No approved conversions found for assigned affiliates') : ''],
                ['Approved with Payout > $0',  $commissionDiag['conv_with_payout'] ?? 0,
                 ($commissionDiag['conv_with_payout'] ?? 0) > 0 ? 'ok' : 'warn',
                 ($commissionDiag['conv_with_payout'] ?? 0) == 0 && ($commissionDiag['approved_conversions'] ?? 0) > 0 ? 'All approved conversions have $0 payout — commission = $0' : ''],
                ['Pending Conversions',        $commissionDiag['pending_conversions'] ?? 0, 'info', ''],
                ['Commission Records in DB',   $commissionDiag['commission_records'] ?? 0,
                 ($commissionDiag['commission_records'] ?? 0) > 0 ? 'ok' : 'warn',
                 ''],
            ];
            foreach ($diagItems as [$label, $value, $status, $hint]):
                $dot = $status === 'ok' ? '&#10003;' : ($status === 'warn' ? '&#9888;' : '&#8226;');
                $clr = $status === 'ok' ? '#059669' : ($status === 'warn' ? '#B45309' : '#6B7280');
            ?>
            <tr>
                <td style="padding:3px 8px 3px 0;color:<?= $clr ?>;font-size:15px;width:20px"><?= $dot ?></td>
                <td style="padding:3px 12px 3px 0;color:#475569;font-weight:600"><?= $label ?></td>
                <td style="padding:3px 12px 3px 0;color:#1E293B;font-weight:700"><?= htmlspecialchars((string)$value) ?></td>
                <?php if ($hint): ?><td style="padding:3px 0;color:#92400E;font-size:12px"><?= htmlspecialchars($hint) ?></td><?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (isset($commissionDiag['error'])): ?>
            <tr><td colspan="4" style="color:#DC2626;padding:4px 0">Error: <?= htmlspecialchars($commissionDiag['error']) ?></td></tr>
            <?php endif; ?>
            </table>
            <?php if (($commissionDiag['conv_with_payout'] ?? 0) > 0 && ($commissionDiag['commission_records'] ?? 0) === 0): ?>
            <div style="margin-top:10px;background:#FEF3C7;border:1px solid #FCD34D;border-radius:6px;padding:8px 12px;color:#92400E;font-weight:600">
                &#9888; <?= ($commissionDiag['conv_with_payout'] ?? 0) ?> eligible conversion(s) found but 0 commission records exist.
                Click <strong>Run Recalculation</strong> below to process them now.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <form method="POST">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="recalculate_commissions">
            <div style="margin-bottom:10px">
                <span style="font-size:12px;color:#64748B;font-weight:600">Quick range:</span>
                <button type="button" style="margin-left:8px;font-size:11px;padding:2px 8px;border:1px solid #CBD5E1;border-radius:4px;background:#fff;cursor:pointer"
                    onclick="document.querySelector('[name=recalc_from]').value='2020-01-01';document.querySelector('[name=recalc_to]').value='<?= date('Y-m-d') ?>'">All Time</button>
                <button type="button" style="margin-left:4px;font-size:11px;padding:2px 8px;border:1px solid #CBD5E1;border-radius:4px;background:#fff;cursor:pointer"
                    onclick="document.querySelector('[name=recalc_from]').value='<?= date('Y-m-d') ?>';document.querySelector('[name=recalc_to]').value='<?= date('Y-m-d') ?>'">Today</button>
                <button type="button" style="margin-left:4px;font-size:11px;padding:2px 8px;border:1px solid #CBD5E1;border-radius:4px;background:#fff;cursor:pointer"
                    onclick="document.querySelector('[name=recalc_from]').value='<?= date('Y-m-d', strtotime('monday this week')) ?>';document.querySelector('[name=recalc_to]').value='<?= date('Y-m-d') ?>'">This Week</button>
                <button type="button" style="margin-left:4px;font-size:11px;padding:2px 8px;border:1px solid #CBD5E1;border-radius:4px;background:#fff;cursor:pointer"
                    onclick="document.querySelector('[name=recalc_from]').value='<?= date('Y-m-d', strtotime('-14 days')) ?>';document.querySelector('[name=recalc_to]').value='<?= date('Y-m-d') ?>'">15 Days</button>
                <button type="button" style="margin-left:4px;font-size:11px;padding:2px 8px;border:1px solid #CBD5E1;border-radius:4px;background:#fff;cursor:pointer"
                    onclick="document.querySelector('[name=recalc_from]').value='<?= date('Y-m-01') ?>';document.querySelector('[name=recalc_to]').value='<?= date('Y-m-d') ?>'">This Month</button>
                <button type="button" style="margin-left:4px;font-size:11px;padding:2px 8px;border:1px solid #CBD5E1;border-radius:4px;background:#fff;cursor:pointer"
                    onclick="document.querySelector('[name=recalc_from]').value='<?= date('Y-01-01') ?>';document.querySelector('[name=recalc_to]').value='<?= date('Y-m-d') ?>'">This Year</button>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:480px;margin-bottom:14px">
                <div class="form-group" style="margin:0">
                    <label style="font-weight:600;font-size:13px">From Date</label>
                    <input type="date" name="recalc_from" class="form-control"
                           value="2020-01-01"
                           style="font-size:13px">
                </div>
                <div class="form-group" style="margin:0">
                    <label style="font-weight:600;font-size:13px">To Date</label>
                    <input type="date" name="recalc_to" class="form-control"
                           value="<?= date('Y-m-d') ?>"
                           style="font-size:13px">
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
                <button class="btn btn-primary btn-sm" style="background:#059669;border-color:#059669"
                        onclick="return confirm('Process all approved conversions in this date range?\nRate: <?= number_format((float)$manager['commission_rate'], 2) ?>%\nDuplicates are skipped safely.')">
                    &#9881; Run Recalculation
                </button>
                <button type="button" class="btn btn-secondary btn-sm"
                        onclick="document.getElementById('recalc-panel').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
</div>

<div class="grid-2 mb-3">
    <!-- Permissions + Rate -->
    <div class="card">
        <div class="card-header"><span class="card-title">Permissions &amp; Settings</span></div>
        <div class="card-body">
            <form method="POST">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="submit_type" value="permissions">
                <?php foreach ($permLabels as $key => $label): ?>
                <div class="form-check" style="margin-bottom:10px">
                    <input type="checkbox" name="permissions[]" value="<?= $key ?>" id="ep_<?= $key ?>"
                           <?= in_array($key, $managerPerms) ? 'checked' : '' ?>>
                    <label for="ep_<?= $key ?>"><?= $label ?></label>
                </div>
                <?php endforeach; ?>
                <div class="form-group mt-2">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= Helpers::e($manager['notes'] ?? '') ?></textarea>
                </div>
                <div class="form-group" style="background:#FAFAF9;border:1px solid #E5E7EB;border-radius:8px;padding:14px">
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                        <span style="background:#EEF2FF;color:#4338CA;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;letter-spacing:.04em">ADMIN ONLY</span>
                        Commission Rate (%) &mdash; calculated from Net Profit only
                    </label>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        <input type="number" name="commission_rate" class="form-control" min="0" max="100" step="0.01"
                               value="<?= Helpers::e($manager['commission_rate'] ?? '0') ?>"
                               style="max-width:140px">
                        <span style="font-size:12px;color:#6B7280">% of (Advertiser Revenue &minus; Affiliate Payout). <strong>Never shown</strong> to the manager.</span>
                    </div>
                </div>

                <!-- ── Offer-Specific Commission Selection ──────────────────── -->
                <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:14px;margin-top:10px">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px">
                        <label style="display:flex;align-items:center;gap:8px;margin:0">
                            <span style="background:#DCFCE7;color:#166534;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;letter-spacing:.04em">ADMIN ONLY</span>
                            <span style="font-weight:600;color:#166534">Selected Offers Commission</span>
                        </label>
                        <button type="button" id="open-offer-modal-btn"
                            style="background:#16A34A;color:#fff;border:none;border-radius:6px;padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px"
                            id="open-offer-modal-btn">
                            &#43; Select Offers
                        </button>
                    </div>

                    <!-- ── Commission Mode Toggle ─────────────────────────────── -->
                    <div style="display:flex;gap:0;border:1px solid #BBF7D0;border-radius:8px;overflow:hidden;margin-bottom:12px;background:#fff">
                        <label id="mode-all-label" style="flex:1;display:flex;align-items:center;gap:8px;padding:10px 14px;cursor:pointer;transition:background .15s;<?= ($manager['commission_mode'] ?? 'all') === 'all' ? 'background:#DCFCE7;' : '' ?>">
                            <input type="radio" name="commission_mode" value="all" id="mode_all"
                                   <?= ($manager['commission_mode'] ?? 'all') === 'all' ? 'checked' : '' ?>
                                   style="accent-color:#16A34A;width:15px;height:15px;flex-shrink:0">
                            <span>
                                <strong style="font-size:13px;color:#166534;display:block">All Offers</strong>
                                <span style="font-size:11px;color:#6B7280">Commission applies to every offer (uses the rate above)</span>
                            </span>
                        </label>
                        <div style="width:1px;background:#BBF7D0"></div>
                        <label id="mode-selected-label" style="flex:1;display:flex;align-items:center;gap:8px;padding:10px 14px;cursor:pointer;transition:background .15s;<?= ($manager['commission_mode'] ?? 'all') === 'selected_only' ? 'background:#DCFCE7;' : '' ?>">
                            <input type="radio" name="commission_mode" value="selected_only" id="mode_selected_only"
                                   <?= ($manager['commission_mode'] ?? 'all') === 'selected_only' ? 'checked' : '' ?>
                                   style="accent-color:#16A34A;width:15px;height:15px;flex-shrink:0">
                            <span>
                                <strong style="font-size:13px;color:#166534;display:block">Selected Offers Only</strong>
                                <span style="font-size:11px;color:#6B7280">Only ticked offers generate commission — others earn nothing</span>
                            </span>
                        </label>
                    </div>

                    <!-- ── Selected Offers Panel (shown when Selected Only mode is active) ── -->
                    <div id="selected-offers-panel" style="<?= ($manager['commission_mode'] ?? 'all') !== 'selected_only' ? 'display:none;' : '' ?>">
                        <p style="font-size:12px;color:#166534;margin:0 0 8px">
                            <strong>Selected offers only</strong> — Only the offers ticked below generate commission.
                            Others convert normally but the manager earns nothing on them.
                        </p>

                        <!-- Selected offers table (visible selection summary) -->
                        <div id="selected-offers-summary">
                        <?php if (empty($managerOfferCommissions)): ?>
                            <p id="no-offers-msg" style="font-size:12px;color:#6B7280;margin:0">
                                No specific offers selected — commission rate above applies to <strong>all</strong> eligible offers.
                            </p>
                        <?php else: ?>
                            <p id="no-offers-msg" style="font-size:12px;color:#6B7280;margin:0;display:none">
                                No specific offers selected — commission rate above applies to <strong>all</strong> eligible offers.
                            </p>
                        <?php endif; ?>
                        <div id="selected-offers-list" style="display:flex;flex-direction:column;gap:6px;margin-top:4px">
                            <?php foreach ($managerOfferCommissions as $selOfferId => $selOfferRate): ?>
                                <?php
                                    $offerName = '—';
                                    foreach ($allOffers as $ao) {
                                        if ((int)$ao['id'] === (int)$selOfferId) { $offerName = $ao['name']; break; }
                                    }
                                ?>
                                <div class="selected-offer-row" data-offer-id="<?= (int)$selOfferId ?>"
                                     style="display:flex;align-items:center;gap:8px;background:#fff;border:1px solid #BBF7D0;border-radius:6px;padding:6px 10px">
                                    <span style="flex:1;font-size:13px;font-weight:500;color:#166534"><?= htmlspecialchars($offerName) ?></span>
                                    <span style="font-size:12px;color:#6B7280">Commission %:</span>
                                    <input type="number" name="offer_commission_rates[<?= (int)$selOfferId ?>]"
                                           value="<?= number_format($selOfferRate, 2, '.', '') ?>"
                                           min="0" max="100" step="0.01"
                                           style="width:80px;padding:3px 6px;border:1px solid #D1FAE5;border-radius:4px;font-size:13px">
                                    <input type="hidden" name="offer_ids[]" value="<?= (int)$selOfferId ?>">
                                    <button type="button" class="remove-offer-btn" data-offer-id="<?= (int)$selOfferId ?>"
                                            style="background:#FEE2E2;color:#DC2626;border:none;border-radius:4px;padding:2px 8px;font-size:12px;cursor:pointer">
                                        &times;
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        </div>
                    </div><!-- /selected-offers-panel -->
                </div>

                <!-- ── Offer Selection Modal ─────────────────────────────────── -->
                <div id="offer-select-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center">
                    <div style="background:#fff;border-radius:12px;width:min(680px,95vw);max-height:85vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,0.3)">
                        <!-- Modal header -->
                        <div style="padding:16px 20px;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between">
                            <div>
                                <h3 style="margin:0;font-size:16px;color:#111827">Select Offers for Commission</h3>
                                <p style="margin:4px 0 0;font-size:12px;color:#6B7280">Tick offers that generate commission. Set per-offer % or leave at 0 to use the global rate.</p>
                            </div>
                            <button type="button" id="close-offer-modal-btn"
                                    style="background:none;border:none;font-size:20px;color:#6B7280;cursor:pointer;line-height:1">&times;</button>
                        </div>
                        <!-- Search -->
                        <div style="padding:12px 20px;border-bottom:1px solid #F3F4F6">
                            <input type="text" id="offer-search-input" placeholder="&#128269; Search offers..."
                                   style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:6px;font-size:14px;box-sizing:border-box">
                        </div>
                        <!-- Offer list -->
                        <div id="offer-modal-list" style="flex:1;overflow-y:auto;padding:12px 20px;display:flex;flex-direction:column;gap:6px">
                            <?php foreach ($allOffers as $ao): ?>
                                <?php $isSelected = array_key_exists((int)$ao['id'], $managerOfferCommissions); ?>
                                <label class="offer-modal-row" data-offer-name="<?= htmlspecialchars(strtolower($ao['name'])) ?>"
                                       style="display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid <?= $isSelected ? '#BBF7D0' : '#E5E7EB' ?>;border-radius:6px;cursor:pointer;background:<?= $isSelected ? '#F0FDF4' : '#fff' ?>;transition:background .15s">
                                    <input type="checkbox" class="offer-modal-cb" data-offer-id="<?= (int)$ao['id'] ?>"
                                           data-offer-name="<?= htmlspecialchars($ao['name']) ?>"
                                           <?= $isSelected ? 'checked' : '' ?>
                                           style="width:16px;height:16px;accent-color:#16A34A;flex-shrink:0">
                                    <span style="flex:1;font-size:13px;font-weight:500;color:#111827"><?= htmlspecialchars($ao['name']) ?></span>
                                    <span style="font-size:11px;padding:2px 6px;border-radius:4px;background:<?= $ao['status']==='active' ? '#DCFCE7' : '#FEF3C7' ?>;color:<?= $ao['status']==='active' ? '#166534' : '#92400E' ?>">
                                        <?= htmlspecialchars($ao['status']) ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($allOffers)): ?>
                                <p style="text-align:center;color:#9CA3AF;padding:20px">No offers found.</p>
                            <?php endif; ?>
                        </div>
                        <!-- Modal footer -->
                        <div style="padding:14px 20px;border-top:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between;gap:10px">
                            <span id="offer-modal-count" style="font-size:13px;color:#6B7280">0 offers selected</span>
                            <div style="display:flex;gap:8px">
                                <button type="button" id="cancel-offer-modal-btn"
                                        style="background:#F3F4F6;color:#374151;border:none;border-radius:6px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer">
                                    Cancel
                                </button>
                                <button type="button" id="apply-offer-modal-btn"
                                        style="background:#16A34A;color:#fff;border:none;border-radius:6px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer">
                                    Apply Selection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                (function(){
                    // Current offer commissions from PHP (offer_id → rate)
                    var existingRates = <?= json_encode($managerOfferCommissions) ?>;

                    var modal        = document.getElementById('offer-select-modal');
                    var openBtn      = document.getElementById('open-offer-modal-btn');
                    var closeBtn     = document.getElementById('close-offer-modal-btn');
                    var cancelBtn    = document.getElementById('cancel-offer-modal-btn');
                    var applyBtn     = document.getElementById('apply-offer-modal-btn');
                    var searchInput  = document.getElementById('offer-search-input');
                    var modalList    = document.getElementById('offer-modal-list');
                    var countLabel   = document.getElementById('offer-modal-count');
                    var listDiv      = document.getElementById('selected-offers-list');
                    var noOffersMsg  = document.getElementById('no-offers-msg');

                    function updateCount() {
                        var checked = modalList.querySelectorAll('.offer-modal-cb:checked').length;
                        countLabel.textContent = checked + ' offer' + (checked !== 1 ? 's' : '') + ' selected';
                    }

                    function openModal() {
                        modal.style.display = 'flex';
                        searchInput.value = '';
                        filterOffers('');
                        updateCount();
                    }
                    function closeModal() { modal.style.display = 'none'; }

                    openBtn.addEventListener('click', openModal);
                    closeBtn.addEventListener('click', closeModal);
                    cancelBtn.addEventListener('click', closeModal);
                    modal.addEventListener('click', function(e){ if(e.target===modal) closeModal(); });

                    searchInput.addEventListener('input', function(){ filterOffers(this.value.toLowerCase()); });

                    function filterOffers(q) {
                        modalList.querySelectorAll('.offer-modal-row').forEach(function(row){
                            row.style.display = (!q || row.dataset.offerName.includes(q)) ? '' : 'none';
                        });
                    }

                    modalList.addEventListener('change', function(e){
                        if(e.target.classList.contains('offer-modal-cb')) {
                            var label = e.target.closest('.offer-modal-row');
                            if (e.target.checked) {
                                label.style.background = '#F0FDF4';
                                label.style.borderColor = '#BBF7D0';
                            } else {
                                label.style.background = '#fff';
                                label.style.borderColor = '#E5E7EB';
                            }
                            updateCount();
                        }
                    });

                    applyBtn.addEventListener('click', function(){
                        // Build new selection from checkboxes
                        var checked = modalList.querySelectorAll('.offer-modal-cb:checked');
                        // Remove all current rows
                        listDiv.querySelectorAll('.selected-offer-row').forEach(function(r){ r.remove(); });

                        checked.forEach(function(cb){
                            var offerId   = cb.dataset.offerId;
                            var offerName = cb.dataset.offerName;
                            var rate      = (existingRates[offerId] !== undefined) ? existingRates[offerId] : 0;

                            var row = document.createElement('div');
                            row.className = 'selected-offer-row';
                            row.dataset.offerId = offerId;
                            row.style.cssText = 'display:flex;align-items:center;gap:8px;background:#fff;border:1px solid #BBF7D0;border-radius:6px;padding:6px 10px';
                            row.innerHTML =
                                '<span style="flex:1;font-size:13px;font-weight:500;color:#166534">' + offerName.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</span>' +
                                '<span style="font-size:12px;color:#6B7280">Commission %:</span>' +
                                '<input type="number" name="offer_commission_rates[' + offerId + ']" value="' + parseFloat(rate).toFixed(2) + '" min="0" max="100" step="0.01" style="width:80px;padding:3px 6px;border:1px solid #D1FAE5;border-radius:4px;font-size:13px">' +
                                '<input type="hidden" name="offer_ids[]" value="' + offerId + '">' +
                                '<button type="button" class="remove-offer-btn" data-offer-id="' + offerId + '" style="background:#FEE2E2;color:#DC2626;border:none;border-radius:4px;padding:2px 8px;font-size:12px;cursor:pointer">&times;</button>';
                            listDiv.appendChild(row);
                        });

                        updateNoOffersMsg();
                        closeModal();
                    });

                    // Remove offer from selection
                    listDiv.addEventListener('click', function(e){
                        var btn = e.target.closest('.remove-offer-btn');
                        if (!btn) return;
                        var offerId = btn.dataset.offerId;
                        // Uncheck in modal
                        var cb = modalList.querySelector('.offer-modal-cb[data-offer-id="' + offerId + '"]');
                        if (cb) {
                            cb.checked = false;
                            var label = cb.closest('.offer-modal-row');
                            label.style.background = '#fff';
                            label.style.borderColor = '#E5E7EB';
                        }
                        btn.closest('.selected-offer-row').remove();
                        updateNoOffersMsg();
                        updateCount();
                    });

                    function updateNoOffersMsg() {
                        var hasOffers = listDiv.querySelectorAll('.selected-offer-row').length > 0;
                        noOffersMsg.style.display = hasOffers ? 'none' : '';
                    }

                    // ── Commission Mode Toggle ────────────────────────────────
                    var modeAllRadio      = document.getElementById('mode_all');
                    var modeSelectedRadio = document.getElementById('mode_selected_only');
                    var modeAllLabel      = document.getElementById('mode-all-label');
                    var modeSelectedLabel = document.getElementById('mode-selected-label');
                    var selectedPanel     = document.getElementById('selected-offers-panel');
                    var openOfferBtn      = document.getElementById('open-offer-modal-btn');

                    function applyModeUI(mode) {
                        if (mode === 'selected_only') {
                            selectedPanel.style.display = '';
                            modeSelectedLabel.style.background = '#DCFCE7';
                            modeAllLabel.style.background = '';
                        } else {
                            selectedPanel.style.display = 'none';
                            modeAllLabel.style.background = '#DCFCE7';
                            modeSelectedLabel.style.background = '';
                        }
                    }

                    if (modeAllRadio) {
                        modeAllRadio.addEventListener('change', function(){ if(this.checked) applyModeUI('all'); });
                    }
                    if (modeSelectedRadio) {
                        modeSelectedRadio.addEventListener('change', function(){ if(this.checked) applyModeUI('selected_only'); });
                    }
                    // Init on load
                    applyModeUI(modeSelectedRadio && modeSelectedRadio.checked ? 'selected_only' : 'all');
                })();
                </script>
                <div class="form-check" style="margin-top:10px;padding:10px 14px;background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px">
                    <input type="checkbox" name="hide_earnings" id="hide_earnings_cb" value="1"
                           <?= (int)($manager['hide_earnings'] ?? 0) ? 'checked' : '' ?>>
                    <label for="hide_earnings_cb" style="font-weight:600;color:#92400E">
                        Hide &ldquo;My Earnings&rdquo; section from this manager
                    </label>
                    <div style="font-size:11px;color:#B45309;margin-top:2px;margin-left:20px">
                        When enabled, the manager cannot see their earnings history or commission details.
                    </div>
                </div>
                <hr style="margin:16px 0">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:6px"><span style="color:#00AFF0">&#128222;</span> Skype</label>
                        <input type="text" name="skype" class="form-control" value="<?= Helpers::e($manager['skype'] ?? '') ?>" placeholder="live:username">
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:6px"><span style="color:#2AABEE">&#128232;</span> Telegram</label>
                        <input type="text" name="telegram" class="form-control" value="<?= Helpers::e($manager['telegram'] ?? '') ?>" placeholder="@username">
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:6px"><span style="color:#5865F2">&#127918;</span> Discord</label>
                        <input type="text" name="discord" class="form-control" value="<?= Helpers::e($manager['discord'] ?? '') ?>" placeholder="username or user ID">
                    </div>
                </div>
                <button class="btn btn-primary">Save Permissions &amp; Contacts</button>
            </form>
        </div>
    </div>

    <!-- Status + Balance Adjustment -->
    <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
            <div class="card-header"><span class="card-title">Account Status</span></div>
            <div class="card-body">
                <form method="POST">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="submit_type" value="status">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active"    <?= $manager['status'] === 'active'    ? 'selected' : '' ?>>Active</option>
                            <option value="suspended" <?= $manager['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>

        <!-- Manual Balance Adjustment -->
        <div class="card" style="border-left:4px solid #4F46E5">
            <div class="card-header">
                <span class="card-title">&#128178; Adjust Balance</span>
                <span style="font-size:12px;color:#94A3B8">Admin override</span>
            </div>
            <div class="card-body">
                <div style="background:#EEF2FF;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#3730A3">
                    <strong>Current Balance:</strong>
                    <span style="font-size:16px;font-weight:800;margin-left:8px">$<?= number_format((float)$mgrBalance['balance'], 2) ?></span>
                </div>
                <form method="POST" onsubmit="return confirmAdjust(this)">
                    <?= Helpers::csrf() ?>
                    <input type="hidden" name="submit_type" value="adjust_balance">
                    <div class="form-group">
                        <label style="font-weight:600">Adjustment Type</label>
                        <select name="adj_type" id="adj-type" class="form-control" onchange="updateAdjPreview()">
                            <option value="add">Add to Balance (Credit)</option>
                            <option value="subtract">Subtract from Balance (Debit)</option>
                            <option value="set">Set Balance to Exact Amount</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600">Amount ($)</label>
                        <div style="position:relative">
                            <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#64748B;font-weight:600">$</span>
                            <input type="number" name="adj_amount" id="adj-amount" class="form-control" required
                                   min="0" step="0.01" placeholder="0.00"
                                   style="padding-left:22px" oninput="updateAdjPreview()">
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600">Reason / Note</label>
                        <input type="text" name="adj_note" class="form-control" placeholder="e.g. Manual correction, bonus, refund..." maxlength="255">
                    </div>
                    <div id="adj-preview" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;padding:12px;margin-bottom:12px;font-size:13px;display:none">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                            <span style="color:#64748B">Before:</span>
                            <strong>$<?= number_format((float)$mgrBalance['balance'], 2) ?></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                            <span style="color:#64748B">Adjustment:</span>
                            <strong id="adj-diff" style="color:#4F46E5">—</strong>
                        </div>
                        <div style="border-top:1px solid #E2E8F0;padding-top:6px;display:flex;justify-content:space-between">
                            <span style="font-weight:700">After:</span>
                            <strong id="adj-after" style="color:#059669">—</strong>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm" style="background:#4F46E5;border-color:#4F46E5">Apply Adjustment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
var _curBal = <?= (float)$mgrBalance['balance'] ?>;
function updateAdjPreview() {
    var amt  = parseFloat(document.getElementById('adj-amount').value) || 0;
    var type = document.getElementById('adj-type').value;
    var prev = document.getElementById('adj-preview');
    if (amt <= 0) { prev.style.display='none'; return; }
    prev.style.display='block';
    var after, diff;
    if (type === 'add')      { after = _curBal + amt; diff = '+$' + amt.toFixed(2); }
    else if (type==='subtract') { after = Math.max(0, _curBal - amt); diff = '−$' + amt.toFixed(2); }
    else                     { after = amt; diff = 'Set to $' + amt.toFixed(2); }
    document.getElementById('adj-diff').textContent  = diff;
    document.getElementById('adj-after').textContent = '$' + after.toFixed(2);
    document.getElementById('adj-after').style.color = after > 0 ? '#059669' : '#94A3B8';
}
function confirmAdjust(form) {
    var amt  = parseFloat(form.adj_amount.value) || 0;
    var type = form.adj_type.value;
    var note = form.adj_note.value || '(no reason given)';
    var label = type==='add' ? 'Add $'+amt.toFixed(2)+' to' : (type==='subtract' ? 'Subtract $'+amt.toFixed(2)+' from' : 'Set');
    return confirm(label + ' this manager\'s balance?\n\nReason: ' + note);
}
</script>

<!-- Assigned Affiliates -->
<div class="card mb-3">
    <div class="card-header">
        <span class="card-title">Assigned Affiliates</span>
        <span class="text-muted text-sm"><?= count($assignedAffiliates) ?> affiliates</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Affiliate</th><th>Code</th><th>Assigned On</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if (empty($assignedAffiliates)): ?>
            <tr><td colspan="5" class="text-center text-muted" style="padding:20px">No affiliates assigned yet</td></tr>
            <?php else: ?>
            <?php foreach ($assignedAffiliates as $a): ?>
            <tr>
                <td>
                    <div class="fw-bold"><?= Helpers::e($a['first_name'] . ' ' . $a['last_name']) ?></div>
                    <div class="text-sm text-muted"><?= Helpers::e($a['email']) ?></div>
                </td>
                <td><code style="background:#F1F5F9;padding:2px 6px;border-radius:4px;font-size:12px"><?= Helpers::e($a['affiliate_code']) ?></code></td>
                <td class="text-sm text-muted">
                    <?= $a['manager_assigned_at'] ? date('M j, Y H:i', strtotime($a['manager_assigned_at'])) : '<em>Before tracking</em>' ?>
                </td>
                <td><span class="badge badge-<?= ['active'=>'success','pending'=>'warning','suspended'=>'danger','rejected'=>'muted'][$a['status']] ?? 'muted' ?>"><?= $a['status'] ?></span></td>
                <td>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="submit_type" value="unassign">
                        <input type="hidden" name="affiliate_id" value="<?= $a['id'] ?>">
                        <button class="btn btn-danger btn-sm"
                                onclick="return confirm('Remove this affiliate from manager?')">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign new affiliate -->
<?php if (!empty($unassignedAffiliates)): ?>
<div class="card">
    <div class="card-header"><span class="card-title">Assign Affiliate</span></div>
    <div class="card-body">
        <div style="font-size:12px;color:#6B7280;margin-bottom:12px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:6px;padding:8px 12px">
            &#9888; Commission will only apply to conversions that occur <strong>after the assignment date</strong>. No retroactive earnings.
        </div>
        <form method="POST" class="d-flex gap-3 align-center">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="submit_type" value="assign">
            <select name="affiliate_id" class="form-control" required>
                <option value="">Select affiliate...</option>
                <?php foreach ($unassignedAffiliates as $ua): ?>
                <option value="<?= $ua['id'] ?>"><?= Helpers::e($ua['first_name'] . ' ' . $ua['last_name'] . ' — ' . $ua['affiliate_code']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" style="white-space:nowrap">Assign Affiliate</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ── Commission Activity Log ───────────────────────────────────────────────── -->
<div class="card mt-3">
    <div class="card-header">
        <span class="card-title">&#128203; Commission Activity Log</span>
        <span style="font-size:12px;color:#94A3B8">Last 30 attempts — shows why each conversion did or did not generate commission</span>
    </div>
    <?php if (empty($commissionLogs)): ?>
    <div class="card-body" style="text-align:center;padding:32px;color:#94A3B8">
        <div style="font-size:28px;margin-bottom:8px">&#128203;</div>
        No commission activity yet.
        <?php if ((float)$manager['commission_rate'] > 0 && !empty($assignedAffiliates)): ?>
        <div style="margin-top:12px;background:#FFFBEB;border:1px solid #FCD34D;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400E;text-align:left;max-width:520px;margin-left:auto;margin-right:auto">
            <strong>&#9888; Rate is set but no conversions recorded yet.</strong><br>
            Common causes:
            <ul style="margin:8px 0 0 16px;padding:0">
                <li>No approved conversions yet for assigned affiliates</li>
                <li>Offer <strong>Revenue Amount</strong> is $0.00 or equal to Payout — set Revenue > Payout on each offer</li>
                <li>Click <strong>Recalculate Commissions</strong> above to process existing approved conversions</li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table style="font-size:12px">
            <thead><tr>
                <th>Date</th>
                <th>Affiliate</th>
                <th>Offer</th>
                <th>Mode</th>
                <th>Revenue</th>
                <th>Payout</th>
                <th>Net Profit</th>
                <th>Rate</th>
                <th>Commission</th>
                <th>Result</th>
            </tr></thead>
            <tbody>
            <?php foreach ($commissionLogs as $lg): ?>
            <?php
                $resultColor = ['credited'=>'#059669','skipped'=>'#DC2626','duplicate'=>'#94A3B8'][$lg['result']] ?? '#64748B';
                $resultIcon  = ['credited'=>'&#10003;','skipped'=>'&#9888;','duplicate'=>'&#8634;'][$lg['result']] ?? '?';
                $mode = $lg['calc_mode'] ?? '';
                $modeLabel = $mode === 'net_profit' ? '<span style="color:#7C3AED;font-size:10px;font-weight:700">NET PROFIT</span>'
                    : ($mode === 'payout_based' ? '<span style="color:#0369A1;font-size:10px;font-weight:700">PAYOUT</span>' : '');
            ?>
            <tr title="<?= $lg['skip_reason'] ? Helpers::e($lg['skip_reason']) : 'Commission credited to balance' ?>">
                <td class="text-muted" style="white-space:nowrap"><?= date('M j H:i', strtotime($lg['logged_at'])) ?></td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($lg['affiliate_name'] ?? '—') ?></div>
                    <div class="text-muted" style="font-size:11px"><?= Helpers::e($lg['affiliate_code'] ?? '') ?></div>
                </td>
                <td class="text-muted" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= Helpers::e($lg['offer_name'] ?? '—') ?></td>
                <td><?= $modeLabel ?></td>
                <td><?= $lg['advertiser_revenue'] > 0 ? '$'.number_format($lg['advertiser_revenue'],2) : '<span style="color:#94A3B8">—</span>' ?></td>
                <td><?= $lg['affiliate_payout'] > 0 ? '$'.number_format($lg['affiliate_payout'],2) : '<span style="color:#94A3B8">$0</span>' ?></td>
                <td style="color:<?= (float)$lg['net_profit'] > 0 ? '#059669' : '#64748B' ?>;font-weight:600">
                    $<?= number_format((float)$lg['net_profit'],2) ?>
                </td>
                <td><?= number_format($lg['commission_rate'],1) ?>%</td>
                <td style="font-weight:700;color:<?= (float)$lg['commission_amount'] > 0 ? '#059669' : '#94A3B8' ?>">
                    $<?= number_format((float)$lg['commission_amount'],4) ?>
                </td>
                <td style="white-space:nowrap">
                    <span style="color:<?= $resultColor ?>;font-weight:700"
                          title="<?= $lg['skip_reason'] ? Helpers::e($lg['skip_reason']) : 'Credited' ?>">
                        <?= $resultIcon ?> <?= ucfirst($lg['result']) ?>
                        <?php if ($lg['skip_reason']): ?><span style="font-size:10px;display:block;color:#94A3B8;max-width:160px;white-space:normal;font-weight:400"><?= Helpers::e(substr($lg['skip_reason'],0,80)) ?><?= strlen($lg['skip_reason'])>80?'…':'' ?></span><?php endif; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
