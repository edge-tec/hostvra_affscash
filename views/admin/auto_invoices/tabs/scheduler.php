<?php
/**
 * Tab 1: Global Invoice Scheduler Settings
 */
$timezones = timezone_identifiers_list();
$cronToken = trim((string)Config::get('config', 'fraud_reports.cron_token')) ?: 'affscash_auto_inv_secret';
$cronUrl   = rtrim(Config::get('config', 'app.url') ?? 'https://affscash.net', '/') . '/cron/auto-invoices?token=' . $cronToken;
?>

<div class="card">
    <div class="card-header" style="background:linear-gradient(135deg,#312e81,#4338ca);border-radius:12px 12px 0 0;padding:18px 24px;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h3 style="color:#ffffff;margin:0;font-size:16px;font-weight:700;">Global Billing &amp; Invoice Scheduler Configuration</h3>
            <p style="color:#c7d2fe;margin:4px 0 0 0;font-size:12.5px;">These settings apply to all affiliates unless an affiliate-specific or offer-specific billing rule is defined.</p>
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

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:24px;">
                <!-- Frequency & Timing -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px;">
                    <h4 style="margin:0 0 16px 0;font-size:14px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        Schedule Frequency &amp; Timing
                    </h4>

                    <div class="form-group mb-3">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Billing Frequency</label>
                        <select name="frequency" id="globalFrequency" class="form-control" onchange="toggleFrequencyFields(this.value)">
                            <option value="monthly" <?= ($schedule['frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly (Specific day of each month)</option>
                            <option value="every_x_days" <?= ($schedule['frequency'] ?? '') === 'every_x_days' ? 'selected' : '' ?>>Every X Days (e.g. Every 7, 15, 30 Days)</option>
                            <option value="weekly" <?= ($schedule['frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly (Every Monday)</option>
                            <option value="custom" <?= ($schedule['frequency'] ?? '') === 'custom' ? 'selected' : '' ?>>Custom Date Range / Rolling</option>
                        </select>
                    </div>

                    <div class="form-group mb-3" id="wrapMonthlyDay" style="<?= ($schedule['frequency'] ?? '') !== 'monthly' ? 'display:none;' : '' ?>">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Generate Date (Day of Month)</label>
                        <select name="monthly_day" class="form-control">
                            <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= $d ?>" <?= (int)($schedule['monthly_day'] ?? 1) === $d ? 'selected' : '' ?>><?= $d ?><?= ($d==1?'st':($d==2?'nd':($d==3?'rd':'th'))) ?> of every month</option>
                            <?php endfor; ?>
                        </select>
                        <small class="text-muted">Invoices will be calculated for the preceding month/cycle on this day.</small>
                    </div>

                    <div class="form-group mb-3" id="wrapIntervalDays" style="<?= ($schedule['frequency'] ?? '') !== 'every_x_days' ? 'display:none;' : '' ?>">
                        <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Generate Every X Days</label>
                        <select name="interval_days" class="form-control">
                            <option value="7" <?= (int)($schedule['interval_days'] ?? 15) === 7 ? 'selected' : '' ?>>Every 7 Days (Weekly cycle)</option>
                            <option value="15" <?= (int)($schedule['interval_days'] ?? 15) === 15 ? 'selected' : '' ?>>Every 15 Days (Bi-monthly: 1st-15th &amp; 16th-End)</option>
                            <option value="30" <?= (int)($schedule['interval_days'] ?? 15) === 30 ? 'selected' : '' ?>>Every 30 Days</option>
                            <option value="45" <?= (int)($schedule['interval_days'] ?? 15) === 45 ? 'selected' : '' ?>>Every 45 Days</option>
                        </select>
                    </div>

                    <div class="grid-2" style="gap:12px;">
                        <div class="form-group mb-3">
                            <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Invoice Time</label>
                            <input type="time" name="invoice_time" class="form-control" value="<?= htmlspecialchars(substr($schedule['invoice_time'] ?? '00:00:00', 0, 5)) ?>">
                        </div>
                        <div class="form-group mb-3">
                            <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block;">Timezone</label>
                            <select name="timezone" class="form-control">
                                <option value="UTC" <?= ($schedule['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : '' ?>>UTC (Default)</option>
                                <?php foreach (['America/New_York', 'America/Los_Angeles', 'Europe/London', 'Europe/Berlin', 'Asia/Dubai', 'Asia/Singapore', 'Asia/Tokyo', 'Asia/Dhaka'] as $tz): ?>
                                <option value="<?= $tz ?>" <?= ($schedule['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                                <?php endforeach; ?>
                            </select>
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
                                <option value="net15" <?= ($schedule['payment_terms'] ?? '') === 'net15' ? 'selected' : '' ?>>Net 15 (+15 Days)</option>
                                <option value="net30" <?= ($schedule['payment_terms'] ?? '') === 'net30' ? 'selected' : '' ?>>Net 30 (+30 Days)</option>
                                <option value="net7" <?= ($schedule['payment_terms'] ?? '') === 'net7' ? 'selected' : '' ?>>Net 7 (+7 Days)</option>
                                <option value="weekly" <?= ($schedule['payment_terms'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly (+3 Days)</option>
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
function toggleFrequencyFields(val) {
    document.getElementById('wrapMonthlyDay').style.display = (val === 'monthly') ? 'block' : 'none';
    document.getElementById('wrapIntervalDays').style.display = (val === 'every_x_days') ? 'block' : 'none';
}
</script>
