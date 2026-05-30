<!-- ═══════════════════ IP SCORE CHECKER MODAL ═══════════════════ -->
<div id="ip-score-overlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.75);z-index:10000;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(6px)">
    <div style="background:#fff;border-radius:16px;max-width:680px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 80px -10px rgba(0,0,0,.5);border:1px solid #E2E8F0">
        <!-- Header -->
        <div style="padding:20px 24px;border-bottom:1px solid #E2E8F0;background:linear-gradient(135deg,#F8FAFC,#EEF2FF);border-radius:16px 16px 0 0;display:flex;justify-content:space-between;align-items:center">
            <div>
                <h3 style="margin:0;font-size:18px;font-weight:700;color:#1E293B;display:flex;align-items:center;gap:8px">
                    🛡️ Multi-Provider IP Fraud Score Checker
                </h3>
                <p style="margin:4px 0 0;font-size:12px;color:#64748B">Select a fraud detection provider and check any IP</p>
            </div>
            <button onclick="closeScoreModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#94A3B8;line-height:1">&times;</button>
        </div>

        <!-- Body -->
        <div style="padding:24px">
            <!-- IP + Provider Selection -->
            <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
                <div style="flex:1;min-width:160px">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#64748B;letter-spacing:.04em;display:block;margin-bottom:4px">IP Address</label>
                    <input type="text" id="score-ip-input" class="form-control" placeholder="e.g. 8.8.8.8"
                           style="font-family:monospace;font-size:14px;padding:10px 14px;border-radius:8px;border:1.5px solid #CBD5E1">
                </div>
                <div style="flex:1;min-width:200px">
                    <label style="font-size:11px;font-weight:700;text-transform:uppercase;color:#64748B;letter-spacing:.04em;display:block;margin-bottom:4px">Fraud Detector</label>
                    <select id="score-provider-select" class="form-control"
                            style="padding:10px 14px;border-radius:8px;border:1.5px solid #CBD5E1;font-size:13px;font-weight:600">
                        <option value="ipqualityscore">🔴 IPQualityScore</option>
                        <option value="ipquery">🟢 IPQuery.io (Free)</option>
                        <option value="scamalytics">🟣 Scamalytics</option>
                        <option value="proxycheck">🔵 ProxyCheck.io</option>
                        <option value="botscout">🟠 BotScout</option>
                        <option value="frauddefense">🟡 FraudDefense.io</option>
                        <option value="fraudlabspro">⚫ FraudLabs Pro</option>
                    </select>
                </div>
                <div style="align-self:flex-end">
                    <button id="score-check-btn" onclick="runScoreCheck()" class="btn btn-primary"
                            style="padding:10px 20px;border-radius:8px;font-weight:700;font-size:13px;white-space:nowrap;background:linear-gradient(135deg,#7C3AED,#4F46E5);border:none;color:#fff;cursor:pointer">
                        🔍 Check Score
                    </button>
                </div>
            </div>

            <!-- Loading state -->
            <div id="score-loading" style="display:none;text-align:center;padding:40px">
                <div style="width:40px;height:40px;border:3px solid #E2E8F0;border-top:3px solid #7C3AED;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 12px"></div>
                <p style="color:#64748B;font-size:13px;margin:0">Checking IP score with <span id="score-loading-provider"></span>...</p>
            </div>

            <!-- Error state -->
            <div id="score-error" style="display:none;background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;padding:16px;margin-bottom:16px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:18px">⚠️</span>
                    <span id="score-error-msg" style="font-size:13px;color:#DC2626;font-weight:600"></span>
                </div>
            </div>

            <!-- Result area -->
            <div id="score-result" style="display:none">
                <!-- Score Gauge -->
                <div style="display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap">
                    <!-- Big Score Circle -->
                    <div style="flex:0 0 140px;text-align:center">
                        <div id="score-gauge" style="width:120px;height:120px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-direction:column;margin:0 auto;border:5px solid #E2E8F0;position:relative">
                            <div id="score-value" style="font-size:36px;font-weight:800;line-height:1">0</div>
                            <div id="score-risk-label" style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em">Clean</div>
                        </div>
                        <div id="score-provider-label" style="margin-top:8px;font-size:11px;font-weight:700;color:#64748B"></div>
                    </div>

                    <!-- Proxy Type + Flags -->
                    <div style="flex:1;min-width:200px">
                        <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px;margin-bottom:10px">
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#94A3B8;letter-spacing:.04em;margin-bottom:4px">Proxy Type</div>
                            <div id="score-proxy-type" style="font-size:18px;font-weight:800;color:#1E293B">None</div>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <span id="flag-vpn" class="score-flag">🔒 VPN</span>
                            <span id="flag-proxy" class="score-flag">🌐 Proxy</span>
                            <span id="flag-tor" class="score-flag">🧅 TOR</span>
                            <span id="flag-bot" class="score-flag">🤖 Bot</span>
                        </div>
                    </div>
                </div>

                <!-- Details Table -->
                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;overflow:hidden;margin-bottom:16px">
                    <table style="width:100%;font-size:12px;border-collapse:collapse">
                        <tbody>
                            <tr style="border-bottom:1px solid #E2E8F0">
                                <td style="padding:10px 14px;font-weight:700;color:#64748B;width:130px">ISP</td>
                                <td id="score-isp" style="padding:10px 14px;color:#1E293B">—</td>
                            </tr>
                            <tr style="border-bottom:1px solid #E2E8F0">
                                <td style="padding:10px 14px;font-weight:700;color:#64748B">Organization</td>
                                <td id="score-org" style="padding:10px 14px;color:#1E293B">—</td>
                            </tr>
                            <tr style="border-bottom:1px solid #E2E8F0">
                                <td style="padding:10px 14px;font-weight:700;color:#64748B">Country</td>
                                <td id="score-country" style="padding:10px 14px;color:#1E293B">—</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 14px;font-weight:700;color:#64748B">City</td>
                                <td id="score-city" style="padding:10px 14px;color:#1E293B">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Extra Details (collapsible) -->
                <details style="margin-bottom:16px">
                    <summary style="cursor:pointer;font-size:12px;font-weight:700;color:#64748B;padding:8px 0;user-select:none">
                        📋 Full API Response Details
                    </summary>
                    <pre id="score-raw-json" style="background:#0F172A;color:#A5B4FC;border-radius:8px;padding:14px;font-size:11px;overflow-x:auto;max-height:300px;margin-top:8px"></pre>
                </details>

                <!-- Check Another -->
                <div style="text-align:center;padding-top:4px">
                    <button onclick="document.getElementById('score-result').style.display='none'; document.getElementById('score-ip-input').focus();"
                            class="btn btn-secondary" style="font-size:12px;padding:6px 16px;border-radius:6px">🔄 Check Another IP</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.score-flag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    background: #F1F5F9;
    color: #94A3B8;
    border: 1px solid #E2E8F0;
    transition: all .2s;
}
.score-flag.active-true {
    background: #FEF2F2;
    color: #DC2626;
    border-color: #FECACA;
}
.score-flag.active-false {
    background: #F0FDF4;
    color: #16A34A;
    border-color: #BBF7D0;
}
</style>

<script>
function openScoreModal(ip) {
    document.getElementById('score-ip-input').value = ip || '';
    document.getElementById('score-loading').style.display = 'none';
    document.getElementById('score-error').style.display = 'none';
    document.getElementById('score-result').style.display = 'none';
    const overlay = document.getElementById('ip-score-overlay');
    overlay.style.display = 'flex';
    if (ip) {
        // Auto-run with default provider
        setTimeout(() => runScoreCheck(), 200);
    }
}

function closeScoreModal() {
    document.getElementById('ip-score-overlay').style.display = 'none';
}

// Close on backdrop click
document.getElementById('ip-score-overlay').addEventListener('click', function(e) {
    if (e.target === this) closeScoreModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('ip-score-overlay').style.display === 'flex') {
        closeScoreModal();
    }
});

async function runScoreCheck() {
    const ip = document.getElementById('score-ip-input').value.trim();
    const provider = document.getElementById('score-provider-select').value;

    if (!ip) {
        alert('Please enter an IP address');
        return;
    }

    // Show loading
    document.getElementById('score-loading').style.display = 'block';
    document.getElementById('score-loading-provider').textContent =
        document.getElementById('score-provider-select').selectedOptions[0].textContent.trim();
    document.getElementById('score-error').style.display = 'none';
    document.getElementById('score-result').style.display = 'none';
    document.getElementById('score-check-btn').disabled = true;

    try {
        const res = await fetch('/admin/ip-score-check?ip=' + encodeURIComponent(ip) + '&provider=' + encodeURIComponent(provider));
        const data = await res.json();

        document.getElementById('score-loading').style.display = 'none';
        document.getElementById('score-check-btn').disabled = false;

        if (data.error || data.success === false) {
            document.getElementById('score-error').style.display = 'block';
            document.getElementById('score-error-msg').textContent = data.error || 'Unknown error occurred';
            return;
        }

        // Populate result
        const score = data.score || 0;
        const riskLevel = data.risk_level || 'Clean';

        // Score gauge colors
        let gaugeColor = '#22C55E'; // green
        let gaugeBg = '#F0FDF4';
        if (score >= 75) { gaugeColor = '#DC2626'; gaugeBg = '#FEF2F2'; }
        else if (score >= 40) { gaugeColor = '#F59E0B'; gaugeBg = '#FFFBEB'; }
        else if (score >= 15) { gaugeColor = '#3B82F6'; gaugeBg = '#EFF6FF'; }

        const gauge = document.getElementById('score-gauge');
        gauge.style.borderColor = gaugeColor;
        gauge.style.background = gaugeBg;

        document.getElementById('score-value').textContent = score;
        document.getElementById('score-value').style.color = gaugeColor;
        document.getElementById('score-risk-label').textContent = riskLevel;
        document.getElementById('score-risk-label').style.color = gaugeColor;
        document.getElementById('score-provider-label').textContent = data.provider || provider;

        // Proxy type
        const pt = document.getElementById('score-proxy-type');
        pt.textContent = data.proxy_type || 'None';
        pt.style.color = (data.proxy_type && data.proxy_type !== 'None') ? '#DC2626' : '#22C55E';

        // Flags
        setFlag('flag-vpn', data.vpn);
        setFlag('flag-proxy', data.proxy);
        setFlag('flag-tor', data.tor);
        setFlag('flag-bot', data.bot);

        // Details
        document.getElementById('score-isp').textContent = data.isp || '—';
        document.getElementById('score-org').textContent = data.org || '—';
        document.getElementById('score-country').textContent = data.country || '—';
        document.getElementById('score-city').textContent = data.city || '—';

        // Raw JSON
        document.getElementById('score-raw-json').textContent = JSON.stringify(data, null, 2);

        document.getElementById('score-result').style.display = 'block';

    } catch (err) {
        document.getElementById('score-loading').style.display = 'none';
        document.getElementById('score-check-btn').disabled = false;
        document.getElementById('score-error').style.display = 'block';
        document.getElementById('score-error-msg').textContent = 'Network error: ' + err.message;
    }
}

function setFlag(id, value) {
    const el = document.getElementById(id);
    el.className = 'score-flag active-' + (!!value);
    // Add check/cross icon
    const label = el.textContent.replace(/[✓✗]/g, '').trim();
    el.innerHTML = label + ' ' + (value ? '<span style="font-weight:800">✓</span>' : '<span>✗</span>');
}
</script>
