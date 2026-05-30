<?php
$pageTitle = 'Create Invoice';
require BASE_PATH . '/views/layouts/admin.php';
?>

<div class="page-header">
    <div>
        <h1>Create Invoice</h1>
        <p>Auto-load offers by period, edit payouts, generate PDF &amp; send email</p>
    </div>
    <a href="/admin/invoices" class="btn btn-secondary">← Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div>• <?= Helpers::e($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Step 1: Setup ────────────────────────────────────────────────────────── -->
<div class="card mb-3" id="stepSetup">
    <div class="card-header" style="background:linear-gradient(135deg,#4338CA,#6D28D9);border-radius:8px 8px 0 0">
        <span class="card-title" style="color:#fff">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-3px;margin-right:6px"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Step 1 — Select Affiliate &amp; Period
        </span>
    </div>
    <div class="card-body">
        <div class="form-row cols-3" style="align-items:flex-end">
            <div class="form-group" style="margin-bottom:0">
                <label>Invoice Type</label>
                <select id="invType" class="form-control" onchange="toggleEntityList()">
                    <option value="affiliate_payout">Affiliate Payout</option>
                    <option value="advertiser_billing">Advertiser Billing</option>
                    <option value="manager_fee">Affiliate Manager Fee</option>
                </select>
            </div>
            <div class="form-group" id="affSelectWrap" style="margin-bottom:0">
                <label>Affiliate <span style="color:#EF4444">*</span></label>
                <select id="affSelect" class="form-control">
                    <option value="">Select affiliate...</option>
                    <?php foreach ($affiliates as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="advSelectWrap" style="display:none;margin-bottom:0">
                <label>Advertiser <span style="color:#EF4444">*</span></label>
                <select id="advSelect" class="form-control">
                    <option value="">Select advertiser...</option>
                    <?php foreach ($advertisers as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="mgrSelectWrap" style="display:none;margin-bottom:0">
                <label>Affiliate Manager <span style="color:#EF4444">*</span></label>
                <select id="mgrSelect" class="form-control">
                    <option value="">Select manager...</option>
                    <?php foreach ($affiliateManagers as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= Helpers::e($m['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row cols-3" style="margin-top:16px;align-items:flex-end">
            <div class="form-group" style="margin-bottom:0">
                <label>Period Start <span style="color:#EF4444">*</span></label>
                <input type="date" id="periodFrom" class="form-control" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>Period End <span style="color:#EF4444">*</span></label>
                <input type="date" id="periodTo" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div style="margin-bottom:0">
                <button type="button" class="btn btn-primary" style="width:100%;height:40px" onclick="loadOffers()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                    Load Offers
                </button>
            </div>
        </div>

        <div id="affBalanceBar" style="display:none"></div>
        <div id="mgrBalanceBar" style="display:none"></div>
        <div id="loadMsg" style="margin-top:12px;display:none"></div>
    </div>
</div>

<!-- ── Step 2: Offers + invoice form (hidden until offers load) ─────────────── -->
<form method="POST" action="/admin/invoices/create" id="invoiceForm" style="display:none">
    <?= Helpers::csrf() ?>
    <input type="hidden" name="type" id="hidType" value="affiliate_payout">
    <input type="hidden" name="entity_id" id="hidEntityId" value="">
    <input type="hidden" name="period_start" id="hidFrom" value="">
    <input type="hidden" name="period_end"   id="hidTo"   value="">
    <input type="hidden" name="total_override" id="hidTotalOverride" value="">
    <input type="hidden" name="payment_details_override" id="hidPaymentDetails" value="">

    <!-- Line items table -->
    <div class="card mb-3">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;background:#16A34A;color:#fff;border-bottom:1px solid #15803D">
            <span class="card-title" style="color:#fff">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Step 2 — Review Line Items &amp; Generate
            </span>
            <span id="periodLabel" style="font-size:13px;color:#DCFCE7"></span>
        </div>
        <div class="card-body" style="padding:0">
            <div class="table-scroll-wrap">
            <table style="width:100%;border-collapse:collapse" id="offersTable">
                <thead>
                    <tr style="background:#F8FAFC;border-bottom:2px solid #E2E8F0">
                        <th style="padding:10px 14px;text-align:left;font-size:12px;color:#64748B;text-transform:uppercase">Offer / Description</th>
                        <th style="padding:10px 14px;text-align:right;font-size:12px;color:#64748B;width:110px">Qty</th>
                        <th style="padding:10px 14px;text-align:right;font-size:12px;color:#64748B;width:140px">Unit Price ($)</th>
                        <th style="padding:10px 14px;text-align:right;font-size:12px;color:#64748B;width:130px">Total ($)</th>
                        <th style="padding:10px 14px;width:44px"></th>
                    </tr>
                </thead>
                <tbody id="offerRows"></tbody>
                <tfoot>
                    <tr style="background:#F1F5F9;border-top:2px solid #E2E8F0">
                        <td style="padding:10px 14px;font-weight:700;font-size:13px;text-align:right" colspan="3">TOTAL INVOICE AMOUNT</td>
                        <td style="padding:10px 14px;text-align:right;font-weight:700;color:#16A34A;font-size:15px" id="totalPayout">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            </div><!-- /table-scroll-wrap -->

            <!-- Add Line Item button -->
            <div style="padding:12px 14px;border-top:1px solid #E2E8F0;background:#fff">
                <button type="button" class="btn btn-secondary btn-sm" onclick="addLineItem()" style="font-weight:600">
                    + Add Line Item
                </button>
            </div>

            <!-- (kept for the legacy "totalConversions" calculation; hidden because line items now use Qty)
                 Restoring on a Manager Fee invoice is unnecessary because manager fees only have one row. -->
            <span id="totalConversions" style="display:none">0</span>

            <!-- Hidden line item inputs (populated by JS before submit) -->
            <div id="hiddenItems"></div>
        </div>
    </div>

    <!-- Invoice settings -->
    <div class="grid-2 inv-bottom-grid" style="gap:20px">
        <div class="card mb-3">
            <div class="card-header"><span class="card-title">Invoice Settings</span></div>
            <div class="card-body">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" id="fldDueDate">
                    </div>
                    <div class="form-group">
                        <label>Tax Rate (%)</label>
                        <input type="number" step="0.01" name="tax_rate" id="fldTaxRate" class="form-control" value="0" min="0" max="100" oninput="recalcTotals()">
                    </div>
                </div>
                <!-- Quill rich-text editor for Notes/Description -->
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:6px">
                        Description / Notes
                        <span style="font-size:11px;color:#94A3B8;font-weight:400;background:#F1F5F9;padding:2px 7px;border-radius:99px">Rich Text</span>
                        <span style="font-size:11px;color:#94A3B8;font-weight:400">(optional — shown on PDF)</span>
                    </label>
                    <textarea name="notes" id="notesHidden" style="display:none"></textarea>
                    <div id="quill-notes-wrap" style="border:1px solid #E2E8F0;border-radius:8px;overflow:hidden;background:#fff">
                        <div id="quill-notes-editor"></div>
                    </div>
                </div>

                <!-- ── Rewards Section ──────────────────────────────────────── -->
                <div style="margin-top:18px;border-top:1px solid #E2E8F0;padding-top:16px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:linear-gradient(135deg,#7C3AED,#4F46E5);border-radius:8px">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            </span>
                            <span style="font-weight:700;font-size:13px;color:#1E1B4B">Rewards</span>
                            <span style="font-size:11px;color:#94A3B8;background:#EEF2FF;padding:2px 8px;border-radius:99px;font-weight:600">Milestone bonuses</span>
                        </div>
                        <button type="button" onclick="toggleSection('rewardsBody','rewardsChev')" style="background:transparent;border:none;cursor:pointer;color:#64748B;font-size:12px;display:flex;align-items:center;gap:4px">
                            <span id="rewardsChev" style="transition:transform .2s;display:inline-block">▼</span>
                        </button>
                    </div>
                    <div id="rewardsBody">
                        <p style="font-size:12.5px;color:#475569;margin:0 0 10px;line-height:1.6">
                            The <strong>Rewards</strong> module gives affiliates milestone bonuses when they cross earning thresholds. You can reference active reward milestones below as a note on this invoice — for example, a bonus payout triggered by a milestone reached this period.
                        </p>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-bottom:12px">
                            <div style="background:#EEF2FF;border:1px solid #C7D2FE;border-radius:10px;padding:12px">
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#6D28D9;font-weight:700;margin-bottom:4px">Milestone Bonus</div>
                                <div style="font-size:12px;color:#475569">Add a line item for a reward payout triggered by earnings milestone.</div>
                                <button type="button" onclick="addRewardLineItem()" style="margin-top:8px;font-size:11.5px;padding:5px 10px;background:#4F46E5;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600">+ Add Reward Line</button>
                            </div>
                            <div style="background:#F5F3FF;border:1px solid #DDD6FE;border-radius:10px;padding:12px">
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#6D28D9;font-weight:700;margin-bottom:4px">Loyalty Bonus</div>
                                <div style="font-size:12px;color:#475569">Add a line item for a loyalty or VIP tier bonus earned this period.</div>
                                <button type="button" onclick="addLoyaltyLineItem()" style="margin-top:8px;font-size:11.5px;padding:5px 10px;background:#7C3AED;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600">+ Add Loyalty Line</button>
                            </div>
                        </div>
                        <div style="background:#FAFAFA;border:1px solid #E2E8F0;border-radius:8px;padding:10px 12px">
                            <div style="font-size:11.5px;color:#64748B;margin-bottom:6px;font-weight:600">Quick-insert reward description into Notes:</div>
                            <div style="display:flex;flex-wrap:wrap;gap:6px">
                                <button type="button" onclick="appendNote('Milestone bonus — earnings threshold reached this billing period.')" style="font-size:11px;padding:4px 9px;background:#fff;border:1px solid #C7D2FE;border-radius:6px;cursor:pointer;color:#4338CA">Threshold bonus</button>
                                <button type="button" onclick="appendNote('VIP tier upgrade reward — congratulations on reaching the next level.')" style="font-size:11px;padding:4px 9px;background:#fff;border:1px solid #C7D2FE;border-radius:6px;cursor:pointer;color:#4338CA">VIP upgrade</button>
                                <button type="button" onclick="appendNote('Referral milestone reward — 10 active referrals unlocked.')" style="font-size:11px;padding:4px 9px;background:#fff;border:1px solid #C7D2FE;border-radius:6px;cursor:pointer;color:#4338CA">Referral unlock</button>
                            </div>
                        </div>
                        <div style="margin-top:10px;text-align:right">
                            <a href="/admin/rewards" target="_blank" style="font-size:11.5px;color:#4F46E5;text-decoration:none">Manage Rewards ↗</a>
                        </div>
                    </div>
                </div>

                <!-- ── Shop Section ────────────────────────────────────────── -->
                <div style="margin-top:16px;border-top:1px solid #E2E8F0;padding-top:16px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:linear-gradient(135deg,#059669,#10B981);border-radius:8px">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            </span>
                            <span style="font-weight:700;font-size:13px;color:#064E3B">Shop</span>
                            <span style="font-size:11px;color:#94A3B8;background:#D1FAE5;padding:2px 8px;border-radius:99px;font-weight:600;color:#065F46">Points redemption</span>
                        </div>
                        <button type="button" onclick="toggleSection('shopBody','shopChev')" style="background:transparent;border:none;cursor:pointer;color:#64748B;font-size:12px;display:flex;align-items:center;gap:4px">
                            <span id="shopChev" style="transition:transform .2s;display:inline-block">▼</span>
                        </button>
                    </div>
                    <div id="shopBody">
                        <p style="font-size:12.5px;color:#475569;margin:0 0 10px;line-height:1.6">
                            The <strong>Shop</strong> lets affiliates spend points on products. If an order includes cash-equivalent value or you need to invoice for a shop redemption credit, add a line item below. This does not affect shop orders directly.
                        </p>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-bottom:12px">
                            <div style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:12px">
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#047857;font-weight:700;margin-bottom:4px">Product Redemption</div>
                                <div style="font-size:12px;color:#475569">Add a line item for a shop product redeemed via points this period.</div>
                                <button type="button" onclick="addShopRedemptionLine()" style="margin-top:8px;font-size:11.5px;padding:5px 10px;background:#059669;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600">+ Add Shop Line</button>
                            </div>
                            <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:12px">
                                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#047857;font-weight:700;margin-bottom:4px">Points Credit</div>
                                <div style="font-size:12px;color:#475569">Add a line item for a points credit or top-up issued to this affiliate.</div>
                                <button type="button" onclick="addPointsCreditLine()" style="margin-top:8px;font-size:11.5px;padding:5px 10px;background:#10B981;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600">+ Add Points Line</button>
                            </div>
                        </div>
                        <div style="background:#FAFAFA;border:1px solid #E2E8F0;border-radius:8px;padding:10px 12px">
                            <div style="font-size:11.5px;color:#64748B;margin-bottom:6px;font-weight:600">Quick-insert shop description into Notes:</div>
                            <div style="display:flex;flex-wrap:wrap;gap:6px">
                                <button type="button" onclick="appendNote('Shop redemption credit — product redeemed via points this period.')" style="font-size:11px;padding:4px 9px;background:#fff;border:1px solid #A7F3D0;border-radius:6px;cursor:pointer;color:#047857">Redemption credit</button>
                                <button type="button" onclick="appendNote('Points balance adjustment — manual top-up applied to account.')" style="font-size:11px;padding:4px 9px;background:#fff;border:1px solid #A7F3D0;border-radius:6px;cursor:pointer;color:#047857">Points top-up</button>
                                <button type="button" onclick="appendNote('Shop order fulfilled — cash equivalent payout for redeemed prize.')" style="font-size:11px;padding:4px 9px;background:#fff;border:1px solid #A7F3D0;border-radius:6px;cursor:pointer;color:#047857">Cash equivalent</button>
                            </div>
                        </div>
                        <div style="margin-top:10px;text-align:right">
                            <a href="/admin/shop" target="_blank" style="font-size:11.5px;color:#059669;text-decoration:none">Manage Shop ↗</a>
                        </div>
                    </div>
                </div>
                <div class="form-group" id="paymentDetailsGroup" style="display:none">
                    <label style="font-weight:700">Payment Details <span style="color:#94A3B8;font-weight:400;font-size:11px">(shown on PDF — pre-filled from affiliate profile)</span></label>
                    <textarea id="fldPaymentDetails" class="form-control" rows="3"
                        placeholder="e.g. Bank: HSBC&#10;Account: 1234567890&#10;IBAN: GB29NWBK..."
                        oninput="document.getElementById('hidPaymentDetails').value=this.value"
                        style="font-size:12px;font-family:monospace"></textarea>
                </div>
            </div>
        </div>

        <!-- Summary card -->
        <div class="card mb-3" style="background:linear-gradient(135deg,#F0F4FF,#EEF2FF)">
            <div class="card-header" style="background:transparent;border-bottom:1px solid #C7D2FE">
                <span class="card-title" style="color:#4338CA">Invoice Summary</span>
            </div>
            <div class="card-body">
                <table style="width:100%;font-size:14px">
                    <tr>
                        <td style="color:#64748B;padding:6px 0">Total Conversions:</td>
                        <td style="text-align:right;font-weight:600" id="summConv">0</td>
                    </tr>
                    <tr>
                        <td style="color:#64748B;padding:6px 0">Subtotal:</td>
                        <td style="text-align:right;font-weight:600" id="summSubtotal">$0.00</td>
                    </tr>
                    <tr id="summTaxRow" style="display:none">
                        <td style="color:#64748B;padding:6px 0" id="summTaxLabel">Tax (0%):</td>
                        <td style="text-align:right;font-weight:600" id="summTax">$0.00</td>
                    </tr>
                    <tr style="border-top:2px solid #C7D2FE">
                        <td style="padding:10px 0 4px;font-weight:700;font-size:17px;color:#4338CA">Total:</td>
                        <td style="text-align:right;padding:10px 0 4px">
                            <span id="summTotal" style="font-weight:800;font-size:20px;color:#4338CA">$0.00</span>
                            &nbsp;
                            <button type="button" id="btnEditTotal"
                                onclick="toggleEditTotal()"
                                title="Admin: override total amount"
                                style="background:#EDE9FE;border:1px solid #C4B5FD;color:#5B21B6;border-radius:5px;padding:2px 9px;font-size:11px;font-weight:700;cursor:pointer;vertical-align:middle">
                                ✎ Edit
                            </button>
                        </td>
                    </tr>
                    <tr id="totalEditRow" style="display:none">
                        <td colspan="2" style="padding:4px 0 8px">
                            <div style="display:flex;gap:8px;align-items:center">
                                <input type="number" id="fldTotalOverride" step="0.01" min="0"
                                    placeholder="Enter custom total"
                                    style="flex:1;border:2px solid #7C3AED;border-radius:6px;padding:6px 10px;font-size:14px;font-weight:700;color:#5B21B6"
                                    oninput="applyTotalOverride(this.value)">
                                <button type="button" onclick="clearTotalOverride()"
                                    style="background:#F1F5F9;border:1px solid #CBD5E1;color:#64748B;border-radius:5px;padding:6px 10px;font-size:12px;cursor:pointer">
                                    Reset
                                </button>
                            </div>
                            <div style="font-size:11px;color:#7C3AED;margin-top:4px">⚠ Admin override — replaces calculated total on the invoice</div>
                        </td>
                    </tr>
                </table>

                <div style="margin-top:16px;padding:10px 14px;background:rgba(67,56,202,0.08);border-radius:6px;font-size:12px;color:#4338CA">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    PDF will be generated &amp; emailed automatically. For manager invoices, the amount is instantly deducted from their commission balance.
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px;height:44px;font-size:15px" onclick="return prepareSubmit()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-3px;margin-right:6px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Generate Invoice &amp; Send PDF
                </button>
            </div>
        </div>
    </div>
</form>

<style>
/* ── Invoice Create — Responsive Fixes ─────────────────────────────────── */

/* Step 1 setup row: use auto-fit so it wraps gracefully */
#stepSetup .form-row.cols-3 {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

/* Offer table: scrollable on small screens */
.table-scroll-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
#offersTable {
    min-width: 520px; /* prevent columns crushing */
}

/* Bottom 2-col grid: switch to single col below 760px */
@media (max-width: 760px) {
    .inv-bottom-grid {
        grid-template-columns: 1fr !important;
    }
    #stepSetup .form-row.cols-3 {
        grid-template-columns: 1fr;
    }
    #stepSetup .form-row.cols-2 {
        grid-template-columns: 1fr;
    }
}

/* Offer table rows */
.offer-row td { padding: 10px 14px; vertical-align: middle; border-bottom: 1px solid #F1F5F9; }
.desc-input   { width: 100%; border: 1px solid #E2E8F0; border-radius: 5px; padding: 6px 10px; font-size: 13px; font-family: inherit; }
.desc-input:focus { outline: none; border-color: #16A34A; box-shadow: 0 0 0 3px rgba(22,163,74,0.1); }
.qty-input    { text-align: right; width: 100%; min-width: 70px; border: 1px solid #E2E8F0; border-radius: 5px; padding: 5px 8px; font-size: 13px; font-family: inherit; }
.qty-input:focus { outline: none; border-color: #16A34A; box-shadow: 0 0 0 3px rgba(22,163,74,0.1); }
.rate-input   { text-align: right; width: 100%; min-width: 100px; border: 1px solid #E2E8F0; border-radius: 5px; padding: 5px 8px; font-size: 13px; font-family: inherit; }
.rate-input:focus { outline: none; border-color: #16A34A; box-shadow: 0 0 0 3px rgba(22,163,74,0.1); }
.total-input  { font-weight: 700; color: #16A34A; }
.remove-offer { background: none; border: none; color: #CBD5E1; cursor: pointer; padding: 4px; border-radius: 4px; transition: color .15s; }
.remove-offer:hover { color: #EF4444; }

/* Hint bar below table */
.inv-hint-bar {
    padding: 10px 14px;
    font-size: 12px;
    color: #64748B;
    background: #F8FAFC;
    border-top: 1px solid #F1F5F9;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}
</style>

<script>
// ── State ────────────────────────────────────────────────────────────────────
var offerData = [];  // [{offer_id, offer_name, conversions, avg_rate, total_payout}]

function toggleEntityList() {
    var t = document.getElementById('invType').value;
    document.getElementById('affSelectWrap').style.display = t === 'affiliate_payout'  ? '' : 'none';
    document.getElementById('advSelectWrap').style.display = t === 'advertiser_billing' ? '' : 'none';
    document.getElementById('mgrSelectWrap').style.display = t === 'manager_fee'        ? '' : 'none';
    document.getElementById('affBalanceBar').style.display = 'none';
    document.getElementById('mgrBalanceBar').style.display = 'none';
    offerData = [];
    document.getElementById('invoiceForm').style.display = 'none';
}

// ── Load offers via AJAX ─────────────────────────────────────────────────────
function loadOffers() {
    var type  = document.getElementById('invType').value;
    var selEl = type === 'affiliate_payout'  ? document.getElementById('affSelect')
              : type === 'advertiser_billing' ? document.getElementById('advSelect')
              :                                 document.getElementById('mgrSelect');
    var entityId = selEl.value;
    var from     = document.getElementById('periodFrom').value;
    var to       = document.getElementById('periodTo').value;

    if (!entityId) {
        var label = type === 'manager_fee' ? 'a manager' : (type === 'advertiser_billing' ? 'an advertiser' : 'an affiliate');
        showMsg('error', 'Please select ' + label + '.');
        return;
    }
    if (!from || !to) { showMsg('error', 'Please select a period.'); return; }
    if (from > to)    { showMsg('error', 'Period start must be before period end.'); return; }

    // Advertiser billing: manual line items only
    if (type === 'advertiser_billing') {
        showManualForm(entityId, from, to);
        return;
    }

    // Manager fee: invoice is based solely on the manager's available commission balance.
    // We do NOT load affiliate offer/payout data — that belongs to affiliate invoices.
    if (type === 'manager_fee') {
        document.getElementById('affBalanceBar').style.display = 'none';
        showMsg('info', 'Loading commission balance…');
        fetch('/admin/invoices?action=get_manager_info&manager_id=' + entityId)
            .then(function(r) { return r.json(); })
            .then(function(d) {
                document.getElementById('loadMsg').style.display = 'none';
                if (d.error) { showMsg('error', d.error); return; }

                var bal = parseFloat(d.balance) || 0;
                var bar = document.getElementById('mgrBalanceBar');
                var hasBalance = bal > 0;
                var barStyle = hasBalance
                    ? 'background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46'
                    : 'background:#FEF2F2;border:1px solid #FECACA;color:#991B1B';
                bar.innerHTML = '<div style="padding:8px 14px;border-radius:6px;font-size:12px;margin-top:8px;' + barStyle + '">' +
                    (hasBalance ? '💰' : '⚠️') +
                    ' Available Commission Balance: <strong>$' + bal.toFixed(2) + '</strong>' +
                    ' &nbsp;·&nbsp; Manager: <strong>' + escHtml(d.name) + '</strong>' +
                    (!hasBalance ? ' &nbsp;— <em>No available balance. Invoice cannot be generated.</em>' : '') +
                    '</div>';
                bar.style.display = '';

                if (!hasBalance) {
                    showMsg('error', 'This manager has no available commission balance to invoice.');
                    return;
                }

                // Single line item: the manager's full earned commission balance.
                // Admin can reduce the rate field before submitting if a partial invoice is needed.
                offerData = [{
                    offer_id    : 0,
                    offer_name  : 'Affiliate Management Commission',
                    conversions : 1,
                    avg_rate    : bal,
                    total_payout: bal,
                }];
                renderOffers(entityId, from, to);
            })
            .catch(function() { showMsg('error', 'Failed to load manager balance. Please try again.'); });
        return;
    }

    // Affiliate payout: load per-offer conversion data
    showMsg('info', 'Loading offers...');
    // Show affiliate balance info before loading
    fetch('/admin/invoices?action=get_affiliate_info&affiliate_id=' + entityId)
        .then(function(r){ return r.json(); })
        .then(function(d){
            var bar = document.getElementById('affBalanceBar');
            if (d.balance !== undefined) {
                var cls = d.balance >= d.threshold ? 'ok' : 'warn';
                var icon = d.balance >= d.threshold ? '✅' : '⚠️';
                bar.innerHTML = '<div style="padding:8px 14px;border-radius:6px;font-size:12px;margin-top:8px;' +
                    (cls==='ok' ? 'background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46' : 'background:#FFFBEB;border:1px solid #FCD34D;color:#92400E') + '">' +
                    icon + ' Balance: <strong>$' + parseFloat(d.balance).toFixed(2) + '</strong>' +
                    (d.threshold > 0 ? ' &nbsp;·&nbsp; Threshold: <strong>$' + parseFloat(d.threshold).toFixed(2) + '</strong>' : '') +
                    (d.payment_method ? ' &nbsp;·&nbsp; Payment: <strong>' + d.payment_method + '</strong>' : '') +
                    '</div>';
                bar.style.display = '';
                // Store payment details for pre-filling the PDF field (decoded from JSON if needed)
                var _rawPd = d.payment_details || '';
                var _fmtPd = '';
                try {
                    var _pdObj = JSON.parse(_rawPd);
                    if (_pdObj && typeof _pdObj === 'object') {
                        var _labelMap = {
                            account_holder_name:'Account Holder Name', email:'Email / Account ID',
                            bank_name:'Bank Name', account_number:'Account Number',
                            iban_swift:'IBAN / SWIFT', routing_number:'Routing Number',
                            branch_name:'Branch Name', bank_address:'Bank Address',
                            crypto_type:'Cryptocurrency', network_type:'Network',
                            wallet_address:'Wallet Address'
                        };
                        var _lines = [];
                        Object.keys(_pdObj).forEach(function(k) {
                            var v = (_pdObj[k] || '').toString().trim();
                            if (!v) return;
                            var lbl = _labelMap[k] || k.replace(/_/g,' ').replace(/\b\w/g,function(c){return c.toUpperCase();});
                            _lines.push(lbl + ': ' + v);
                        });
                        _fmtPd = _lines.join('\n');
                    } else {
                        _fmtPd = _rawPd;
                    }
                } catch(e) { _fmtPd = _rawPd; }
                window._lastAffPaymentDetails = (d.payment_method ? d.payment_method + ':\n' : '') + _fmtPd;
            } else {
                bar.style.display = 'none';
                window._lastAffPaymentDetails = '';
            }
        }).catch(function(){ document.getElementById('affBalanceBar').style.display='none'; });

    fetch('/admin/invoices?action=load_offers&affiliate_id=' + entityId + '&from=' + from + '&to=' + to)
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.error) { showMsg('error', d.error); return; }
            if (!d.offers || d.offers.length === 0) {
                // No conversions found — still show the invoice form so admin
                // can manually add a custom line item (e.g. bonus, adjustment).
                offerData = [];
                renderOffers(entityId, from, to);
                showMsg('warning', 'No approved conversions found for this period. You can add a custom line item below.');
                return;
            }
            offerData = d.offers.map(function(o) {
                return {
                    offer_id    : o.offer_id,
                    offer_name  : o.offer_name,
                    conversions : parseInt(o.conversions) || 0,
                    avg_rate    : parseFloat(o.avg_rate) || 0,
                    total_payout: parseFloat(o.total_payout) || 0,
                };
            });
            renderOffers(entityId, from, to);
            document.getElementById('loadMsg').style.display = 'none';
        })
        .catch(function() { showMsg('error', 'Failed to load offers. Please try again.'); });
}

function showManualForm(entityId, from, to) {
    document.getElementById('hidType').value     = document.getElementById('invType').value;
    document.getElementById('hidEntityId').value = entityId;
    document.getElementById('hidFrom').value     = from;
    document.getElementById('hidTo').value       = to;
    offerData = [{ offer_id: 0, offer_name: 'Service Fee', conversions: 1, avg_rate: 0, total_payout: 0 }];
    renderOffers(entityId, from, to);
    document.getElementById('loadMsg').style.display = 'none';
}

function renderOffers(entityId, from, to) {
    document.getElementById('hidType').value     = document.getElementById('invType').value;
    document.getElementById('hidEntityId').value = entityId;
    document.getElementById('hidFrom').value     = from;
    document.getElementById('hidTo').value       = to;

    var fl = new Date(from), tl = new Date(to);
    var fmt = function(d) {
        return d.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
    };
    document.getElementById('periodLabel').textContent = fmt(fl) + ' – ' + fmt(tl);

    buildOfferRows();
    recalcTotals();

    // The four-column layout (Description / Qty / Unit Price / Total) is now used for
    // every invoice type. Headers stay the same regardless of mode.

    document.getElementById('invoiceForm').style.display = '';

    // Collapse Step 1 into a compact summary bar so it doesn't overlap
    var setup = document.getElementById('stepSetup');
    var type  = document.getElementById('invType').value;
    var selEl = type === 'affiliate_payout'  ? document.getElementById('affSelect')
              : type === 'advertiser_billing' ? document.getElementById('advSelect')
              :                                 document.getElementById('mgrSelect');
    var entityName = selEl.options[selEl.selectedIndex] ? selEl.options[selEl.selectedIndex].text : '';
    var fl = new Date(from + 'T00:00:00'), tl = new Date(to + 'T00:00:00');
    var fmt = function(d) { return d.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }); };
    setup.innerHTML =
        '<div style="padding:12px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">' +
        '<div style="display:flex;align-items:center;gap:10px">' +
        '<span style="background:#EDE9FE;color:#5B21B6;border-radius:50%;width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;flex-shrink:0">1</span>' +
        '<span style="font-size:13px;color:#475569"><strong>' + escHtml(entityName) + '</strong> &nbsp;·&nbsp; ' + fmt(fl) + ' – ' + fmt(tl) + '</span>' +
        '</div>' +
        '<button type="button" onclick="resetSetup()" style="background:none;border:1px solid #CBD5E1;color:#64748B;border-radius:6px;padding:4px 12px;font-size:12px;cursor:pointer">✎ Change</button>' +
        '</div>';
    setup.style.borderRadius = '8px';

    document.getElementById('invoiceForm').scrollIntoView({ behavior:'smooth', block:'start' });

    // Show payment details field (pre-filled from affiliate info if available)
    var pdGroup = document.getElementById('paymentDetailsGroup');
    if (pdGroup) pdGroup.style.display = '';
    // Pre-fill from last fetched affiliate info
    if (window._lastAffPaymentDetails !== undefined) {
        var pdFld = document.getElementById('fldPaymentDetails');
        if (pdFld && !pdFld.value) {
            pdFld.value = window._lastAffPaymentDetails;
            document.getElementById('hidPaymentDetails').value = window._lastAffPaymentDetails;
        }
    }
}

function resetSetup() {
    location.reload();
}

function buildOfferRows() {
    var tbody = document.getElementById('offerRows');
    tbody.innerHTML = '';
    offerData.forEach(function(o, idx) {
        var tr = document.createElement('tr');
        tr.className = 'offer-row';
        tr.dataset.idx = idx;
        // Description (editable text), Qty (editable number), Unit Price (editable number),
        // Total (computed read-only display = qty × unit price), Remove button.
        tr.innerHTML =
            '<td><input type="text" class="desc-input" value="' + escAttr(o.offer_name) + '" data-idx="' + idx + '" oninput="onDescChange(this)" placeholder="Offer or service description"></td>' +
            '<td style="text-align:right"><input type="number" class="qty-input" value="' + (o.conversions || 0) + '" min="0" step="1" data-idx="' + idx + '" oninput="onQtyChange(this)" title="Qty"></td>' +
            '<td style="text-align:right"><input type="number" class="rate-input" value="' + (o.avg_rate || 0).toFixed(4) + '" min="0" step="0.0001" data-idx="' + idx + '" oninput="onRateChange(this)" title="Unit Price"></td>' +
            '<td style="text-align:right"><input type="number" class="rate-input total-input" value="' + (o.total_payout || 0).toFixed(4) + '" min="0" step="0.0001" data-idx="' + idx + '" oninput="onTotalChange(this)" title="Total — editing this overrides qty × unit price" id="rowTotal_' + idx + '"></td>' +
            '<td><button type="button" class="remove-offer" onclick="removeOffer(' + idx + ')" title="Remove">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button></td>';
        tbody.appendChild(tr);
    });
}

function onDescChange(input) {
    var idx = parseInt(input.dataset.idx);
    offerData[idx].offer_name = input.value;
}

function onQtyChange(input) {
    var idx = parseInt(input.dataset.idx);
    var qty = parseFloat(input.value) || 0;
    offerData[idx].conversions  = qty;
    offerData[idx].total_payout = qty * (offerData[idx].avg_rate || 0);
    var totalCell = document.getElementById('rowTotal_' + idx);
    if (totalCell) totalCell.value = offerData[idx].total_payout.toFixed(4);
    recalcTotals();
}

function onRateChange(input) {
    var idx  = parseInt(input.dataset.idx);
    var rate = parseFloat(input.value) || 0;
    offerData[idx].avg_rate     = rate;
    offerData[idx].total_payout = rate * (offerData[idx].conversions || 0);
    var totalCell = document.getElementById('rowTotal_' + idx);
    if (totalCell) totalCell.value = offerData[idx].total_payout.toFixed(4);
    recalcTotals();
}

// Direct edit of the Total column. Overrides qty × unit price for that row only.
// Back-fills avg_rate so the prepareSubmit() payload still encodes a sensible rate.
function onTotalChange(input) {
    var idx   = parseInt(input.dataset.idx);
    var total = parseFloat(input.value) || 0;
    offerData[idx].total_payout = total;
    var qty = offerData[idx].conversions || 0;
    if (qty > 0) offerData[idx].avg_rate = total / qty;
    recalcTotals();
}

function escAttr(s) {
    return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function removeOffer(idx) {
    offerData.splice(idx, 1);
    buildOfferRows();
    recalcTotals();
    if (offerData.length === 0) {
        showMsg('warning', 'All offers removed. Load offers again to start over.');
        document.getElementById('invoiceForm').style.display = 'none';
    }
}

var _totalOverride = null; // null = use calculated, number = admin override

function recalcTotals() {
    var totalConv = 0, subtotal = 0;
    offerData.forEach(function(o) {
        totalConv += o.conversions;
        subtotal  += o.total_payout;
    });
    var taxRate = parseFloat(document.getElementById('fldTaxRate').value) || 0;
    var tax     = subtotal * taxRate / 100;
    var calcTotal = subtotal + tax;
    var displayTotal = (_totalOverride !== null) ? _totalOverride : calcTotal;

    document.getElementById('totalConversions').textContent = totalConv.toLocaleString();
    document.getElementById('totalPayout').textContent      = '$' + subtotal.toFixed(2);
    document.getElementById('summConv').textContent         = totalConv.toLocaleString();
    document.getElementById('summSubtotal').textContent     = '$' + subtotal.toFixed(2);
    document.getElementById('summTotal').textContent        = '$' + displayTotal.toFixed(2);
    document.getElementById('summTotal').style.color        = (_totalOverride !== null) ? '#7C3AED' : '#4338CA';
    document.getElementById('hidTotalOverride').value       = (_totalOverride !== null) ? _totalOverride.toFixed(2) : '';

    if (taxRate > 0) {
        document.getElementById('summTaxRow').style.display  = '';
        document.getElementById('summTaxLabel').textContent  = 'Tax (' + taxRate + '%):';
        document.getElementById('summTax').textContent       = '$' + tax.toFixed(2);
    } else {
        document.getElementById('summTaxRow').style.display  = 'none';
    }
}

// ── Admin: edit total amount ─────────────────────────────────────────────────
function toggleEditTotal() {
    var row = document.getElementById('totalEditRow');
    row.style.display = (row.style.display === 'none') ? '' : 'none';
    if (row.style.display !== 'none') {
        var cur = document.getElementById('summTotal').textContent.replace('$','');
        document.getElementById('fldTotalOverride').value = cur;
        document.getElementById('fldTotalOverride').focus();
    }
}
function applyTotalOverride(val) {
    var v = parseFloat(val);
    _totalOverride = (!isNaN(v) && v >= 0) ? v : null;
    recalcTotals();
}
function clearTotalOverride() {
    _totalOverride = null;
    document.getElementById('fldTotalOverride').value = '';
    document.getElementById('totalEditRow').style.display = 'none';
    recalcTotals();
}

// ── Build hidden inputs before submit ────────────────────────────────────────
function prepareSubmit() {
    if (offerData.length === 0) {
        alert('No line items to invoice. Click "+ Add Line Item" to add a row.');
        return false;
    }
    // Validate every line has a description and a positive amount.
    for (var i = 0; i < offerData.length; i++) {
        var o = offerData[i];
        if (!String(o.offer_name || '').trim()) {
            alert('Row ' + (i + 1) + ': please enter an Offer / Description.');
            return false;
        }
        if (!(parseFloat(o.total_payout) > 0)) {
            alert('Row ' + (i + 1) + ': total must be greater than zero.');
            return false;
        }
    }

    var container = document.getElementById('hiddenItems');
    container.innerHTML = '';
    offerData.forEach(function(o) {
        // The admin-edited description is used verbatim — no extra "(N conv. @ ...)" suffix
        // is appended, so editing the description in the form is preserved on the invoice PDF.
        var qty   = parseFloat(o.conversions)  || 0;
        var rate  = parseFloat(o.avg_rate)     || 0;
        var total = parseFloat(o.total_payout) || 0;
        // If the admin edited Total directly without changing qty/rate, recompute rate
        // so backend's qty × rate = total.
        if (qty > 0 && Math.abs(qty * rate - total) > 0.0001) {
            rate = total / qty;
        }
        addHidden(container, 'item_desc[]',  String(o.offer_name).trim());
        addHidden(container, 'item_qty[]',   qty);
        addHidden(container, 'item_rate[]',  rate.toFixed(4));
    });
    // Sync payment details override
    var pd = document.getElementById('fldPaymentDetails');
    if (pd) document.getElementById('hidPaymentDetails').value = pd.value;
    return true;
}

function addHidden(parent, name, value) {
    var i = document.createElement('input');
    i.type  = 'hidden';
    i.name  = name;
    i.value = value;
    parent.appendChild(i);
}

// ── Utilities ────────────────────────────────────────────────────────────────
function showMsg(type, msg) {
    var colors = {
        error:   { bg:'#FEF2F2', border:'#FCA5A5', color:'#B91C1C' },
        warning: { bg:'#FFFBEB', border:'#FCD34D', color:'#92400E' },
        info:    { bg:'#EFF6FF', border:'#93C5FD', color:'#1D4ED8' },
    };
    var c = colors[type] || colors.info;
    var el = document.getElementById('loadMsg');
    el.style.cssText = 'padding:10px 14px;border-radius:6px;font-size:13px;background:' + c.bg + ';border:1px solid ' + c.border + ';color:' + c.color;
    el.textContent = msg;
    el.style.display = '';
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Add custom manual line item ───────────────────────────────────────────
// Append a blank, editable line item directly. Inline editing happens in the
// new editable inputs (description / qty / unit price / total).
function addLineItem() {
    offerData.push({ offer_id: 0, offer_name: '', conversions: 1, avg_rate: 0, total_payout: 0 });
    buildOfferRows();
    recalcTotals();
    // Focus the new description field so admins can start typing immediately.
    var rows = document.querySelectorAll('#offerRows .offer-row');
    var last = rows[rows.length - 1];
    if (last) {
        var d = last.querySelector('.desc-input');
        if (d) d.focus();
    }
    // Make sure the form is visible even if no offers were loaded yet.
    var f = document.getElementById('invoiceForm');
    if (f && f.style.display === 'none') f.style.display = '';
}

// Backwards-compat alias: keep old name working in case other places call it.
function addCustomLine() { addLineItem(); }

// ── Rewards & Shop helper line-item inserters ─────────────────────────────
function addRewardLineItem() {
    offerData.push({ offer_id: 0, offer_name: 'Milestone Reward Bonus', conversions: 1, avg_rate: 0, total_payout: 0 });
    buildOfferRows(); recalcTotals();
    var f = document.getElementById('invoiceForm');
    if (f && f.style.display === 'none') f.style.display = '';
    // Focus the newly added description field
    var rows = document.querySelectorAll('#offerRows .offer-row');
    var last = rows[rows.length - 1];
    if (last) { var d = last.querySelector('.desc-input'); if (d) { d.focus(); d.select(); } }
}
function addLoyaltyLineItem() {
    offerData.push({ offer_id: 0, offer_name: 'Loyalty / VIP Tier Bonus', conversions: 1, avg_rate: 0, total_payout: 0 });
    buildOfferRows(); recalcTotals();
    var f = document.getElementById('invoiceForm');
    if (f && f.style.display === 'none') f.style.display = '';
    var rows = document.querySelectorAll('#offerRows .offer-row');
    var last = rows[rows.length - 1];
    if (last) { var d = last.querySelector('.desc-input'); if (d) { d.focus(); d.select(); } }
}
function addShopRedemptionLine() {
    offerData.push({ offer_id: 0, offer_name: 'Shop Product Redemption Credit', conversions: 1, avg_rate: 0, total_payout: 0 });
    buildOfferRows(); recalcTotals();
    var f = document.getElementById('invoiceForm');
    if (f && f.style.display === 'none') f.style.display = '';
    var rows = document.querySelectorAll('#offerRows .offer-row');
    var last = rows[rows.length - 1];
    if (last) { var d = last.querySelector('.desc-input'); if (d) { d.focus(); d.select(); } }
}
function addPointsCreditLine() {
    offerData.push({ offer_id: 0, offer_name: 'Points Credit / Top-up', conversions: 1, avg_rate: 0, total_payout: 0 });
    buildOfferRows(); recalcTotals();
    var f = document.getElementById('invoiceForm');
    if (f && f.style.display === 'none') f.style.display = '';
    var rows = document.querySelectorAll('#offerRows .offer-row');
    var last = rows[rows.length - 1];
    if (last) { var d = last.querySelector('.desc-input'); if (d) { d.focus(); d.select(); } }
}

// ── Collapsible section toggle ────────────────────────────────────────────
function toggleSection(bodyId, chevId) {
    var body = document.getElementById(bodyId);
    var chev = document.getElementById(chevId);
    if (!body) return;
    var collapsed = body.style.display === 'none';
    body.style.display = collapsed ? '' : 'none';
    if (chev) chev.style.transform = collapsed ? '' : 'rotate(-90deg)';
}

// ── Append text to Quill notes editor ────────────────────────────────────
function appendNote(text) {
    if (window._notesQuill) {
        var len = window._notesQuill.getLength();
        if (len > 1) { window._notesQuill.insertText(len - 1, '\n'); len++; }
        window._notesQuill.insertText(len - 1, text);
        window._notesQuill.setSelection(window._notesQuill.getLength());
    }
}
</script>

<!-- Quill rich-text editor for Description / Notes -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<style>
#quill-notes-wrap .ql-toolbar { border:none; border-bottom:1px solid #E2E8F0; background:#F8FAFC; padding:6px 10px; }
#quill-notes-wrap .ql-container { border:none; font-size:13px; font-family:inherit; }
#quill-notes-wrap .ql-editor { min-height:90px; padding:8px 12px; color:#0F172A; }
#quill-notes-wrap .ql-editor.ql-blank::before { color:#94A3B8; font-style:normal; }
#quill-notes-wrap:focus-within { border-color:#16A34A !important; box-shadow:0 0 0 3px rgba(22,163,74,.1); }
</style>
<script>
(function(){
    var quill = new Quill('#quill-notes-editor', {
        theme: 'snow',
        placeholder: 'Payment terms, bank details, milestone notes, shop redemption details…',
        modules: {
            toolbar: [
                ['bold','italic','underline'],
                [{'list':'ordered'},{'list':'bullet'}],
                ['link','clean']
            ]
        }
    });
    window._notesQuill = quill;
    // Sync to hidden textarea on every change
    quill.on('text-change', function(){
        var html = quill.root.innerHTML;
        if (html === '<p><br></p>') html = '';
        document.getElementById('notesHidden').value = html;
    });
    // Also sync on form submit as a safety net
    var form = document.getElementById('invoiceForm');
    if (form) {
        form.addEventListener('submit', function(){
            var html = quill.root.innerHTML;
            if (html === '<p><br></p>') html = '';
            document.getElementById('notesHidden').value = html;
        }, true);
    }
})();
</script>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
