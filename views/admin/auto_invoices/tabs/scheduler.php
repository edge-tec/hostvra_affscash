<?php
/**
 * Tab 1: Global Invoice Scheduler Settings
 */
$timezones = timezone_identifiers_list();
$cronToken = trim((string)Config::get('config', 'fraud_reports.cron_token')) ?: 'affscash_auto_inv_secret';
$cronUrl   = rtrim(Config::get('config', 'app.url') ?? 'https://affscash.net', '/') . '/cron/auto-invoices?token=' . $cronToken;

$freq = $schedule['frequency'] ?? 'monthly';
$periodType = $schedule['period_type'] ?? 'prev_month';
$customStartDay = (int)($schedule['custom_start_day'] ?? 1);
$customEndDay = (int)($schedule['custom_end_day'] ?? 31);
$customPeriodStart = $schedule['custom_period_start'] ?? '';
$customPeriodEnd = $schedule['custom_period_end'] ?? '';
?>

<div class="card">
    <div class="card-header" style="background:linear-gradient(135deg,#312e81,#4338ca);border-radius:12px 12px 0 0;padding:18px 24px;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h3 style="color:#ffffff;margin:0;font-size:16px;font-weight:700;">Global Billing &amp; Invoice Scheduler Configuration</h3>
            <p style="color:#c7d2fe;margin:4px 0 0 0;font-size:12.5px;">Configure automatic billing generation frequency, specific dates, custom calculation periods, and invoice parameters.</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="color:#e0e7ff;font-size:13px;font-weight:600;">Status:</span>
            <label class="aig-switch">
                <input type="checkbox" name="enabled" form="formGlobalSchedule" value="1" <?= !empty($schedule['enabled']) ? 'checked' : '' ?>>
                <span class="aig-slider"></span>
            </label>
        </div>
    </div>

    <div class="card-body" style="padding:28px;">
        <form id="formGlobalSchedule" method="POST" action="/admin/auto-invoices?tab=scheduler">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="save_schedule">

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(340px, 1fr));gap:24px;">
                <!-- Frequency & Timing -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px;">
                    <h4 style="margin:0 0 16px 0;font-size:14px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        Schedule Frequency &amp; Timing
                    </h4>

                    <!-- Billing Frequency -->
                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Billing Frequency</label>
                        <select name="frequency" id="globalFrequency" class="form-control" onchange="updateSchedulerUI()">
                            <option value="monthly" <?= $freq === 'monthly' ? 'selected' : '' ?>>Monthly (Specific day of each month)</option>
                            <option value="every_x_days" <?= $freq === 'every_x_days' ? 'selected' : '' ?>>Every X Days (e.g. Every 7, 15, 30 Days)</option>
                            <option value="weekly" <?= $freq === 'weekly' ? 'selected' : '' ?>>Weekly (Every Monday)</option>
                            <option value="custom" <?= $freq === 'custom' ? 'selected' : '' ?>>Custom Date Range / Rolling</option>
                        </select>
                    </div>

                    <!-- Generate Date (Day of Month) -->
                    <div class="form-group mb-3" id="wrapMonthlyDay" style="<?= $freq !== 'monthly' ? 'display:none;' : '' ?>">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Generate Execution Date (Day of Month)</label>
                        <select name="monthly_day" id="globalMonthlyDay" class="form-control" onchange="updateSchedulerUI()">
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= $d ?>" <?= (int)($schedule['monthly_day'] ?? 1) === $d ? 'selected' : '' ?>>
                                <?= $d ?><?= ($d==1?'st':($d==2?'nd':($d==3?'rd':'th'))) ?> of every month
                            </option>
                            <?php endfor; ?>
                        </select>
                        <small class="text-muted">Cron will automatically execute and generate invoices on this specific day each month.</small>
                    </div>

                    <!-- Generate Every X Days -->
                    <div class="form-group mb-3" id="wrapIntervalDays" style="<?= $freq !== 'every_x_days' ? 'display:none;' : '' ?>">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Generate Every X Days</label>
                        <select name="interval_days" id="globalIntervalDays" class="form-control" onchange="updateSchedulerUI()">
                            <option value="7" <?= (int)($schedule['interval_days'] ?? 15) === 7 ? 'selected' : '' ?>>Every 7 Days (Weekly cycle)</option>
                            <option value="14" <?= (int)($schedule['interval_days'] ?? 15) === 14 ? 'selected' : '' ?>>Every 14 Days (Bi-weekly: Every 2 Weeks)</option>
                            <option value="15" <?= (int)($schedule['interval_days'] ?? 15) === 15 ? 'selected' : '' ?>>Every 15 Days (Bi-monthly: 1st-15th &amp; 16th-End)</option>
                            <option value="30" <?= (int)($schedule['interval_days'] ?? 15) === 30 ? 'selected' : '' ?>>Every 30 Days (Monthly cycle)</option>
                            <option value="45" <?= (int)($schedule['interval_days'] ?? 15) === 45 ? 'selected' : '' ?>>Every 45 Days</option>
                            <option value="60" <?= (int)($schedule['interval_days'] ?? 15) === 60 ? 'selected' : '' ?>>Every 60 Days (2 Months)</option>
                        </select>
                    </div>

                    <!-- Billing Calculation Period (Which conversions to invoice) -->
                    <div class="form-group mb-3" style="background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;padding:14px;">
                        <label style="font-weight:700;font-size:13px;color:#1e293b;margin-bottom:6px;display:block;">
                            <span style="color:#4f46e5;">&#128197;</span> Invoiced Conversion Period (Calculation Range)
                        </label>
                        <select name="period_type" id="globalPeriodType" class="form-control" onchange="updateSchedulerUI()">
                            <option value="all_unbilled" <?= $periodType === 'all_unbilled' ? 'selected' : '' ?>>All Unbilled Lifetime Conversions (All Time Up to Today)</option>
                            <option value="prev_month" <?= $periodType === 'prev_month' ? 'selected' : '' ?>>Previous Full Month (1st to Last Day of Preceding Month)</option>
                            <option value="current_month" <?= $periodType === 'current_month' ? 'selected' : '' ?>>Current Month to Date (1st of this month to Execution Date)</option>
                            <option value="bi_monthly_1_15" <?= $periodType === 'bi_monthly_1_15' ? 'selected' : '' ?>>1st to 15th of the Month (First Half)</option>
                            <option value="bi_monthly_16_end" <?= $periodType === 'bi_monthly_16_end' ? 'selected' : '' ?>>16th to End of the Month (Second Half)</option>
                            <option value="bi_weekly_14" <?= $periodType === 'bi_weekly_14' ? 'selected' : '' ?>>Every 14 Days Cycle (1-14 / 15-30 / 31-14 Next Month)</option>
                            <option value="last_14_days" <?= $periodType === 'last_14_days' ? 'selected' : '' ?>>Last 14 Days (Rolling 2 Weeks)</option>
                            <option value="custom_days" <?= $periodType === 'custom_days' ? 'selected' : '' ?>>Specific Day Range Every Month (Custom Days X to Y)</option>
                            <option value="custom_dates" <?= $periodType === 'custom_dates' ? 'selected' : '' ?>>Fixed Custom Date Range (Exact Start &amp; End Dates)</option>
                            <option value="rolling_days" <?= $periodType === 'rolling_days' ? 'selected' : '' ?>>Rolling Last X Days</option>
                        </select>
                        <small class="text-muted" style="display:block;margin-top:4px;">Defines the date range of unpaid approved conversions to include in each generated invoice.</small>

                        <!-- Custom Days of Month Range (X to Y) -->
                        <div id="wrapCustomDays" style="display:<?= $periodType === 'custom_days' ? 'grid' : 'none' ?>;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px;padding-top:12px;border-top:1px dashed #e2e8f0;">
                            <div>
                                <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Period Start Day</label>
                                <select name="custom_start_day" id="globalCustomStartDay" class="form-control form-control-sm" onchange="updateSchedulerUI()">
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?= $d ?>" <?= $customStartDay === $d ? 'selected' : '' ?>>Day <?= $d ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Period End Day</label>
                                <select name="custom_end_day" id="globalCustomEndDay" class="form-control form-control-sm" onchange="updateSchedulerUI()">
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?= $d ?>" <?= $customEndDay === $d ? 'selected' : '' ?>>Day <?= $d ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Fixed Custom Date Range Pickers -->
                        <div id="wrapCustomDates" style="display:<?= $periodType === 'custom_dates' ? 'grid' : 'none' ?>;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px;padding-top:12px;border-top:1px dashed #e2e8f0;">
                            <div>
                                <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Start Date</label>
                                <input type="date" name="custom_period_start" id="globalCustomPeriodStart" class="form-control form-control-sm" value="<?= htmlspecialchars($customPeriodStart) ?>" onchange="updateSchedulerUI()">
                            </div>
                            <div>
                                <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">End Date</label>
                                <input type="date" name="custom_period_end" id="globalCustomPeriodEnd" class="form-control form-control-sm" value="<?= htmlspecialchars($customPeriodEnd) ?>" onchange="updateSchedulerUI()">
                            </div>
                        </div>
                    </div>

                    <!-- Execution Time & Timezone -->
                    <div class="grid-2" style="gap:12px;">
                        <div class="form-group mb-3">
                            <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Invoice Time</label>
                            <input type="time" name="invoice_time" id="globalInvoiceTime" class="form-control" value="<?= htmlspecialchars(substr($schedule['invoice_time'] ?? '00:00:00', 0, 5)) ?>" onchange="updateSchedulerUI()">
                        </div>
                        <div class="form-group mb-3">
                            <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Timezone</label>
                            <select name="timezone" id="globalTimezone" class="form-control" onchange="updateSchedulerUI()">
                                <option value="UTC" <?= ($schedule['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : '' ?>>UTC (Default)</option>
                                <?php foreach (['Asia/Dhaka', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'Europe/Berlin', 'Asia/Dubai', 'Asia/Singapore', 'Asia/Tokyo'] as $tz): ?>
                                <option value="<?= $tz ?>" <?= ($schedule['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Live Schedule Summary Box -->
                    <div id="liveSchedulePreview" style="background:#eef2ff;border:1px solid #c7d2fe;border-radius:8px;padding:12px 14px;margin-top:10px;">
                        <div style="font-size:12px;font-weight:700;color:#3730a3;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Live Schedule &amp; Period Preview:
                        </div>
                        <div style="font-size:12px;color:#1e293b;line-height:1.6;">
                            <div>&bull; <strong>Next Run:</strong> <span id="previewNextRun" style="color:#4f46e5;">Calculating...</span></div>
                            <div>&bull; <strong>Conversions Period:</strong> <span id="previewPeriodRange" style="color:#059669;">Calculating...</span></div>
                        </div>
                    </div>
                </div>

                <!-- Numbering, PDF & Delivery -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px;">
                    <h4 style="margin:0 0 16px 0;font-size:14px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        Invoice Numbering &amp; Automation
                    </h4>

                    <div class="grid-2" style="gap:12px;">
                        <div class="form-group mb-3">
                            <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Invoice Prefix</label>
                            <input type="text" name="invoice_prefix" class="form-control" value="<?= htmlspecialchars($schedule['invoice_prefix'] ?? 'AFFSCASH-INV-') ?>" placeholder="AFFSCASH-INV-">
                        </div>
                        <div class="form-group mb-3">
                            <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Starting Sequence #</label>
                            <input type="number" name="starting_number" class="form-control" value="<?= (int)($schedule['starting_number'] ?? 100001) ?>" min="1">
                        </div>
                    </div>

                    <div class="grid-2" style="gap:12px;">
                        <div class="form-group mb-3">
                            <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Min Payable Amount ($)</label>
                            <input type="number" step="0.01" name="min_payout_threshold" class="form-control" value="<?= (float)($schedule['min_payout_threshold'] ?? 50.00) ?>" min="0">
                        </div>
                        <div class="form-group mb-3">
                            <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Default Payment Terms</label>
                            <select name="payment_terms" class="form-control">
                                <option value="net14" <?= ($schedule['payment_terms'] ?? '') === 'net14' ? 'selected' : '' ?>>Every 14 Days (Due in +2 Days)</option>
                                <option value="net15" <?= ($schedule['payment_terms'] ?? '') === 'net15' ? 'selected' : '' ?>>Net 15 (+15 Days)</option>
                                <option value="net30" <?= ($schedule['payment_terms'] ?? '') === 'net30' ? 'selected' : '' ?>>Net 30 (+30 Days)</option>
                                <option value="net7" <?= ($schedule['payment_terms'] ?? '') === 'net7' ? 'selected' : '' ?>>Net 7 (+7 Days)</option>
                                <option value="net45" <?= ($schedule['payment_terms'] ?? '') === 'net45' ? 'selected' : '' ?>>Net 45 (+45 Days)</option>
                                <option value="net60" <?= ($schedule['payment_terms'] ?? '') === 'net60' ? 'selected' : '' ?>>Net 60 (+60 Days)</option>
                                <option value="weekly" <?= ($schedule['payment_terms'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly (+2 Days)</option>
                                <option value="biweekly" <?= ($schedule['payment_terms'] ?? '') === 'biweekly' ? 'selected' : '' ?>>Bi-weekly (+2 Days)</option>
                                <option value="immediate" <?= ($schedule['payment_terms'] ?? '') === 'immediate' ? 'selected' : '' ?>>Immediate (Upon Generation)</option>
                            </select>
                        </div>
                    </div>

                    <div style="border-top:1px solid #e2e8f0;padding-top:16px;margin-top:8px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                            <div>
                                <div style="font-weight:600;font-size:13px;color:#1e293b;">Auto Create PDF Invoices</div>
                                <div style="font-size:12px;color:#64748b;">Generate and store high-resolution A4 PDF copies on disk</div>
                            </div>
                            <input type="checkbox" name="auto_pdf" value="1" <?= !empty($schedule['auto_pdf']) ? 'checked' : '' ?> style="width:18px;height:18px;">
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-weight:600;font-size:13px;color:#1e293b;">Auto Send Email to Affiliate</div>
                                <div style="font-size:12px;color:#64748b;">Dispatch automatic email with invoice summary and link</div>
                            </div>
                            <input type="checkbox" name="auto_email" value="1" <?= !empty($schedule['auto_email']) ? 'checked' : '' ?> style="width:18px;height:18px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Execution Cron Info -->
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:18px 22px;margin-top:24px;">
                <h4 style="margin:0 0 8px 0;font-size:13.5px;font-weight:700;color:#1e40af;display:flex;align-items:center;gap:6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Automatic Cron Job Setup (aaPanel / System Crontab)
                </h4>
                <p style="font-size:12.5px;color:#1e3a8a;margin:0 0 10px 0;">
                    To enable fully hands-off automatic invoice generation, set up a cron job to execute hourly on your server:
                </p>
                <div style="background:#1e293b;color:#f8fafc;padding:10px 14px;border-radius:6px;font-family:monospace;font-size:12px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;">
                    <span>0 * * * * php <?= BASE_PATH ?>/cron/auto_invoices.php > /dev/null 2>&1</span>
                </div>
                <div style="font-size:12px;color:#475569;">
                    Or as a Web Cron URL in aaPanel: <code style="background:#dbeafe;color:#1e40af;padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($cronUrl) ?></code>
                </div>
            </div>

            <div style="margin-top:24px;display:flex;justify-content:flex-end;">
                <button type="submit" class="btn btn-primary" style="padding:10px 28px;font-weight:700;font-size:14px;">
                    Save Scheduler Configuration
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateSchedulerUI() {
    const freq = document.getElementById('globalFrequency').value;
    const periodType = document.getElementById('globalPeriodType').value;
    const monthlyDay = parseInt(document.getElementById('globalMonthlyDay').value || '1');
    const timeVal = document.getElementById('globalInvoiceTime').value || '00:00';
    const tz = document.getElementById('globalTimezone').value || 'UTC';

    // Toggle fields
    document.getElementById('wrapMonthlyDay').style.display = (freq === 'monthly') ? 'block' : 'none';
    document.getElementById('wrapIntervalDays').style.display = (freq === 'every_x_days') ? 'block' : 'none';
    document.getElementById('wrapCustomDays').style.display = (periodType === 'custom_days') ? 'grid' : 'none';
    document.getElementById('wrapCustomDates').style.display = (periodType === 'custom_dates') ? 'grid' : 'none';

    // Calculate preview Next Run
    let nextRunText = '';
    if (freq === 'monthly') {
        nextRunText = `Day ${monthlyDay} of every month at ${timeVal} (${tz})`;
    } else if (freq === 'every_x_days') {
        const days = document.getElementById('globalIntervalDays').value || '15';
        nextRunText = `Every ${days} Days at ${timeVal} (${tz})`;
    } else if (freq === 'weekly') {
        nextRunText = `Every Monday at ${timeVal} (${tz})`;
    } else {
        nextRunText = `Custom Schedule at ${timeVal} (${tz})`;
    }
    document.getElementById('previewNextRun').textContent = nextRunText;

    // Calculate preview Period
    let periodText = '';
    if (periodType === 'prev_month') {
        periodText = 'Previous Month (1st to Last Day of last month)';
    } else if (periodType === 'current_month') {
        periodText = 'Current Month (1st of this month to run date)';
    } else if (periodType === 'bi_monthly_1_15') {
        periodText = '1st to 15th of the month';
    } else if (periodType === 'bi_monthly_16_end') {
        periodText = '16th to End of the month';
    } else if (periodType === 'bi_weekly_14') {
        periodText = '14-Day Cycle (1-14 / 15-30 / 31-14 next month, 31st carried forward)';
    } else if (periodType === 'custom_days') {
        const sDay = document.getElementById('globalCustomStartDay').value || '1';
        const eDay = document.getElementById('globalCustomEndDay').value || '31';
        periodText = `Day ${sDay} to Day ${eDay} of every month`;
    } else if (periodType === 'custom_dates') {
        const sDate = document.getElementById('globalCustomPeriodStart').value || 'YYYY-MM-DD';
        const eDate = document.getElementById('globalCustomPeriodEnd').value || 'YYYY-MM-DD';
        periodText = `${sDate} to ${eDate}`;
    } else if (periodType === 'rolling_days') {
        periodText = 'Rolling conversions up to invoice generation date';
    }
    document.getElementById('previewPeriodRange').textContent = periodText;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', updateSchedulerUI);
</script>
