<?php require BASE_PATH . '/views/layouts/super_admin.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-family: var(--font-heading); font-weight: 700; color: #FFFFFF; margin: 0;">🌐 Tracking Domains & SSL Management</h2>
    <button onclick="document.getElementById('addDomainModal').style.display='flex'" class="btn-premium" style="padding: 10px 20px; font-weight: 600; text-decoration: none;">+ Add Tracking Domain</button>
</div>

<?php if (!empty($success)): ?>
<div class="alert alert-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #34D399; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px;">
    <?= Helpers::e($success) ?>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px;">
    <?= Helpers::e($error) ?>
</div>
<?php endif; ?>

<!-- System Setup Guidelines Card -->
<div class="glass-card" style="margin-bottom: 30px; background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.25);">
    <div class="glass-header">
        <h3 class="glass-title" style="color: #C7D2FE;">📋 Required Server Configurations</h3>
    </div>
    <div style="padding: 24px; font-size: 14px; line-height: 1.6; color: #E2E8F0;">
        <p style="margin: 0 0 16px;">
            To ensure tracking domains resolve properly, the client administrator must create an <strong>A Record</strong> pointing to the server IP:
            <br><strong style="color: #FCA5A5;">Target Server IP: <?= Helpers::e($serverIp) ?></strong>
            <br><br>
            <strong style="color: #FCA5A5;">⚠️ VirtualHost & SSL Automation:</strong> 
            When you upload an SSL certificate, the system will attempt to automatically generate the Nginx or Apache VirtualHost configuration and enforce HTTPS. If the script does not have `exec()` permissions, you must manually point the web server panel (BT Panel/cPanel) to the script directory and apply the custom SSL certificate there.
        </p>
    </div>
</div>

<!-- Domain Health Diagnostics table -->
<div class="glass-card">
    <div class="glass-header">
        <h3 class="glass-title">Assigned Custom Domains &amp; SSL Handshakes</h3>
    </div>
    <div style="padding: 24px; overflow-x: auto;">
        <table class="glass-table" id="domainsTable">
            <thead>
                <tr>
                    <th>Tracking Domain</th>
                    <th>Tenant Network</th>
                    <th>DNS Pointing</th>
                    <th>SSL Status</th>
                    <th>Check Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($domains)): ?>
                    <?php foreach ($domains as $d): ?>
                        <tr>
                            <td>
                                <a href="http://<?= Helpers::e($d['domain']) ?>" target="_blank" style="color: #818CF8; font-weight: 700; text-decoration: none;"><?= Helpers::e($d['domain']) ?></a>
                            </td>
                            <td>
                                <strong><?= Helpers::e($d['company_name'] ?: 'Unassigned') ?></strong>
                            </td>
                            <td>
                                <span style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; background: <?= $d['dns_status'] === 'pointing' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $d['dns_status'] === 'pointing' ? '#34D399' : '#FCA5A5' ?>;">
                                    <?= strtoupper(Helpers::e($d['dns_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <span style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; background: <?= $d['ssl_status'] === 'active' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $d['ssl_status'] === 'active' ? '#34D399' : '#FCA5A5' ?>;">
                                    <?= $d['ssl_status'] === 'active' ? 'HTTPS ACTIVE' : 'NO SSL' ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 12px; color: var(--text-muted);"><?= Helpers::e($d['last_check_at'] ?: 'Never') ?></span>
                                <?php if ($d['check_message']): ?>
                                    <br><span style="font-size: 11px; color: #FCA5A5;" title="<?= Helpers::e($d['check_message']) ?>">⚠ Message</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/super_admin/domains?action=verify&amp;id=<?= $d['id'] ?>" class="btn-premium" style="padding: 6px 14px; font-size: 12px; text-decoration: none; display: inline-block;">🩺 Check</a>
                                <button onclick="openSslModal('<?= Helpers::e($d['domain']) ?>')" style="background: transparent; border: 1px solid #A78BFA; color: #A78BFA; padding: 6px 14px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">🔒 Manage SSL</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Domain Modal -->
<div id="addDomainModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11,9,26,0.8); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 30px; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.4);">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #FFFFFF;">Add Tracking Domain</h3>
        <form action="/super_admin/domains?action=add_domain" method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 14px;">Domain Name (e.g. track.example.com)</label>
                <input type="text" name="domain" required style="width: 100%; padding: 12px; background: rgba(15,12,38,0.5); border: 1px solid var(--border-glass); border-radius: 8px; color: #FFFFFF;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 14px;">Assign to Admin (Tenant)</label>
                <select name="tenant_id" required style="width: 100%; padding: 12px; background: rgba(15,12,38,0.5); border: 1px solid var(--border-glass); border-radius: 8px; color: #FFFFFF;">
                    <option value="">Select an Admin Workspace</option>
                    <?php foreach ($tenants as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= Helpers::e($t['company_name']) ?> (ID: <?= $t['id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('addDomainModal').style.display='none'" style="background: transparent; color: #94A3B8; border: none; cursor: pointer; font-weight: 600; padding: 10px 20px;">Cancel</button>
                <button type="submit" class="btn-premium" style="padding: 10px 20px;">Assign Domain</button>
            </div>
        </form>
    </div>
</div>

<!-- Upload SSL Modal -->
<div id="uploadSslModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11,9,26,0.8); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 30px; border-radius: 12px; width: 100%; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); max-height: 90vh; overflow-y: auto;">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #FFFFFF;">Upload Custom SSL Certificate</h3>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Install a custom SSL certificate for <strong id="sslDomainLabel" style="color: #A78BFA;"></strong>.</p>
        <form action="/super_admin/domains?action=save_ssl" method="POST">
            <input type="hidden" name="domain" id="sslDomainInput" value="">
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 14px;">Certificate (CRT)</label>
                <textarea name="ssl_cert" required style="width: 100%; padding: 12px; background: rgba(15,12,38,0.5); border: 1px solid var(--border-glass); border-radius: 8px; color: #FFFFFF; font-family: monospace; height: 100px; font-size: 12px;" placeholder="-----BEGIN CERTIFICATE-----&#10;...&#10;-----END CERTIFICATE-----"></textarea>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 14px;">Private Key (KEY)</label>
                <textarea name="ssl_key" required style="width: 100%; padding: 12px; background: rgba(15,12,38,0.5); border: 1px solid var(--border-glass); border-radius: 8px; color: #FFFFFF; font-family: monospace; height: 100px; font-size: 12px;" placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"></textarea>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: var(--text-muted); margin-bottom: 8px; font-size: 14px;">CA Bundle (Optional)</label>
                <textarea name="ssl_ca" style="width: 100%; padding: 12px; background: rgba(15,12,38,0.5); border: 1px solid var(--border-glass); border-radius: 8px; color: #FFFFFF; font-family: monospace; height: 80px; font-size: 12px;" placeholder="-----BEGIN CERTIFICATE-----&#10;..."></textarea>
            </div>
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('uploadSslModal').style.display='none'" style="background: transparent; color: #94A3B8; border: none; cursor: pointer; font-weight: 600; padding: 10px 20px;">Cancel</button>
                <button type="submit" class="btn-premium" style="padding: 10px 20px; background: linear-gradient(135deg, #10B981 0%, #059669 100%);">Save & Apply SSL</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#domainsTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 10,
        "language": {
            "search": "Filter Domain:"
        }
    });
});

function openSslModal(domain) {
    document.getElementById('sslDomainLabel').innerText = domain;
    document.getElementById('sslDomainInput').value = domain;
    document.getElementById('uploadSslModal').style.display = 'flex';
}
</script>

<?php require BASE_PATH . '/views/layouts/super_admin_footer.php'; ?>
