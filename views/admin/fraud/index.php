<?php require BASE_PATH . '/views/layouts/admin.php'; ?>

<div class="page-header">
    <div>
        <h1>&#128737; Fraud Detector</h1>
        <p>Real-time fraud detection powered by <strong>IPQualityScore</strong> (ipqualityscore.com)</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/admin/fraud?export=csv" class="btn btn-secondary">&#8595; Fraud Clicks CSV</a>
        <?php if (($fraudCfg['mode'] ?? 'score_only') === 'score_only'): ?>
        <a href="/admin/fraud?export=conversion_csv" class="btn btn-secondary">&#8595; Conversion Scores CSV</a>
        <?php endif; ?>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid mb-3">
    <div class="stat-card">
        <div class="stat-icon red">&#9888;</div>
        <div class="stat-label">Fraud Today</div>
        <div class="stat-value"><?= number_format($todayFraud['cnt'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">&#128683;</div>
        <div class="stat-label">Blocked Today</div>
        <div class="stat-value"><?= number_format($todayFraud['blocked'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#128270;</div>
        <div class="stat-label">Total Fraud Clicks</div>
        <div class="stat-value"><?= number_format($totalFraud) ?></div>
    </div>
    <?php
        $ipqsKey       = $fraudCfg['ipqs_api_key'] ?? '';
        $ipqsEnabled   = (bool)($fraudCfg['ipqs_enabled'] ?? false);
        $fraudActive   = $ipqsEnabled && !empty($ipqsKey);

        $ipqActive     = (bool)($fraudCfg['ipquery_enabled'] ?? false);

        $scamKey       = $fraudCfg['scamalytics_api_key'] ?? '';
        $scamEnabled   = (bool)($fraudCfg['scamalytics_enabled'] ?? false);
        $scamActive    = $scamEnabled && !empty($scamKey);
        $scamMode      = $fraudCfg['scamalytics_mode'] ?? 'score_only';
    ?>
    <div class="stat-card">
        <div class="stat-icon <?= $fraudActive ? 'green' : 'muted' ?>">&#128737;</div>
        <div class="stat-label">IPQS Status</div>
        <div class="stat-value" style="font-size:16px;color:<?= $fraudActive ? 'var(--secondary,#10b981)' : 'inherit' ?>">
            <?= $fraudActive ? 'Active' : 'Disabled' ?>
        </div>
        <?php if ($fraudActive): ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px">IPQualityScore</div>
        <?php endif; ?>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $ipqActive ? 'green' : 'muted' ?>">&#127759;</div>
        <div class="stat-label">IPQuery Status</div>
        <div class="stat-value" style="font-size:16px;color:<?= $ipqActive ? 'var(--secondary,#10b981)' : 'inherit' ?>">
            <?= $ipqActive ? 'Active' : 'Disabled' ?>
        </div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px">ipquery.io &middot; Free</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $scamActive ? 'green' : 'muted' ?>">&#128272;</div>
        <div class="stat-label">Scamalytics Status</div>
        <div class="stat-value" style="font-size:16px;color:<?= $scamActive ? 'var(--secondary,#10b981)' : 'inherit' ?>">
            <?= $scamActive ? 'Active' : 'Disabled' ?>
        </div>
        <?php if ($scamActive): ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px"><?= $scamMode === 'auto_block' ? 'Auto-Block' : 'Score Only' ?></div>
        <?php endif; ?>
    </div>
    <?php
        $pcKey     = $fraudCfg['proxycheck_api_key'] ?? '';
        $pcEnabled = (bool)($fraudCfg['proxycheck_enabled'] ?? false);
        $pcActive  = $pcEnabled && !empty($pcKey);
        $pcMode    = $fraudCfg['proxycheck_mode'] ?? 'score_only';

        $bsKey     = $fraudCfg['botscout_api_key'] ?? '';
        $bsEnabled = (bool)($fraudCfg['botscout_enabled'] ?? false);
        $bsActive  = $bsEnabled && !empty($bsKey);
        $bsMode    = $fraudCfg['botscout_mode'] ?? 'score_only';

        $fdKey     = $fraudCfg['frauddefense_api_key'] ?? '';
        $fdEnabled = (bool)($fraudCfg['frauddefense_enabled'] ?? false);
        $fdActive  = $fdEnabled && !empty($fdKey);
        $fdMode    = $fraudCfg['frauddefense_mode'] ?? 'score_only';

        $flpKey     = $fraudCfg['fraudlabspro_api_key'] ?? '';
        $flpEnabled = (bool)($fraudCfg['fraudlabspro_enabled'] ?? false);
        $flpActive  = $flpEnabled && !empty($flpKey);
        $flpMode    = $fraudCfg['fraudlabspro_mode'] ?? 'score_only';
    ?>
    <div class="stat-card">
        <div class="stat-icon <?= $pcActive ? 'green' : 'muted' ?>">&#128204;</div>
        <div class="stat-label">ProxyCheck Status</div>
        <div class="stat-value" style="font-size:16px;color:<?= $pcActive ? 'var(--secondary,#10b981)' : 'inherit' ?>">
            <?= $pcActive ? 'Active' : 'Disabled' ?>
        </div>
        <?php if ($pcActive): ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px"><?= $pcMode === 'auto_block' ? 'Auto-Block' : 'Score Only' ?></div>
        <?php endif; ?>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $bsActive ? 'green' : 'muted' ?>">&#129302;</div>
        <div class="stat-label">BotScout Status</div>
        <div class="stat-value" style="font-size:16px;color:<?= $bsActive ? 'var(--secondary,#10b981)' : 'inherit' ?>">
            <?= $bsActive ? 'Active' : 'Disabled' ?>
        </div>
        <?php if ($bsActive): ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px"><?= $bsMode === 'auto_block' ? 'Auto-Block' : 'Score Only' ?></div>
        <?php endif; ?>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $fdActive ? 'green' : 'muted' ?>">&#128737;</div>
        <div class="stat-label">FraudDefense Status</div>
        <div class="stat-value" style="font-size:16px;color:<?= $fdActive ? 'var(--secondary,#10b981)' : 'inherit' ?>">
            <?= $fdActive ? 'Active' : 'Disabled' ?>
        </div>
        <?php if ($fdActive): ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px"><?= $fdMode === 'auto_block' ? 'Auto-Block' : 'Score Only' ?></div>
        <?php endif; ?>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $flpActive ? 'green' : 'muted' ?>">&#129302;</div>
        <div class="stat-label">FraudLabs Pro Status</div>
        <div class="stat-value" style="font-size:16px;color:<?= $flpActive ? 'var(--secondary,#10b981)' : 'inherit' ?>">
            <?= $flpActive ? 'Active' : 'Disabled' ?>
        </div>
        <?php if ($flpActive): ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:3px"><?= $flpMode === 'auto_block' ? 'Auto-Block' : 'Score Only' ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2 mb-3">
    <!-- Settings -->
    <div class="card">
        <div class="card-header"><span class="card-title">&#128737; IPQualityScore Settings</span></div>
        <div class="card-body">
            <form method="POST" id="fraud-settings-form">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="save_settings">

                <!-- ── Detection Mode ── -->
                <?php $currentMode = $fraudCfg['mode'] ?? 'score_only'; ?>
                <div style="margin-bottom:18px">
                    <p style="font-size:13px;font-weight:700;color:var(--text-muted);margin:0 0 10px">Detection Mode</p>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:8px;cursor:pointer;border:2px solid <?= $currentMode==='block' ? 'var(--primary,#4f46e5)' : 'var(--border)' ?>;background:<?= $currentMode==='block' ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)' ?>;margin-bottom:8px" id="lbl-mode-block">
                        <input type="radio" name="fraud_mode" value="block" <?= $currentMode==='block' ? 'checked' : '' ?> onchange="updateModeUI()" style="margin-top:3px;flex-shrink:0">
                        <div>
                            <strong>&#128683; Block Mode</strong>
                            <span style="font-size:11px;background:#4f46e5;color:#fff;border-radius:4px;padding:1px 6px;margin-left:4px">DEFAULT</span>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:3px">Fraud check runs on every <strong>click</strong> and optionally on conversions. Fraudulent traffic is blocked automatically.</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:8px;cursor:pointer;border:2px solid <?= $currentMode==='score_only' ? '#0891b2' : 'var(--border)' ?>;background:<?= $currentMode==='score_only' ? '#ecfeff' : 'var(--bg-subtle,#f8f9fa)' ?>" id="lbl-mode-score">
                        <input type="radio" name="fraud_mode" value="score_only" <?= $currentMode==='score_only' ? 'checked' : '' ?> onchange="updateModeUI()" style="margin-top:3px;flex-shrink:0">
                        <div>
                            <strong>&#128270; Score-Only Mode</strong>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:3px">Fraud check runs <strong>only after a conversion</strong> — never on clicks. Conversions are <strong>never blocked automatically</strong>. The fraud score is saved for admin review.</div>
                        </div>
                    </label>
                </div>

                <!-- ── IPQualityScore API ── -->
                <div style="background:var(--bg-subtle,#f8f9fa);border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:18px">
                    <p style="font-size:13px;font-weight:700;color:var(--text-muted);margin:0 0 12px">&#x1F6E1; IPQualityScore API</p>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="ipqs_enabled" id="ipqsEnabled" <?= ($fraudCfg['ipqs_enabled'] ?? false) ? 'checked' : '' ?>>
                        <label for="ipqsEnabled"><strong>Enable IPQualityScore fraud detection</strong></label>
                    </div>
                    <div class="form-group">
                        <label>API Key</label>
                        <div style="display:flex;gap:8px">
                            <input type="text" name="ipqs_api_key" id="ipqs-api-key-input" class="form-control"
                                   value="<?= Helpers::e($fraudCfg['ipqs_api_key'] ?? '') ?>"
                                   placeholder="Paste your IPQualityScore API key…"
                                   style="font-family:monospace">
                            <button type="button" class="btn btn-secondary" onclick="testIpqsConnection()" id="btn-test-ipqs" style="white-space:nowrap">
                                Test Connection
                            </button>
                        </div>
                        <div class="form-hint">
                            Get your API key at <a href="https://www.ipqualityscore.com/" target="_blank" rel="noopener">ipqualityscore.com</a>
                        </div>
                        <div id="ipqs-test-result" style="display:none;margin-top:8px;padding:8px 12px;border-radius:6px;font-size:13px"></div>
                    </div>
                    <div class="form-group mt-3">
                        <label>Outgoing Proxy / Rotating Proxy <small class="text-muted">(Optional — Hides Server IP from IPQS)</small></label>
                        <input type="text" name="ipqs_proxy" class="form-control"
                               value="<?= Helpers::e($fraudCfg['ipqs_proxy'] ?? '') ?>"
                               placeholder="e.g. http://username:password@proxy-ip:port or http://123.45.67.89:8080"
                               style="font-family:monospace">
                        <div class="form-hint">
                            Set an outgoing HTTP/SOCKS5 proxy to mask your server IP address when rotating trial API keys. IPQS will see requests coming from the proxy IP instead of your server.
                        </div>
                    </div>
                </div>

                <!-- ── Conversion Trigger (Block Mode only) ── -->
                <div id="row-check-on-conv" style="<?= ($fraudCfg['mode'] ?? 'score_only') === 'score_only' ? 'display:none' : '' ?>">
                    <div class="form-check mb-2" style="border:2px solid var(--primary,#4f46e5);border-radius:6px;padding:10px 14px;background:var(--primary-light,#eef2ff)">
                        <?php
                        // Block Mode now enforces blocking by default. The checkbox is
                        // pre-checked so existing installs upgrade to active blocking on
                        // the next save. Admins who want to opt out can still untick it.
                        $_blockChecked = !isset($fraudCfg['check_on_conversion'])
                            ? true
                            : (bool)$fraudCfg['check_on_conversion'];
                        ?>
                        <input type="checkbox" name="check_on_conversion" id="checkOnConversion"
                               <?= $_blockChecked ? 'checked' : '' ?>>
                        <label for="checkOnConversion">
                            <strong>&#9889; Enforce conversion blocking when fraud is detected</strong>
                        </label>
                        <div class="form-hint" style="margin-top:4px">
                            <strong>Block Mode default behaviour.</strong> When checked, fraud detection fires on every conversion and fraudulent conversions are blocked in real time — affiliate balance is reversed, the advertiser postback is suppressed, and the conversion is hidden from reports. Untick to score conversions silently without blocking.
                        </div>
                    </div>
                </div>
                <div id="row-score-only-note" style="<?= ($fraudCfg['mode'] ?? 'score_only') !== 'score_only' ? 'display:none' : '' ?>;margin-bottom:12px;padding:10px 14px;border-radius:6px;background:#ecfeff;border:2px solid #0891b2;font-size:12px;color:#0c4a6e">
                    <strong>&#128270; Score-Only Mode active:</strong> Fraud score is checked after every conversion and saved automatically. No conversions are blocked — admin reviews scores manually in the Fraud Score Report.
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Flag Threshold (score)</label>
                        <input type="number" name="score_threshold" class="form-control" min="0" max="100"
                               value="<?= (int)($fraudCfg['score_threshold'] ?? 75) ?>">
                        <div class="form-hint">Flag if score &ge; this (0–100)</div>
                    </div>
                    <div class="form-group">
                        <label>Block Threshold (score)</label>
                        <input type="number" name="block_threshold" class="form-control" min="0" max="100"
                               value="<?= (int)($fraudCfg['block_threshold'] ?? 90) ?>">
                        <div class="form-hint">Block if score &ge; this (0–100)</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>API Timeout (seconds)</label>
                    <input type="number" name="timeout_seconds" class="form-control" min="10" max="30"
                           value="<?= max(10, (int)($fraudCfg['timeout_seconds'] ?? 10)) ?>">
                    <div class="form-hint">Minimum 10 s recommended for IPQualityScore</div>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="fail_open" id="failOpen"
                           <?= ($fraudCfg['fail_open'] ?? true) ? 'checked' : '' ?>>
                    <label for="failOpen">Fail open (allow traffic if IPQS API is unreachable)</label>
                </div>
                <p style="font-size:12px;font-weight:700;color:var(--text-muted);margin:12px 0 8px">Auto-flag / block for:</p>
                <?php
                $checks    = $fraudCfg['checks'] ?? [];
                $checkOpts = ['vpn'=>'VPN','proxy'=>'Proxy','tor'=>'Tor Exit Nodes','bot'=>'Bots','datacenter'=>'Datacenter IPs'];
                foreach ($checkOpts as $k => $label): ?>
                <div class="form-check mb-1">
                    <input type="checkbox" name="check_<?= $k ?>" <?= ($checks[$k] ?? false) ? 'checked' : '' ?>>
                    <label><?= $label ?></label>
                </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary" style="margin-top:16px">Save Settings</button>
            </form>
        </div>
    </div>

    <!-- Top Fraud IPs -->
    <div class="card">
        <div class="card-header"><span class="card-title">Top Fraud IPs</span></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>IP Address</th><th>Hits</th><th>Max Score</th></tr></thead>
                <tbody>
                <?php if (empty($topFraudIPs)): ?>
                <tr><td colspan="3" class="text-center text-muted" style="padding:24px">No fraud detected yet</td></tr>
                <?php else: ?>
                <?php foreach ($topFraudIPs as $ip): ?>
                <tr>
                    <td style="font-family:monospace;font-size:13px"><?= Helpers::e($ip['ip_address']) ?></td>
                    <td><?= (int)$ip['cnt'] ?></td>
                    <td><span class="badge badge-<?= $ip['max_score']>=90?'danger':($ip['max_score']>=75?'warning':'muted') ?>"><?= (int)$ip['max_score'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── IPQuery.io + Scamalytics Settings ──────────────────────────────────── -->
<div class="grid-2 mb-3">

    <!-- IPQuery.io -->
    <div class="card">
        <div class="card-header"><span class="card-title">&#127759; IPQuery.io Settings</span></div>
        <div class="card-body">
            <form method="POST" id="ipquery-settings-form">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="save_ipquery_settings">

                <!-- Detection Mode -->
                <?php $ipqMode = $fraudCfg['ipquery_mode'] ?? 'score_only'; ?>
                <div style="margin-bottom:16px">
                    <p style="font-size:13px;font-weight:700;color:var(--text-muted);margin:0 0 8px">Detection Mode</p>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $ipqMode==='score_only' ? '#0891b2' : 'var(--border)' ?>;background:<?= $ipqMode==='score_only' ? '#ecfeff' : 'var(--bg-subtle,#f8f9fa)' ?>;margin-bottom:6px" id="lbl-ipq-score">
                        <input type="radio" name="ipquery_mode" value="score_only" <?= $ipqMode==='score_only' ? 'checked' : '' ?> onchange="updateIpqModeUI()" style="margin-top:3px;flex-shrink:0">
                        <div>
                            <strong>&#128270; Score Only</strong>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Record score on every conversion. Nothing is blocked automatically.</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $ipqMode==='auto_block' ? 'var(--primary,#4f46e5)' : 'var(--border)' ?>;background:<?= $ipqMode==='auto_block' ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)' ?>" id="lbl-ipq-block">
                        <input type="radio" name="ipquery_mode" value="auto_block" <?= $ipqMode==='auto_block' ? 'checked' : '' ?> onchange="updateIpqModeUI()" style="margin-top:3px;flex-shrink:0">
                        <div>
                            <strong>&#128683; Auto Block</strong>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Block conversions with score &ge; block threshold. Flag those in the medium range.</div>
                        </div>
                    </label>
                </div>

                <!-- Enable + info -->
                <div style="background:var(--bg-subtle,#f8f9fa);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:14px">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="ipquery_enabled" id="ipqueryEnabled"
                               <?= ($fraudCfg['ipquery_enabled'] ?? false) ? 'checked' : '' ?>>
                        <label for="ipqueryEnabled"><strong>Enable IPQuery.io fraud detection</strong></label>
                    </div>
                    <div style="font-size:12px;color:var(--text-muted)">
                        Free API — no key required. Checks VPN, proxy, Tor, datacenter on every conversion.
                    </div>
                    <div style="margin-top:10px;display:flex;gap:8px;align-items:center">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="testIPQuery()" id="btn-test-ipquery" style="white-space:nowrap">
                            Test Connection
                        </button>
                        <span style="font-size:12px;color:var(--text-muted)">Tests IP 8.8.8.8 (Google DNS)</span>
                    </div>
                    <div id="ipquery-test-result" style="display:none;margin-top:8px;padding:8px 12px;border-radius:6px;font-size:13px"></div>
                </div>

                <!-- Thresholds -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Flag Threshold (score)</label>
                        <input type="number" name="ipquery_flag_threshold" class="form-control" min="1" max="100"
                               value="<?= (int)($fraudCfg['ipquery_flag_threshold'] ?? 50) ?>">
                        <div class="form-hint">Flag if score &ge; this (0–100)</div>
                    </div>
                    <div class="form-group">
                        <label>Block Threshold (score)</label>
                        <input type="number" name="ipquery_block_threshold" class="form-control" min="1" max="100"
                               value="<?= (int)($fraudCfg['ipquery_block_threshold'] ?? 80) ?>">
                        <div class="form-hint">Block if score &ge; this (auto_block only)</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save IPQuery Settings</button>
            </form>
        </div>
    </div>

    <!-- Scamalytics -->
    <div class="card">
        <div class="card-header"><span class="card-title">&#128272; Scamalytics Settings</span></div>
        <div class="card-body">
            <form method="POST" id="scamalytics-settings-form">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="save_scamalytics_settings">

                <!-- Mode -->
                <?php $scamCurrentMode = $fraudCfg['scamalytics_mode'] ?? 'score_only'; ?>
                <div style="margin-bottom:16px">
                    <p style="font-size:13px;font-weight:700;color:var(--text-muted);margin:0 0 8px">Detection Mode</p>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $scamCurrentMode==='score_only' ? '#0891b2' : 'var(--border)' ?>;background:<?= $scamCurrentMode==='score_only' ? '#ecfeff' : 'var(--bg-subtle,#f8f9fa)' ?>;margin-bottom:6px" id="lbl-scam-score">
                        <input type="radio" name="scamalytics_mode" value="score_only" <?= $scamCurrentMode==='score_only' ? 'checked' : '' ?> onchange="updateScamModeUI()" style="margin-top:3px;flex-shrink:0">
                        <div>
                            <strong>&#128270; Score Only</strong>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Record score on every conversion. Nothing is blocked automatically.</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $scamCurrentMode==='auto_block' ? 'var(--primary,#4f46e5)' : 'var(--border)' ?>;background:<?= $scamCurrentMode==='auto_block' ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)' ?>" id="lbl-scam-block">
                        <input type="radio" name="scamalytics_mode" value="auto_block" <?= $scamCurrentMode==='auto_block' ? 'checked' : '' ?> onchange="updateScamModeUI()" style="margin-top:3px;flex-shrink:0">
                        <div>
                            <strong>&#128683; Auto Block</strong>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Block conversions with score &ge; block threshold. Flag those in the medium range.</div>
                        </div>
                    </label>
                </div>

                <!-- Enable + Credentials -->
                <div style="background:var(--bg-subtle,#f8f9fa);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:14px">
                    <div class="form-check mb-3">
                        <input type="checkbox" name="scamalytics_enabled" id="scamalyticsEnabled"
                               <?= ($fraudCfg['scamalytics_enabled'] ?? false) ? 'checked' : '' ?>>
                        <label for="scamalyticsEnabled"><strong>Enable Scamalytics fraud detection</strong></label>
                    </div>

                    <!-- Credentials info box -->
                    <div style="background:#fffbeb;border:1px solid #f59e0b;border-radius:6px;padding:10px 12px;font-size:12px;color:#78350f;margin-bottom:12px">
                        <strong>Where to find your credentials:</strong> Log in to
                        <a href="https://scamalytics.com/" target="_blank" rel="noopener">scamalytics.com</a>
                        and open the API page. You will see:<br>
                        <code style="font-size:11px">user: 69d275dd058b8</code><br>
                        <code style="font-size:11px">API key: 784521…</code><br>
                        <code style="font-size:11px">Example: https://api<strong>12</strong>.scamalytics.com/v3/…</code><br>
                        The <strong>server number</strong> is the number after "api" in the example URL (e.g. <strong>12</strong>).
                    </div>

                    <div class="form-row cols-2" style="margin-bottom:10px">
                        <div class="form-group" style="margin-bottom:0">
                            <label>API User</label>
                            <input type="text" name="scamalytics_user" id="scamalytics-user-input" class="form-control"
                                   value="<?= Helpers::e($fraudCfg['scamalytics_user'] ?? '') ?>"
                                   placeholder="e.g. 69d275dd058b8"
                                   style="font-family:monospace">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label>API Server <span style="font-weight:400;color:var(--text-muted)">(number only)</span></label>
                            <input type="text" name="scamalytics_server" id="scamalytics-server-input" class="form-control"
                                   value="<?= Helpers::e($fraudCfg['scamalytics_server'] ?? '') ?>"
                                   placeholder="e.g. 12"
                                   style="font-family:monospace;max-width:100px">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label>API Key</label>
                        <div style="display:flex;gap:8px">
                            <input type="text" name="scamalytics_api_key" id="scamalytics-api-key-input" class="form-control"
                                   value="<?= Helpers::e($fraudCfg['scamalytics_api_key'] ?? '') ?>"
                                   placeholder="Paste your Scamalytics API key…"
                                   style="font-family:monospace">
                            <button type="button" class="btn btn-secondary" onclick="testScamalytics()" id="btn-test-scam" style="white-space:nowrap">
                                Test
                            </button>
                        </div>
                        <div class="form-hint">
                            Get your credentials at <a href="https://scamalytics.com/" target="_blank" rel="noopener">scamalytics.com</a>
                        </div>
                        <div id="scam-test-result" style="display:none;margin-top:8px;padding:8px 12px;border-radius:6px;font-size:13px"></div>
                    </div>
                </div>

                <!-- Thresholds -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Flag Threshold (score)</label>
                        <input type="number" name="scamalytics_flag_threshold" class="form-control" min="1" max="100"
                               value="<?= (int)($fraudCfg['scamalytics_flag_threshold'] ?? 50) ?>">
                        <div class="form-hint">Flag if score &ge; this (0–100)</div>
                    </div>
                    <div class="form-group">
                        <label>Block Threshold (score)</label>
                        <input type="number" name="scamalytics_block_threshold" class="form-control" min="1" max="100"
                               value="<?= (int)($fraudCfg['scamalytics_block_threshold'] ?? 80) ?>">
                        <div class="form-hint">Block if score &ge; this (auto_block mode only)</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Scamalytics Settings</button>
            </form>
        </div>
    </div>

</div><!-- /.grid-2 IPQuery+Scamalytics -->

<!-- ── ProxyCheck.io + BotScout + FraudDefense Settings ─────────────────── -->
<div class="grid-2 mb-3">

    <!-- ProxyCheck.io -->
    <div class="card">
        <div class="card-header"><span class="card-title">&#128204; ProxyCheck.io Settings</span></div>
        <div class="card-body">
            <form method="POST" id="proxycheck-settings-form">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="save_proxycheck_settings">

                <?php $pcCurrentMode = $fraudCfg['proxycheck_mode'] ?? 'score_only'; ?>
                <div style="margin-bottom:16px">
                    <p style="font-size:13px;font-weight:700;color:var(--text-muted);margin:0 0 8px">Detection Mode</p>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $pcCurrentMode==='score_only' ? '#0891b2' : 'var(--border)' ?>;background:<?= $pcCurrentMode==='score_only' ? '#ecfeff' : 'var(--bg-subtle,#f8f9fa)' ?>;margin-bottom:6px" id="lbl-pc-score">
                        <input type="radio" name="proxycheck_mode" value="score_only" <?= $pcCurrentMode==='score_only' ? 'checked' : '' ?> onchange="updatePcModeUI()" style="margin-top:3px;flex-shrink:0">
                        <div>
                            <strong>&#128270; Score Only</strong>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Record proxy/VPN risk score on every conversion. Nothing is blocked automatically.</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $pcCurrentMode==='auto_block' ? 'var(--primary,#4f46e5)' : 'var(--border)' ?>;background:<?= $pcCurrentMode==='auto_block' ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)' ?>" id="lbl-pc-block">
                        <input type="radio" name="proxycheck_mode" value="auto_block" <?= $pcCurrentMode==='auto_block' ? 'checked' : '' ?> onchange="updatePcModeUI()" style="margin-top:3px;flex-shrink:0">
                        <div>
                            <strong>&#128683; Auto Block</strong>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Block conversions with risk &ge; block threshold, or any detected proxy/VPN.</div>
                        </div>
                    </label>
                </div>

                <div style="background:var(--bg-subtle,#f8f9fa);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:14px">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="proxycheck_enabled" id="proxycheckEnabled"
                               <?= ($fraudCfg['proxycheck_enabled'] ?? false) ? 'checked' : '' ?>>
                        <label for="proxycheckEnabled"><strong>Enable ProxyCheck.io fraud detection</strong></label>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label>API Key</label>
                        <div style="display:flex;gap:8px">
                            <input type="text" name="proxycheck_api_key" id="proxycheck-api-key-input" class="form-control"
                                   value="<?= Helpers::e($fraudCfg['proxycheck_api_key'] ?? '') ?>"
                                   placeholder="Paste your ProxyCheck.io API key…"
                                   style="font-family:monospace">
                            <button type="button" class="btn btn-secondary" onclick="testProxyCheck()" id="btn-test-pc" style="white-space:nowrap">Test</button>
                        </div>
                        <div class="form-hint">Get your API key at <a href="https://proxycheck.io/" target="_blank" rel="noopener">proxycheck.io</a> &mdash; free tier available</div>
                        <div id="pc-test-result" style="display:none;margin-top:8px;padding:8px 12px;border-radius:6px;font-size:13px"></div>
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label>Flag Threshold (risk score)</label>
                        <input type="number" name="proxycheck_flag_threshold" class="form-control" min="1" max="100"
                               value="<?= (int)($fraudCfg['proxycheck_flag_threshold'] ?? 30) ?>">
                        <div class="form-hint">Flag if risk &ge; this (0–100)</div>
                    </div>
                    <div class="form-group">
                        <label>Block Threshold (risk score)</label>
                        <input type="number" name="proxycheck_block_threshold" class="form-control" min="1" max="100"
                               value="<?= (int)($fraudCfg['proxycheck_block_threshold'] ?? 50) ?>">
                        <div class="form-hint">Block if risk &ge; this (auto_block only)</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save ProxyCheck Settings</button>
            </form>
        </div>
    </div>

    <!-- BotScout -->
    <div class="card">
        <div class="card-header"><span class="card-title">&#129302; BotScout Settings</span></div>
        <div class="card-body">
            <form method="POST" id="botscout-settings-form">
                <?= Helpers::csrf() ?>
                <input type="hidden" name="action" value="save_botscout_settings">

                <?php $bsCurrentMode = $fraudCfg['botscout_mode'] ?? 'score_only'; ?>
                <div style="margin-bottom:16px">
                    <p style="font-size:13px;font-weight:700;color:var(--text-muted);margin:0 0 8px">Detection Mode</p>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $bsCurrentMode==='score_only' ? '#0891b2' : 'var(--border)' ?>;background:<?= $bsCurrentMode==='score_only' ? '#ecfeff' : 'var(--bg-subtle,#f8f9fa)' ?>;margin-bottom:6px" id="lbl-bs-score">
                        <input type="radio" name="botscout_mode" value="score_only" <?= $bsCurrentMode==='score_only' ? 'checked' : '' ?> onchange="updateBsModeUI()" style="margin-top:3px;flex-shrink:0">
                        <div>
                            <strong>&#128270; Score Only</strong>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Record bot detection result on every conversion. Nothing is blocked automatically.</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $bsCurrentMode==='auto_block' ? 'var(--primary,#4f46e5)' : 'var(--border)' ?>;background:<?= $bsCurrentMode==='auto_block' ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)' ?>" id="lbl-bs-block">
                        <input type="radio" name="botscout_mode" value="auto_block" <?= $bsCurrentMode==='auto_block' ? 'checked' : '' ?> onchange="updateBsModeUI()" style="margin-top:3px;flex-shrink:0">
                        <div>
                            <strong>&#128683; Auto Block</strong>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Block conversions automatically when a bot is detected.</div>
                        </div>
                    </label>
                </div>

                <div style="background:var(--bg-subtle,#f8f9fa);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:14px">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="botscout_enabled" id="botscoutEnabled"
                               <?= ($fraudCfg['botscout_enabled'] ?? false) ? 'checked' : '' ?>>
                        <label for="botscoutEnabled"><strong>Enable BotScout bot detection</strong></label>
                    </div>
                    <div style="background:#fffbeb;border:1px solid #f59e0b;border-radius:6px;padding:8px 10px;font-size:12px;color:#78350f;margin-bottom:10px">
                        BotScout checks IPs against its bot database. Detects scrapers, spammers, and automated traffic.
                        Get a free API key at <a href="https://botscout.com/getkey.htm" target="_blank" rel="noopener">botscout.com/getkey.htm</a>.
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label>API Key</label>
                        <div style="display:flex;gap:8px">
                            <input type="text" name="botscout_api_key" id="botscout-api-key-input" class="form-control"
                                   value="<?= Helpers::e($fraudCfg['botscout_api_key'] ?? '') ?>"
                                   placeholder="Paste your BotScout API key…"
                                   style="font-family:monospace">
                            <button type="button" class="btn btn-secondary" onclick="testBotScout()" id="btn-test-bs" style="white-space:nowrap">Test</button>
                        </div>
                        <div id="bs-test-result" style="display:none;margin-top:8px;padding:8px 12px;border-radius:6px;font-size:13px"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save BotScout Settings</button>
            </form>
        </div>
    </div>

</div><!-- /.grid-2 ProxyCheck+BotScout -->

<!-- FraudDefense.io full-width card -->
<div class="card mb-3">
    <div class="card-header"><span class="card-title">&#128737; FraudDefense.io Settings</span></div>
    <div class="card-body" style="max-width:680px">
        <form method="POST" id="frauddefense-settings-form">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="save_frauddefense_settings">

            <?php $fdCurrentMode = $fraudCfg['frauddefense_mode'] ?? 'score_only'; ?>
            <div style="margin-bottom:16px">
                <p style="font-size:13px;font-weight:700;color:var(--text-muted);margin:0 0 8px">Detection Mode</p>
                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $fdCurrentMode==='score_only' ? '#0891b2' : 'var(--border)' ?>;background:<?= $fdCurrentMode==='score_only' ? '#ecfeff' : 'var(--bg-subtle,#f8f9fa)' ?>;margin-bottom:6px" id="lbl-fd-score">
                    <input type="radio" name="frauddefense_mode" value="score_only" <?= $fdCurrentMode==='score_only' ? 'checked' : '' ?> onchange="updateFdModeUI()" style="margin-top:3px;flex-shrink:0">
                    <div>
                        <strong>&#128270; Score Only</strong>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Record fraud score on every conversion. Nothing is blocked automatically.</div>
                    </div>
                </label>
                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $fdCurrentMode==='auto_block' ? 'var(--primary,#4f46e5)' : 'var(--border)' ?>;background:<?= $fdCurrentMode==='auto_block' ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)' ?>" id="lbl-fd-block">
                    <input type="radio" name="frauddefense_mode" value="auto_block" <?= $fdCurrentMode==='auto_block' ? 'checked' : '' ?> onchange="updateFdModeUI()" style="margin-top:3px;flex-shrink:0">
                    <div>
                        <strong>&#128683; Auto Block</strong>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Block conversions with fraud_score &ge; block threshold. Flag those in the medium range.</div>
                    </div>
                </label>
            </div>

            <div style="background:var(--bg-subtle,#f8f9fa);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:14px">
                <div class="form-check mb-2">
                    <input type="checkbox" name="frauddefense_enabled" id="frauddefenseEnabled"
                           <?= ($fraudCfg['frauddefense_enabled'] ?? false) ? 'checked' : '' ?>>
                    <label for="frauddefenseEnabled"><strong>Enable FraudDefense.io fraud detection</strong></label>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label>API Key</label>
                    <div style="display:flex;gap:8px">
                        <input type="text" name="frauddefense_api_key" id="frauddefense-api-key-input" class="form-control"
                               value="<?= Helpers::e($fraudCfg['frauddefense_api_key'] ?? '') ?>"
                               placeholder="Paste your FraudDefense.io API key…"
                               style="font-family:monospace">
                        <button type="button" class="btn btn-secondary" onclick="testFraudDefense()" id="btn-test-fd" style="white-space:nowrap">Test</button>
                    </div>
                    <div class="form-hint">Get your API key at <a href="https://frauddefense.io/" target="_blank" rel="noopener">frauddefense.io</a></div>
                    <div id="fd-test-result" style="display:none;margin-top:8px;padding:8px 12px;border-radius:6px;font-size:13px"></div>
                </div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label>Flag Threshold (fraud score)</label>
                    <input type="number" name="frauddefense_flag_threshold" class="form-control" min="1" max="100"
                           value="<?= (int)($fraudCfg['frauddefense_flag_threshold'] ?? 30) ?>">
                    <div class="form-hint">Flag if score &ge; this (0–100)</div>
                </div>
                <div class="form-group">
                    <label>Block Threshold (fraud score)</label>
                    <input type="number" name="frauddefense_block_threshold" class="form-control" min="1" max="100"
                           value="<?= (int)($fraudCfg['frauddefense_block_threshold'] ?? 50) ?>">
                    <div class="form-hint">Block if score &ge; this (auto_block only)</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save FraudDefense Settings</button>
        </form>
    </div>
</div>

<!-- ── FraudLabs Pro Settings ──────────────────────────────────────────── -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title">&#129302; FraudLabs Pro Settings</span></div>
    <div class="card-body" style="max-width:680px">
        <form method="POST" id="fraudlabspro-settings-form">
            <?= Helpers::csrf() ?>
            <input type="hidden" name="action" value="save_fraudlabspro_settings">

            <?php $flpCurrentMode = $fraudCfg['fraudlabspro_mode'] ?? 'score_only'; ?>
            <div style="margin-bottom:16px">
                <p style="font-size:13px;font-weight:700;color:var(--text-muted);margin:0 0 8px">Detection Mode</p>
                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $flpCurrentMode==='score_only' ? '#0891b2' : 'var(--border)' ?>;background:<?= $flpCurrentMode==='score_only' ? '#ecfeff' : 'var(--bg-subtle,#f8f9fa)' ?>;margin-bottom:6px" id="lbl-flp-score">
                    <input type="radio" name="fraudlabspro_mode" value="score_only" <?= $flpCurrentMode==='score_only' ? 'checked' : '' ?> onchange="updateFlpModeUI()" style="margin-top:3px;flex-shrink:0">
                    <div>
                        <strong>&#128270; Score Only</strong>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Record fraud score on every conversion. Nothing is blocked automatically.</div>
                    </div>
                </label>
                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;border:2px solid <?= $flpCurrentMode==='auto_block' ? 'var(--primary,#4f46e5)' : 'var(--border)' ?>;background:<?= $flpCurrentMode==='auto_block' ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)' ?>" id="lbl-flp-block">
                    <input type="radio" name="fraudlabspro_mode" value="auto_block" <?= $flpCurrentMode==='auto_block' ? 'checked' : '' ?> onchange="updateFlpModeUI()" style="margin-top:3px;flex-shrink:0">
                    <div>
                        <strong>&#128683; Auto Block</strong>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Block conversions with fraud_score &ge; block threshold. Flag those in the medium range.</div>
                    </div>
                </label>
            </div>

            <div style="background:var(--bg-subtle,#f8f9fa);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:14px">
                <div class="form-check mb-2">
                    <input type="checkbox" name="fraudlabspro_enabled" id="fraudlabsproEnabled"
                           <?= ($fraudCfg['fraudlabspro_enabled'] ?? false) ? 'checked' : '' ?>>
                    <label for="fraudlabsproEnabled"><strong>Enable FraudLabs Pro fraud detection</strong></label>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label>API Key</label>
                    <div style="display:flex;gap:8px">
                        <input type="text" name="fraudlabspro_api_key" id="fraudlabspro-api-key-input" class="form-control"
                               value="<?= Helpers::e($fraudCfg['fraudlabspro_api_key'] ?? '') ?>"
                               placeholder="Paste your FraudLabs Pro API key…"
                               style="font-family:monospace">
                        <button type="button" class="btn btn-secondary" onclick="testFraudLabsPro()" id="btn-test-flp" style="white-space:nowrap">Test</button>
                    </div>
                    <div class="form-hint">Get your free API key at <a href="https://www.fraudlabspro.com" target="_blank" rel="noopener">fraudlabspro.com</a></div>
                    <div id="flp-test-result" style="display:none;margin-top:8px;padding:8px 12px;border-radius:6px;font-size:13px"></div>
                </div>
            </div>

            <div class="grid-2" style="gap:16px;margin-bottom:16px">
                <div class="form-group">
                    <label>Flag Threshold (fraud score)</label>
                    <input type="number" name="fraudlabspro_flag_threshold" class="form-control" min="1" max="100"
                           value="<?= (int)($fraudCfg['fraudlabspro_flag_threshold'] ?? 40) ?>">
                    <div class="form-hint">Flag if score &ge; this (0–100)</div>
                </div>
                <div class="form-group">
                    <label>Block Threshold (fraud score)</label>
                    <input type="number" name="fraudlabspro_block_threshold" class="form-control" min="1" max="100"
                           value="<?= (int)($fraudCfg['fraudlabspro_block_threshold'] ?? 75) ?>">
                    <div class="form-hint">Block if score &ge; this (auto_block only)</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save FraudLabs Pro Settings</button>
        </form>
    </div>
</div>

<?php if (($fraudCfg['mode'] ?? 'score_only') === 'score_only'): ?>
<!-- ── Score-Only Conversion Monitoring Panel ───────────────────────────── -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
        <div>
            <span class="card-title">&#128270; Score-Only Conversion Monitor</span>
            <span style="font-size:12px;color:#0891b2;margin-left:8px;background:#ecfeff;border:1px solid #0891b2;border-radius:4px;padding:2px 8px">Score-Only Mode Active</span>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <button onclick="sortConvTable('date')"  id="sort-date"  class="btn btn-sm btn-primary">Latest First</button>
            <button onclick="sortConvTable('score')" id="sort-score" class="btn btn-sm btn-secondary">Highest Score</button>
            <a href="/admin/fraud?export=conversion_csv" class="btn btn-sm btn-secondary">&#8595; Export CSV</a>
        </div>
    </div>
    <?php if (empty($scoredConversions)): ?>
    <div style="padding:32px;text-align:center;color:var(--text-muted)">
        No conversions recorded yet. Fraud scores appear here automatically after each conversion.
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table id="tbl-scored-convs">
            <thead>
                <tr>
                    <th>Conversion ID</th><th>Affiliate</th><th>Offer</th><th>Click ID</th>
                    <th>IP Address</th><th>Fraud Score</th><th>Status</th><th>Payout</th><th>Date</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($scoredConversions as $sc):
                $fs = (int)($sc['fraud_score'] ?? 0);
                $bm = ['approved'=>'success','pending'=>'warning','rejected'=>'danger'];
            ?>
            <tr data-date="<?= strtotime($sc['converted_at']) ?>" data-score="<?= $fs ?>">
                <td style="font-family:monospace;font-size:11px"><?= substr(Helpers::e($sc['conversion_id']),0,8) ?>…</td>
                <td>
                    <div class="fw-bold"><?= Helpers::e($sc['aff_name']) ?></div>
                    <div class="text-sm text-muted"><?= Helpers::e($sc['affiliate_code']) ?> &middot; ID:<?= (int)$sc['affiliate_id'] ?></div>
                </td>
                <td class="text-sm"><?= Helpers::e($sc['offer_name']) ?> <div class="text-muted" style="font-size:11px">ID:<?= (int)$sc['offer_id'] ?></div></td>
                <td style="font-family:monospace;font-size:11px"><?= substr(Helpers::e($sc['click_id']),0,8) ?>…</td>
                <td style="font-family:monospace;font-size:12px"><?= Helpers::e($sc['ip_address'] ?: '—') ?></td>
                <td style="text-align:center">
                    <?php if ($fs >= 75): ?>
                    <span class="badge badge-danger"><?= $fs ?></span>
                    <?php elseif ($fs >= 40): ?>
                    <span class="badge badge-warning"><?= $fs ?></span>
                    <?php elseif ($fs > 0): ?>
                    <span class="badge badge-muted"><?= $fs ?></span>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:11px">—</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-<?= $bm[$sc['status']] ?? 'muted' ?>"><?= $sc['status'] ?></span></td>
                <td class="fw-bold">$<?= number_format($sc['payout'],2) ?></td>
                <td class="text-sm text-muted"><?= date('M j, H:i', strtotime($sc['converted_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:5px;flex-wrap:wrap">
                    <?php if ($sc['status'] !== 'approved'): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Approve this conversion?')">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="update_conv_status">
                        <input type="hidden" name="conversion_id" value="<?= Helpers::e($sc['conversion_id']) ?>">
                        <input type="hidden" name="conv_status" value="approved">
                        <input type="hidden" name="redirect_back" value="/admin/fraud?<?= Helpers::e($_SERVER['QUERY_STRING'] ?? '') ?>">
                        <button class="btn btn-success btn-sm">&#10003; Approve</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($sc['status'] !== 'rejected'): ?>
                    <button type="button" class="btn btn-danger btn-sm"
                            onclick="openRejectModal('<?= Helpers::e($sc['conversion_id']) ?>')">&#10007; Reject</button>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── IPQuery.io Full IP Analysis Report ─────────────────────────────── -->
<?php if (!empty($ipqueryReport)):
    // Pre-compute summary counts for the filter bar
    $iqCountHigh  = count(array_filter($ipqueryReport, fn($r) => ($r['ipquery_risk_level'] ?? '') === 'high'));
    $iqCountMed   = count(array_filter($ipqueryReport, fn($r) => ($r['ipquery_risk_level'] ?? '') === 'medium'));
    $iqCountVpn   = count(array_filter($ipqueryReport, fn($r) => !empty($r['ipquery_vpn'])));
    $iqCountProxy = count(array_filter($ipqueryReport, fn($r) => !empty($r['ipquery_proxy'])));
    $iqCountTor   = count(array_filter($ipqueryReport, fn($r) => !empty($r['ipquery_tor'])));
    $iqCountDc    = count(array_filter($ipqueryReport, fn($r) => !empty($r['ipquery_datacenter'])));
?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <span class="card-title">&#127759; IPQuery.io — Full IP Analysis Report</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($ipqueryReport) ?> conversions scored &middot; sorted by risk score</span>
    </div>
    <!-- Filter bar -->
    <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;flex-wrap:wrap;gap:6px;align-items:center">
        <span style="font-size:12px;font-weight:600;color:var(--text-muted);margin-right:4px">Filter:</span>
        <button type="button" id="iq-filter-all"      onclick="iqFilter('all')"      class="btn btn-sm btn-primary"    style="font-size:11px">All (<?= count($ipqueryReport) ?>)</button>
        <button type="button" id="iq-filter-high"     onclick="iqFilter('high')"     class="btn btn-sm btn-secondary"  style="font-size:11px;background:#fee2e2;color:#b91c1c;border-color:#fca5a5">&#10060; High Risk (<?= $iqCountHigh ?>)</button>
        <button type="button" id="iq-filter-medium"   onclick="iqFilter('medium')"   class="btn btn-sm btn-secondary"  style="font-size:11px;background:#fef3c7;color:#92400e;border-color:#fcd34d">&#9888; Medium Risk (<?= $iqCountMed ?>)</button>
        <button type="button" id="iq-filter-vpn"      onclick="iqFilter('vpn')"      class="btn btn-sm btn-secondary"  style="font-size:11px;background:#ede9fe;color:#7c3aed;border-color:#c4b5fd">VPN (<?= $iqCountVpn ?>)</button>
        <button type="button" id="iq-filter-proxy"    onclick="iqFilter('proxy')"    class="btn btn-sm btn-secondary"  style="font-size:11px;background:#fef3c7;color:#b45309;border-color:#fcd34d">PROXY (<?= $iqCountProxy ?>)</button>
        <button type="button" id="iq-filter-tor"      onclick="iqFilter('tor')"      class="btn btn-sm btn-secondary"  style="font-size:11px;background:#fee2e2;color:#dc2626;border-color:#fca5a5">TOR (<?= $iqCountTor ?>)</button>
        <button type="button" id="iq-filter-dc"       onclick="iqFilter('dc')"       class="btn btn-sm btn-secondary"  style="font-size:11px;background:#f0f9ff;color:#0369a1;border-color:#7dd3fc">Datacenter (<?= $iqCountDc ?>)</button>
    </div>
    <div class="table-wrap">
        <table id="tbl-ipquery-report">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Country / City</th>
                    <th>ISP / ASN</th>
                    <th>Risk Score</th>
                    <th>Flags</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>Conv Status</th>
                    <th>Payout</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ipqueryReport as $iq):
                $iqScore = (int)($iq['ipquery_risk_score'] ?? 0);
                // Re-apply same thresholds as PHP for display consistency
                $iqLevel = 'low';
                if ($iqScore > 70 || !empty($iq['ipquery_tor']) || (!empty($iq['ipquery_vpn']) && !empty($iq['ipquery_proxy']))) {
                    $iqLevel = 'high';
                } elseif ($iqScore >= 30 || !empty($iq['ipquery_vpn']) || !empty($iq['ipquery_proxy']) || !empty($iq['ipquery_datacenter'])) {
                    $iqLevel = 'medium';
                }
                $iqScoreStyle = $iqLevel === 'high'
                    ? 'background:#fee2e2;color:#b91c1c'
                    : ($iqLevel === 'medium' ? 'background:#fef3c7;color:#92400e' : 'background:#d1fae5;color:#065f46');
                $convBadge = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
                // Data attributes for JS filtering
                $iqFlags = implode(' ', array_filter([
                    !empty($iq['ipquery_vpn'])        ? 'vpn'   : '',
                    !empty($iq['ipquery_proxy'])      ? 'proxy' : '',
                    !empty($iq['ipquery_tor'])        ? 'tor'   : '',
                    !empty($iq['ipquery_datacenter']) ? 'dc'    : '',
                ]));
            ?>
            <tr data-level="<?= $iqLevel ?>" data-flags="<?= $iqFlags ?>" data-score="<?= $iqScore ?>" class="iq-row">
                <td style="font-family:monospace;font-size:12px;white-space:nowrap">
                    <?= Helpers::e($iq['ip_address'] ?: '—') ?>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px"><?= Helpers::e(substr($iq['conversion_id'],0,8)) ?>…</div>
                </td>
                <td style="font-size:12px">
                    <?php if (!empty($iq['ipquery_country_code'])): ?>
                    <span title="<?= Helpers::e($iq['ipquery_country'] ?? '') ?>" style="font-weight:600">
                        <?= Helpers::e($iq['ipquery_country_code']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($iq['ipquery_city'])): ?>
                    <div style="font-size:11px;color:var(--text-muted)"><?= Helpers::e($iq['ipquery_city']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-size:11px;max-width:180px">
                    <?php if (!empty($iq['ipquery_isp'])): ?>
                    <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:175px" title="<?= Helpers::e($iq['ipquery_isp']) ?>">
                        <?= Helpers::e($iq['ipquery_isp']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($iq['ipquery_asn'])): ?>
                    <div style="color:var(--text-muted)"><?= Helpers::e($iq['ipquery_asn']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="text-align:center">
                    <span style="display:inline-block;font-size:14px;font-weight:800;padding:3px 10px;border-radius:20px;<?= $iqScoreStyle ?>">
                        <?= $iqScore ?>
                    </span>
                    <div style="font-size:10px;margin-top:2px;color:var(--text-muted);text-transform:uppercase"><?= $iqLevel ?></div>
                </td>
                <td style="white-space:nowrap;font-size:11px">
                    <?php if ($iq['ipquery_vpn']):       ?><span style="background:#ede9fe;color:#7c3aed;padding:1px 5px;border-radius:4px;margin:1px 1px 1px 0;display:inline-block">VPN</span><?php endif; ?>
                    <?php if ($iq['ipquery_proxy']):     ?><span style="background:#fef3c7;color:#b45309;padding:1px 5px;border-radius:4px;margin:1px 1px 1px 0;display:inline-block">PROXY</span><?php endif; ?>
                    <?php if ($iq['ipquery_tor']):       ?><span style="background:#fee2e2;color:#dc2626;padding:1px 5px;border-radius:4px;margin:1px 1px 1px 0;display:inline-block">TOR</span><?php endif; ?>
                    <?php if ($iq['ipquery_datacenter']): ?><span style="background:#f0f9ff;color:#0369a1;padding:1px 5px;border-radius:4px;margin:1px 1px 1px 0;display:inline-block">DC</span><?php endif; ?>
                    <?php if ($iq['ipquery_mobile']):    ?><span style="background:#f0fdf4;color:#166534;padding:1px 5px;border-radius:4px;margin:1px 1px 1px 0;display:inline-block">MOB</span><?php endif; ?>
                    <?php if (!$iq['ipquery_vpn'] && !$iq['ipquery_proxy'] && !$iq['ipquery_tor'] && !$iq['ipquery_datacenter']): ?>
                    <span style="color:var(--text-muted)">Clean</span>
                    <?php endif; ?>
                </td>
                <td class="text-sm">
                    <div class="fw-bold"><?= Helpers::e($iq['aff_name']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= Helpers::e($iq['affiliate_code']) ?></div>
                </td>
                <td class="text-sm"><?= Helpers::e($iq['offer_name'] ?? '—') ?></td>
                <td>
                    <span class="badge badge-<?= $convBadge[$iq['status']] ?? 'muted' ?>"><?= $iq['status'] ?></span>
                </td>
                <td class="fw-bold text-sm">$<?= number_format((float)$iq['payout'], 2) ?></td>
                <td class="text-sm text-muted" style="white-space:nowrap"><?= date('M j, H:i', strtotime($iq['converted_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title">&#127759; IPQuery.io — Full IP Analysis Report</span></div>
    <div style="padding:32px;text-align:center;color:var(--text-muted)">
        No IPQuery.io scores yet. Enable IPQuery.io above and new conversions will be automatically analyzed.
    </div>
</div>
<?php endif; ?>

<!-- ── FraudDefense.io Score Report ──────────────────────────────────────── -->
<?php if (!empty($fraudDefenseReport)):
    $fdCountHigh = count(array_filter($fraudDefenseReport, fn($r) => (int)($r['frauddefense_score'] ?? 0) >= 50));
    $fdCountMed  = count(array_filter($fraudDefenseReport, fn($r) => (int)($r['frauddefense_score'] ?? 0) >= 30 && (int)($r['frauddefense_score'] ?? 0) < 50));
    $fdCountLow  = count(array_filter($fraudDefenseReport, fn($r) => (int)($r['frauddefense_score'] ?? 0) < 30));
?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <span class="card-title">&#128737; FraudDefense.io — Conversion Score Report</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($fraudDefenseReport) ?> conversions scored &middot; sorted by risk score</span>
    </div>
    <!-- Filter bar -->
    <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;flex-wrap:wrap;gap:6px;align-items:center">
        <span style="font-size:12px;font-weight:600;color:var(--text-muted);margin-right:4px">Filter:</span>
        <button type="button" id="fd-filter-all"  onclick="fdFilter('all')"  class="btn btn-sm btn-primary"   style="font-size:11px">All (<?= count($fraudDefenseReport) ?>)</button>
        <button type="button" id="fd-filter-high" onclick="fdFilter('high')" class="btn btn-sm btn-secondary" style="font-size:11px;background:#fee2e2;color:#b91c1c;border-color:#fca5a5">&#10060; High Risk &ge;50 (<?= $fdCountHigh ?>)</button>
        <button type="button" id="fd-filter-med"  onclick="fdFilter('med')"  class="btn btn-sm btn-secondary" style="font-size:11px;background:#fef3c7;color:#92400e;border-color:#fcd34d">&#9888; Medium 30–49 (<?= $fdCountMed ?>)</button>
        <button type="button" id="fd-filter-low"  onclick="fdFilter('low')"  class="btn btn-sm btn-secondary" style="font-size:11px;background:#d1fae5;color:#065f46;border-color:#6ee7b7">&#10003; Low &lt;30 (<?= $fdCountLow ?>)</button>
    </div>
    <div class="table-wrap">
        <table id="tbl-fd-report">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Risk Score</th>
                    <th>Status</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>Conv Status</th>
                    <th>Payout</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fraudDefenseReport as $fd):
                $fdScore = (int)($fd['frauddefense_score'] ?? 0);
                $fdLevel = $fdScore >= 50 ? 'high' : ($fdScore >= 30 ? 'medium' : 'low');
                $fdScoreStyle = $fdLevel === 'high'
                    ? 'background:#fee2e2;color:#b91c1c'
                    : ($fdLevel === 'medium' ? 'background:#fef3c7;color:#92400e' : 'background:#d1fae5;color:#065f46');
                $convBadge = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
            ?>
            <tr data-fd-score="<?= $fdScore ?>" class="fd-row">
                <td style="font-family:monospace;font-size:12px;white-space:nowrap">
                    <?= Helpers::e($fd['ip_address'] ?: '—') ?>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px"><?= Helpers::e(substr($fd['conversion_id'],0,8)) ?>…</div>
                </td>
                <td style="text-align:center">
                    <span style="display:inline-block;font-size:14px;font-weight:800;padding:3px 10px;border-radius:20px;<?= $fdScoreStyle ?>">
                        <?= $fdScore ?>
                    </span>
                    <div style="font-size:10px;margin-top:2px;color:var(--text-muted);text-transform:uppercase"><?= $fdLevel ?></div>
                </td>
                <td>
                    <?php $st = $fd['frauddefense_status'] ?? 'allowed'; ?>
                    <span style="font-size:11px;padding:2px 7px;border-radius:4px;background:<?= $st==='blocked'?'#fee2e2':($st==='flagged'?'#fef3c7':'#d1fae5') ?>;color:<?= $st==='blocked'?'#b91c1c':($st==='flagged'?'#92400e':'#065f46') ?>">
                        <?= Helpers::e($st) ?>
                    </span>
                </td>
                <td class="text-sm">
                    <div class="fw-bold"><?= Helpers::e($fd['aff_name']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= Helpers::e($fd['affiliate_code']) ?></div>
                </td>
                <td class="text-sm"><?= Helpers::e($fd['offer_name'] ?? '—') ?></td>
                <td>
                    <span class="badge badge-<?= $convBadge[$fd['status']] ?? 'muted' ?>"><?= $fd['status'] ?></span>
                </td>
                <td class="fw-bold text-sm">$<?= number_format((float)$fd['payout'], 2) ?></td>
                <td class="text-sm text-muted" style="white-space:nowrap"><?= date('M j, H:i', strtotime($fd['converted_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title">&#128737; FraudDefense.io — Conversion Score Report</span></div>
    <div style="padding:32px;text-align:center;color:var(--text-muted)">
        No FraudDefense.io scores yet. Enable FraudDefense.io and save settings — new conversions will be automatically scored.
    </div>
</div>
<?php endif; ?>

<!-- ── FraudLabs Pro Score Report ───────────────────────────────────────── -->
<?php if (!empty($fraudLabsProReport)):
    $flpCountHigh    = count(array_filter($fraudLabsProReport, fn($r) => (int)($r['fraudlabspro_score'] ?? 0) >= 75));
    $flpCountMed     = count(array_filter($fraudLabsProReport, fn($r) => (int)($r['fraudlabspro_score'] ?? 0) >= 40 && (int)($r['fraudlabspro_score'] ?? 0) < 75));
    $flpCountLow     = count(array_filter($fraudLabsProReport, fn($r) => (int)($r['fraudlabspro_score'] ?? 0) < 40));
    $flpCountReject  = count(array_filter($fraudLabsProReport, fn($r) => ($r['fraudlabspro_flp_status'] ?? '') === 'REJECT'));
    $flpCountReview  = count(array_filter($fraudLabsProReport, fn($r) => ($r['fraudlabspro_flp_status'] ?? '') === 'REVIEW'));
?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
        <span class="card-title">&#129302; FraudLabs Pro — Conversion Score Report</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($fraudLabsProReport) ?> conversions scored &middot; sorted by risk score</span>
    </div>
    <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;flex-wrap:wrap;gap:6px;align-items:center">
        <span style="font-size:12px;font-weight:600;color:var(--text-muted);margin-right:4px">Filter:</span>
        <button type="button" id="flp-filter-all"    onclick="flpFilter('all')"    class="btn btn-sm btn-primary"   style="font-size:11px">All (<?= count($fraudLabsProReport) ?>)</button>
        <button type="button" id="flp-filter-high"   onclick="flpFilter('high')"   class="btn btn-sm btn-secondary" style="font-size:11px;background:#fee2e2;color:#b91c1c;border-color:#fca5a5">&#10060; High &ge;75 (<?= $flpCountHigh ?>)</button>
        <button type="button" id="flp-filter-med"    onclick="flpFilter('med')"    class="btn btn-sm btn-secondary" style="font-size:11px;background:#fef3c7;color:#92400e;border-color:#fcd34d">&#9888; Medium 40–74 (<?= $flpCountMed ?>)</button>
        <button type="button" id="flp-filter-low"    onclick="flpFilter('low')"    class="btn btn-sm btn-secondary" style="font-size:11px;background:#d1fae5;color:#065f46;border-color:#6ee7b7">&#10003; Low &lt;40 (<?= $flpCountLow ?>)</button>
        <button type="button" id="flp-filter-reject" onclick="flpFilter('reject')" class="btn btn-sm btn-secondary" style="font-size:11px;background:#fee2e2;color:#b91c1c;border-color:#fca5a5">REJECT (<?= $flpCountReject ?>)</button>
        <button type="button" id="flp-filter-review" onclick="flpFilter('review')" class="btn btn-sm btn-secondary" style="font-size:11px;background:#fef3c7;color:#92400e;border-color:#fcd34d">REVIEW (<?= $flpCountReview ?>)</button>
    </div>
    <div class="table-wrap">
        <table id="tbl-flp-report">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Risk Score</th>
                    <th>FLP Status</th>
                    <th>Internal Status</th>
                    <th>Affiliate</th>
                    <th>Offer</th>
                    <th>Conv Status</th>
                    <th>Payout</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fraudLabsProReport as $flp):
                $flpScore    = (int)($flp['fraudlabspro_score']      ?? 0);
                $flpFlpSt    = strtoupper($flp['fraudlabspro_flp_status'] ?? '');
                $flpIntSt    = $flp['fraudlabspro_status'] ?? 'allowed';
                $flpLevel    = $flpScore >= 75 ? 'high' : ($flpScore >= 40 ? 'medium' : 'low');
                $flpScoreStyle = $flpLevel === 'high'
                    ? 'background:#fee2e2;color:#b91c1c'
                    : ($flpLevel === 'medium' ? 'background:#fef3c7;color:#92400e' : 'background:#d1fae5;color:#065f46');
                $flpStColor  = $flpFlpSt === 'REJECT' ? '#b91c1c' : ($flpFlpSt === 'REVIEW' ? '#92400e' : '#065f46');
                $flpStBg     = $flpFlpSt === 'REJECT' ? '#fee2e2' : ($flpFlpSt === 'REVIEW' ? '#fef3c7' : '#d1fae5');
                $convBadge   = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','chargebacked'=>'muted'];
            ?>
            <tr data-flp-score="<?= $flpScore ?>" data-flp-status="<?= strtolower($flpFlpSt) ?>" class="flp-row">
                <td style="font-family:monospace;font-size:12px;white-space:nowrap">
                    <?= Helpers::e($flp['ip_address'] ?: '—') ?>
                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px"><?= Helpers::e(substr($flp['conversion_id'],0,8)) ?>…</div>
                </td>
                <td style="text-align:center">
                    <span style="display:inline-block;font-size:14px;font-weight:800;padding:3px 10px;border-radius:20px;<?= $flpScoreStyle ?>">
                        <?= $flpScore ?>
                    </span>
                    <div style="font-size:10px;margin-top:2px;color:var(--text-muted);text-transform:uppercase"><?= $flpLevel ?></div>
                </td>
                <td style="text-align:center">
                    <?php if ($flpFlpSt !== ''): ?>
                    <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;background:<?= $flpStBg ?>;color:<?= $flpStColor ?>">
                        <?= Helpers::e($flpFlpSt) ?>
                    </span>
                    <?php else: ?>
                    <span style="color:var(--text-muted)">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="font-size:11px;padding:2px 7px;border-radius:4px;background:<?= $flpIntSt==='blocked'?'#fee2e2':($flpIntSt==='flagged'?'#fef3c7':'#d1fae5') ?>;color:<?= $flpIntSt==='blocked'?'#b91c1c':($flpIntSt==='flagged'?'#92400e':'#065f46') ?>">
                        <?= Helpers::e($flpIntSt) ?>
                    </span>
                </td>
                <td class="text-sm">
                    <div class="fw-bold"><?= Helpers::e($flp['aff_name']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= Helpers::e($flp['affiliate_code']) ?></div>
                </td>
                <td class="text-sm"><?= Helpers::e($flp['offer_name'] ?? '—') ?></td>
                <td>
                    <span class="badge badge-<?= $convBadge[$flp['status']] ?? 'muted' ?>"><?= $flp['status'] ?></span>
                </td>
                <td class="fw-bold text-sm">$<?= number_format((float)$flp['payout'], 2) ?></td>
                <td class="text-sm text-muted" style="white-space:nowrap"><?= date('M j, H:i', strtotime($flp['converted_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><span class="card-title">&#129302; FraudLabs Pro — Conversion Score Report</span></div>
    <div style="padding:32px;text-align:center;color:var(--text-muted)">
        No FraudLabs Pro scores yet. Enable FraudLabs Pro and save settings — new conversions will be automatically scored.
    </div>
</div>
<?php endif; ?>

<!-- Fraud Clicks Table -->
<div class="card">
    <div class="card-header"><span class="card-title">Flagged Clicks</span></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Click ID</th><th>IP</th><th>Country</th><th>Score</th><th>Offer</th><th>Affiliate</th><th>Status</th><th>Time</th><th>Action</th></tr></thead>
            <tbody>
            <?php if (empty($fraudClicks)): ?>
            <tr><td colspan="9" class="text-center text-muted" style="padding:24px">No flagged clicks</td></tr>
            <?php else: ?>
            <?php foreach ($fraudClicks as $c): ?>
            <tr>
                <td style="font-family:monospace;font-size:11px"><?= substr(Helpers::e($c['click_id']),0,8) ?>…</td>
                <td style="font-family:monospace;font-size:12px"><?= Helpers::e($c['ip_address']) ?></td>
                <td><?= Helpers::e($c['country'] ?: '—') ?></td>
                <td><span class="badge badge-<?= $c['fraud_score']>=90?'danger':($c['fraud_score']>=75?'warning':'muted') ?>"><?= (int)$c['fraud_score'] ?></span></td>
                <td class="text-sm"><?= Helpers::e($c['offer_name']) ?></td>
                <td class="text-sm"><?= Helpers::e($c['aff_name']) ?></td>
                <td><span class="badge badge-<?= $c['status']==='blocked'?'danger':'warning' ?>"><?= $c['status'] ?></span></td>
                <td class="text-sm text-muted"><?= date('M j H:i', strtotime($c['clicked_at'])) ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <?= Helpers::csrf() ?>
                        <input type="hidden" name="action" value="mark_click">
                        <input type="hidden" name="click_id" value="<?= Helpers::e($c['click_id']) ?>">
                        <?php if ($c['status'] !== 'valid'): ?>
                        <input type="hidden" name="click_status" value="valid">
                        <button class="btn btn-success btn-sm">Clear</button>
                        <?php else: ?>
                        <input type="hidden" name="click_status" value="blocked">
                        <button class="btn btn-danger btn-sm">Block</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateModeUI() {
    var isBlock = document.querySelector('input[name="fraud_mode"][value="block"]').checked;
    document.getElementById('lbl-mode-block').style.borderColor = isBlock ? 'var(--primary,#4f46e5)' : 'var(--border)';
    document.getElementById('lbl-mode-block').style.background  = isBlock ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)';
    document.getElementById('lbl-mode-score').style.borderColor = !isBlock ? '#0891b2' : 'var(--border)';
    document.getElementById('lbl-mode-score').style.background  = !isBlock ? '#ecfeff' : 'var(--bg-subtle,#f8f9fa)';
    var convRow  = document.getElementById('row-check-on-conv');
    var scoreNote = document.getElementById('row-score-only-note');
    if (convRow)   convRow.style.display   = isBlock ? '' : 'none';
    if (scoreNote) scoreNote.style.display = isBlock ? 'none' : '';
}

function testIpqsConnection() {
    var btn     = document.getElementById('btn-test-ipqs');
    var apiKey  = document.getElementById('ipqs-api-key-input').value.trim();
    var result  = document.getElementById('ipqs-test-result');
    if (!apiKey) { alert('Enter your API key first.'); return; }

    btn.disabled    = true;
    btn.innerHTML   = 'Testing&hellip;';
    result.style.display = 'none';

    var fd = new FormData();
    fd.append('action', 'test_ipqs');
    fd.append('ipqs_api_key', apiKey);

    fetch('/admin/fraud', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            result.style.display     = '';
            result.style.background  = d.ok ? '#dcfce7' : '#fee2e2';
            result.style.border      = '1px solid ' + (d.ok ? '#16a34a' : '#dc2626');
            result.style.color       = d.ok ? '#15803d' : '#dc2626';
            result.textContent       = d.message;
        })
        .catch(function() {
            result.style.display = '';
            result.style.background = '#fee2e2';
            result.style.border  = '1px solid #dc2626';
            result.style.color   = '#dc2626';
            result.textContent   = 'Request failed — check browser console.';
        })
        .finally(function() {
            btn.disabled  = false;
            btn.innerHTML = 'Test Connection';
        });
}

function _showTestResult(elId, d) {
    var el = document.getElementById(elId);
    if (!el) return;
    el.style.display    = '';
    el.style.background = d.ok ? '#dcfce7' : '#fee2e2';
    el.style.border     = '1px solid ' + (d.ok ? '#16a34a' : '#dc2626');
    el.style.color      = d.ok ? '#15803d' : '#dc2626';
    el.textContent      = d.message;
}
function _postTest(action, formData, btnId, btnLabel, resultId) {
    var btn = document.getElementById(btnId);
    if (btn) { btn.disabled = true; btn.innerHTML = 'Testing&hellip;'; }
    document.getElementById(resultId).style.display = 'none';
    fetch('/admin/fraud', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(d) { _showTestResult(resultId, d); })
        .catch(function()  { _showTestResult(resultId, {ok:false, message:'Request failed — check browser console.'}); })
        .finally(function() { if (btn) { btn.disabled = false; btn.innerHTML = btnLabel; } });
}

function testIPQuery() {
    var fd = new FormData();
    fd.append('action', 'test_ipquery');
    _postTest('test_ipquery', fd, 'btn-test-ipquery', 'Test Connection', 'ipquery-test-result');
}

function testScamalytics() {
    var user   = (document.getElementById('scamalytics-user-input')   || {}).value || '';
    var server = (document.getElementById('scamalytics-server-input') || {}).value || '';
    var apiKey = (document.getElementById('scamalytics-api-key-input')|| {}).value || '';
    if (!user.trim())   { alert('Enter your Scamalytics API User first.');          return; }
    if (!server.trim()) { alert('Enter your Scamalytics API Server number first.'); return; }
    if (!apiKey.trim()) { alert('Enter your Scamalytics API Key first.');           return; }
    var fd = new FormData();
    fd.append('action',               'test_scamalytics');
    fd.append('scamalytics_user',     user.trim());
    fd.append('scamalytics_server',   server.trim());
    fd.append('scamalytics_api_key',  apiKey.trim());
    _postTest('test_scamalytics', fd, 'btn-test-scam', 'Test', 'scam-test-result');
}

function updateIpqModeUI() {
    var isBlock = document.querySelector('input[name="ipquery_mode"][value="auto_block"]').checked;
    document.getElementById('lbl-ipq-score').style.borderColor = !isBlock ? '#0891b2' : 'var(--border)';
    document.getElementById('lbl-ipq-score').style.background  = !isBlock ? '#ecfeff'  : 'var(--bg-subtle,#f8f9fa)';
    document.getElementById('lbl-ipq-block').style.borderColor = isBlock  ? 'var(--primary,#4f46e5)' : 'var(--border)';
    document.getElementById('lbl-ipq-block').style.background  = isBlock  ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)';
}

function updateScamModeUI() {
    var isBlock = document.querySelector('input[name="scamalytics_mode"][value="auto_block"]').checked;
    document.getElementById('lbl-scam-score').style.borderColor = !isBlock ? '#0891b2' : 'var(--border)';
    document.getElementById('lbl-scam-score').style.background  = !isBlock ? '#ecfeff'  : 'var(--bg-subtle,#f8f9fa)';
    document.getElementById('lbl-scam-block').style.borderColor = isBlock  ? 'var(--primary,#4f46e5)' : 'var(--border)';
    document.getElementById('lbl-scam-block').style.background  = isBlock  ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)';
}

function testProxyCheck() {
    var apiKey = (document.getElementById('proxycheck-api-key-input') || {}).value || '';
    if (!apiKey.trim()) { alert('Enter your ProxyCheck.io API key first.'); return; }
    var fd = new FormData();
    fd.append('action', 'test_proxycheck');
    fd.append('proxycheck_api_key', apiKey.trim());
    _postTest('test_proxycheck', fd, 'btn-test-pc', 'Test', 'pc-test-result');
}

function testBotScout() {
    var apiKey = (document.getElementById('botscout-api-key-input') || {}).value || '';
    if (!apiKey.trim()) { alert('Enter your BotScout API key first.'); return; }
    var fd = new FormData();
    fd.append('action', 'test_botscout');
    fd.append('botscout_api_key', apiKey.trim());
    _postTest('test_botscout', fd, 'btn-test-bs', 'Test', 'bs-test-result');
}

function testFraudDefense() {
    var apiKey = (document.getElementById('frauddefense-api-key-input') || {}).value || '';
    if (!apiKey.trim()) { alert('Enter your FraudDefense.io API key first.'); return; }
    var fd = new FormData();
    fd.append('action', 'test_frauddefense');
    fd.append('frauddefense_api_key', apiKey.trim());
    _postTest('test_frauddefense', fd, 'btn-test-fd', 'Test', 'fd-test-result');
}

function updatePcModeUI() {
    var isBlock = document.querySelector('input[name="proxycheck_mode"][value="auto_block"]').checked;
    document.getElementById('lbl-pc-score').style.borderColor = !isBlock ? '#0891b2' : 'var(--border)';
    document.getElementById('lbl-pc-score').style.background  = !isBlock ? '#ecfeff'  : 'var(--bg-subtle,#f8f9fa)';
    document.getElementById('lbl-pc-block').style.borderColor = isBlock  ? 'var(--primary,#4f46e5)' : 'var(--border)';
    document.getElementById('lbl-pc-block').style.background  = isBlock  ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)';
}

function updateBsModeUI() {
    var isBlock = document.querySelector('input[name="botscout_mode"][value="auto_block"]').checked;
    document.getElementById('lbl-bs-score').style.borderColor = !isBlock ? '#0891b2' : 'var(--border)';
    document.getElementById('lbl-bs-score').style.background  = !isBlock ? '#ecfeff'  : 'var(--bg-subtle,#f8f9fa)';
    document.getElementById('lbl-bs-block').style.borderColor = isBlock  ? 'var(--primary,#4f46e5)' : 'var(--border)';
    document.getElementById('lbl-bs-block').style.background  = isBlock  ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)';
}

function updateFdModeUI() {
    var isBlock = document.querySelector('input[name="frauddefense_mode"][value="auto_block"]').checked;
    document.getElementById('lbl-fd-score').style.borderColor = !isBlock ? '#0891b2' : 'var(--border)';
    document.getElementById('lbl-fd-score').style.background  = !isBlock ? '#ecfeff'  : 'var(--bg-subtle,#f8f9fa)';
    document.getElementById('lbl-fd-block').style.borderColor = isBlock  ? 'var(--primary,#4f46e5)' : 'var(--border)';
    document.getElementById('lbl-fd-block').style.background  = isBlock  ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)';
}

// DataTable for IPQuery report
if ($('#tbl-ipquery-report').length) {
    $('#tbl-ipquery-report').DataTable({
        destroy: true,
        stateSave: true,
        pageLength: 25,
        order: [[3, 'desc']], // sort by risk score desc
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
}

// DataTable for FraudDefense report
if ($('#tbl-fd-report').length) {
    $('#tbl-fd-report').DataTable({
        destroy: true,
        stateSave: true,
        pageLength: 25,
        order: [[1, 'desc']], // sort by risk score desc
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
}

// DataTable for FraudLabs Pro report
if ($('#tbl-flp-report').length) {
    $('#tbl-flp-report').DataTable({
        destroy: true,
        stateSave: true,
        pageLength: 25,
        order: [[1, 'desc']], // sort by risk score desc
        language: { search: 'Search:', lengthMenu: 'Show _MENU_ entries' }
    });
}

// FraudLabs Pro filter buttons
var _flpActiveFilter = 'all';
function flpFilter(mode) {
    _flpActiveFilter = mode;
    ['all','high','med','low','reject','review'].forEach(function(b) {
        var el = document.getElementById('flp-filter-' + b);
        if (!el) return;
        el.classList.toggle('btn-primary',   b === mode);
        el.classList.toggle('btn-secondary', b !== mode);
    });
    document.querySelectorAll('.flp-row').forEach(function(row) {
        var score  = parseInt(row.getAttribute('data-flp-score')  || '0', 10);
        var status = row.getAttribute('data-flp-status') || '';
        var show = false;
        if      (mode === 'all')    show = true;
        else if (mode === 'high')   show = score >= 75;
        else if (mode === 'med')    show = score >= 40 && score < 75;
        else if (mode === 'low')    show = score < 40;
        else if (mode === 'reject') show = status === 'reject';
        else if (mode === 'review') show = status === 'review';
        row.style.display = show ? '' : 'none';
    });
}

function updateFlpModeUI() {
    var isBlock = document.querySelector('input[name="fraudlabspro_mode"][value="auto_block"]').checked;
    document.getElementById('lbl-flp-score').style.borderColor = !isBlock ? '#0891b2' : 'var(--border)';
    document.getElementById('lbl-flp-score').style.background  = !isBlock ? '#ecfeff'  : 'var(--bg-subtle,#f8f9fa)';
    document.getElementById('lbl-flp-block').style.borderColor = isBlock  ? 'var(--primary,#4f46e5)' : 'var(--border)';
    document.getElementById('lbl-flp-block').style.background  = isBlock  ? 'var(--primary-light,#eef2ff)' : 'var(--bg-subtle,#f8f9fa)';
}

function testFraudLabsPro() {
    var apiKey = (document.getElementById('fraudlabspro-api-key-input') || {}).value || '';
    if (!apiKey.trim()) { alert('Enter your FraudLabs Pro API key first.'); return; }
    var fd = new FormData();
    fd.append('action', 'test_fraudlabspro');
    fd.append('fraudlabspro_api_key', apiKey.trim());
    _postTest('test_fraudlabspro', fd, 'btn-test-flp', 'Test', 'flp-test-result');
}

// FraudDefense filter buttons
var _fdActiveFilter = 'all';
function fdFilter(mode) {
    _fdActiveFilter = mode;
    ['all','high','med','low'].forEach(function(b) {
        var el = document.getElementById('fd-filter-' + b);
        if (!el) return;
        el.classList.toggle('btn-primary',   b === mode);
        el.classList.toggle('btn-secondary', b !== mode);
    });
    document.querySelectorAll('.fd-row').forEach(function(row) {
        var score = parseInt(row.getAttribute('data-fd-score') || '0', 10);
        var show = false;
        if (mode === 'all')  show = true;
        else if (mode === 'high') show = score >= 50;
        else if (mode === 'med')  show = score >= 30 && score < 50;
        else if (mode === 'low')  show = score < 30;
        row.style.display = show ? '' : 'none';
    });
}

// IPQuery filter buttons
var _iqActiveFilter = 'all';
function iqFilter(mode) {
    _iqActiveFilter = mode;
    // Update button styles
    var btns = ['all','high','medium','vpn','proxy','tor','dc'];
    btns.forEach(function(b) {
        var el = document.getElementById('iq-filter-' + b);
        if (!el) return;
        el.classList.toggle('btn-primary',   b === mode);
        el.classList.toggle('btn-secondary', b !== mode);
    });
    // Show/hide rows
    document.querySelectorAll('.iq-row').forEach(function(row) {
        var level = row.getAttribute('data-level') || '';
        var flags = row.getAttribute('data-flags') || '';
        var show  = false;
        if (mode === 'all')    show = true;
        else if (mode === 'high')   show = level === 'high';
        else if (mode === 'medium') show = level === 'medium';
        else show = flags.indexOf(mode) !== -1;
        row.style.display = show ? '' : 'none';
    });
}

var _convSortMode = 'date';
function sortConvTable(mode) {
    _convSortMode = mode;
    document.getElementById('sort-date').className  = 'btn btn-sm ' + (mode==='date'  ? 'btn-primary' : 'btn-secondary');
    document.getElementById('sort-score').className = 'btn btn-sm ' + (mode==='score' ? 'btn-primary' : 'btn-secondary');
    var tbody = document.querySelector('#tbl-scored-convs tbody');
    if (!tbody) return;
    var rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort(function(a, b) {
        return (parseFloat(b.getAttribute('data-' + mode)) || 0) - (parseFloat(a.getAttribute('data-' + mode)) || 0);
    });
    rows.forEach(function(r) { tbody.appendChild(r); });
}
</script>

<?php
// Reject-with-reason modal — submits to /admin/fraud (action=update_conv_status, conv_status=rejected).
$rejectFormAction  = '/admin/fraud';
$rejectStatusField = 'conv_status';
$rejectStatusValue = 'rejected';
$rejectExtraHidden = ['action' => 'update_conv_status', 'redirect_back' => '/admin/fraud?' . ($_SERVER['QUERY_STRING'] ?? '')];
require BASE_PATH . '/views/partials/reject_reason_modal.php';
?>

<?php require BASE_PATH . '/views/layouts/admin_footer.php'; ?>
