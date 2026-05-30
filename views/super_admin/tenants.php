<?php require BASE_PATH . '/views/layouts/super_admin.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-family: var(--font-heading); font-weight: 700; color: #FFFFFF; margin: 0;">🌐 Tenant Management</h2>
    <button class="btn-premium" onclick="document.getElementById('createTenantModal').style.display='flex'">+ Register New Tenant</button>
</div>

<!-- Tenants List -->
<div class="glass-card" style="margin-bottom: 40px;">
    <div class="glass-header">
        <h3 class="glass-title">Platform Tenant Workspaces</h3>
    </div>
    <div style="padding: 24px; overflow-x: auto;">
        <table class="glass-table" id="tenantsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tenant Network Info</th>
                    <th>Administrator</th>
                    <th>Active Plan</th>
                    <th>Custom Domain</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $t): ?>
                    <tr>
                        <td><strong>#<?= $t['id'] ?></strong></td>
                        <td>
                            <strong style="font-size: 15px;"><?= Helpers::e($t['company_name']) ?></strong>
                            <br><span style="font-size: 11px; color: var(--text-muted);">Created: <?= date('M d, Y', strtotime($t['created_at'])) ?></span>
                        </td>
                        <td>
                            <strong><?= Helpers::e($t['first_name'] . ' ' . $t['last_name']) ?></strong>
                            <br><span style="font-size: 12px; color: #818CF8;"><?= Helpers::e($t['admin_email']) ?></span>
                        </td>
                        <td>
                            <strong style="color: #A78BFA;"><?= Helpers::e($t['plan_name'] ?? 'No Subscription') ?></strong>
                            <?php if ($t['ends_at']): ?>
                                <br><span style="font-size: 11px; color: <?= strtotime($t['ends_at']) > time() ? '#34D399' : '#FCA5A5' ?>;">Expires: <?= date('M d, Y', strtotime($t['ends_at'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($t['admin_main_domain'])): ?>
                                <div style="margin-bottom: 4px;">
                                    <span style="font-size: 11px; color: var(--text-muted);">Main:</span>
                                    <span style="font-size: 11.5px; background: rgba(99, 102, 241, 0.15); padding: 2px 6px; border-radius: 4px; color: #818CF8; border: 1px solid rgba(99, 102, 241, 0.2);"><?= Helpers::e($t['admin_main_domain']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($t['admin_tracking_domain'])): ?>
                                <div>
                                    <span style="font-size: 11px; color: var(--text-muted);">Tracking:</span>
                                    <span style="font-size: 11.5px; background: rgba(139, 92, 246, 0.15); padding: 2px 6px; border-radius: 4px; color: #C7D2FE; border: 1px solid rgba(139, 92, 246, 0.2);"><?= Helpers::e($t['admin_tracking_domain']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (empty($t['admin_main_domain']) && empty($t['admin_tracking_domain'])): ?>
                                <span style="color: var(--text-muted); font-style: italic;">None assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; background: <?= $t['status'] === 'active' || $t['status'] === 'trial' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $t['status'] === 'active' || $t['status'] === 'trial' ? '#34D399' : '#FCA5A5' ?>;">
                                <?= strtoupper($t['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                <!-- Impersonate -->
                                <a href="/super_admin/tenants/impersonate/<?= $t['id'] ?>" class="btn-premium" style="padding: 6px 12px; font-size: 12px; background: linear-gradient(135deg, #10B981 0%, #059669 100%); box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2); text-decoration: none;" onclick="return confirm('Are you sure you want to login as administrator for <?= Helpers::e($t['company_name']) ?>?')">Login as Admin</a>
                                
                                <!-- Assign Domains Button -->
                                <button class="btn-premium" style="padding: 6px 12px; font-size: 12px; background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.25); color: #C7D2FE; box-shadow: none;" onclick="openDomainModal(<?= $t['id'] ?>, '<?= Helpers::e($t['admin_main_domain'] ?? '') ?>', '<?= Helpers::e($t['admin_tracking_domain'] ?? '') ?>')">Assign Domains</button>

                                <!-- Update Status Button -->
                                <button class="btn-premium" style="padding: 6px 12px; font-size: 12px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255,255,255,0.15); box-shadow: none;" onclick="openStatusModal(<?= $t['id'] ?>, '<?= $t['status'] ?>')">Set Status</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- CREATE TENANT MODAL -->
<div id="createTenantModal" style="display: none; position: fixed; inset: 0; background: rgba(9, 8, 22, 0.7); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div class="glass-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="glass-title">Register &amp; Provision Workspace</h3>
            <button onclick="document.getElementById('createTenantModal').style.display='none'" style="background: none; border: none; color: #FFFFFF; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/super_admin/tenants?action=create" method="POST" style="padding: 24px;">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
            
            <h4 style="font-family: var(--font-heading); color: #818CF8; border-bottom: 1px solid rgba(139, 92, 246, 0.15); padding-bottom: 8px; margin-bottom: 16px;">SaaS Tenant Info</h4>
            <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Company / Network Name</label>
                    <input type="text" name="company_name" required class="glass-input" style="width: 100%;" placeholder="e.g. Acme CPA Network">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Admin Main Domain (Optional)</label>
                    <input type="text" name="admin_main_domain" class="glass-input" style="width: 100%;" placeholder="e.g. acmecpa.com">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Tracking Domain (Optional)</label>
                    <input type="text" name="admin_tracking_domain" class="glass-input" style="width: 100%;" placeholder="e.g. trk.acmecpa.com">
                </div>
            </div>

            <h4 style="font-family: var(--font-heading); color: #818CF8; border-bottom: 1px solid rgba(139, 92, 246, 0.15); padding-bottom: 8px; margin-bottom: 16px; margin-top: 24px;">Administrator Credentials</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">First Name</label>
                    <input type="text" name="first_name" required class="glass-input" style="width: 100%;" placeholder="John">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Last Name</label>
                    <input type="text" name="last_name" required class="glass-input" style="width: 100%;" placeholder="Doe">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Email Address</label>
                    <input type="email" name="email" required class="glass-input" style="width: 100%;" placeholder="admin@acmecpa.com">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Admin Password</label>
                    <input type="password" name="password" required class="glass-input" style="width: 100%;" placeholder="Minimum 8 characters">
                </div>
            </div>

            <h4 style="font-family: var(--font-heading); color: #818CF8; border-bottom: 1px solid rgba(139, 92, 246, 0.15); padding-bottom: 8px; margin-bottom: 16px; margin-top: 24px;">Subscription Plans &amp; Status</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Subscription Tier</label>
                    <select name="plan_id" required class="glass-input glass-select" style="width: 100%;">
                        <?php foreach ($plans as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= Helpers::e($p['name']) ?> - $<?= number_format($p['price'], 2) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Initial Account Status</label>
                    <select name="status" class="glass-input glass-select" style="width: 100%;">
                        <option value="trial">Free Trial</option>
                        <option value="active">Active Subscription</option>
                        <option value="suspended">Suspended Account</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid rgba(139, 92, 246, 0.15); padding-top: 20px;">
                <button type="button" class="btn-premium" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); box-shadow: none;" onclick="document.getElementById('createTenantModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-premium">Register &amp; Provision</button>
            </div>
        </form>
    </div>
</div>

<!-- CHANGE STATUS MODAL -->
<div id="statusModal" style="display: none; position: fixed; inset: 0; background: rgba(9, 8, 22, 0.7); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 400px;">
        <div class="glass-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="glass-title">Update Tenant Status</h3>
            <button onclick="document.getElementById('statusModal').style.display='none'" style="background: none; border: none; color: #FFFFFF; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/super_admin/tenants?action=status" method="POST" style="padding: 24px;">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
            <input type="hidden" name="tenant_id" id="statusTenantId">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; margin-bottom: 8px; color: #C7D2FE;">Workspace Lifecycle Status</label>
                <select name="status" id="statusSelect" class="glass-input glass-select" style="width: 100%;">
                    <option value="trial">TRIAL</option>
                    <option value="active">ACTIVE</option>
                    <option value="expired">EXPIRED</option>
                    <option value="suspended">SUSPENDED</option>
                    <option value="cancelled">CANCELLED</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid rgba(139, 92, 246, 0.15); padding-top: 20px;">
                <button type="button" class="btn-premium" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); box-shadow: none;" onclick="document.getElementById('statusModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-premium">Save Status</button>
            </div>
        </form>
    </div>
</div>

<!-- ASSIGN DOMAINS MODAL -->
<div id="domainModal" style="display: none; position: fixed; inset: 0; background: rgba(9, 8, 22, 0.7); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 450px;">
        <div class="glass-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="glass-title">Assign Custom Domains</h3>
            <button onclick="document.getElementById('domainModal').style.display='none'" style="background: none; border: none; color: #FFFFFF; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/super_admin/tenants?action=update_domains" method="POST" style="padding: 24px;">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
            <input type="hidden" name="tenant_id" id="domainTenantId">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Admin Main Domain (console)</label>
                <input type="text" name="admin_main_domain" id="domainMainInput" class="glass-input" style="width: 100%;" placeholder="e.g. acmecpa.com">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Tracking Domain (clicks)</label>
                <input type="text" name="admin_tracking_domain" id="domainTrackInput" class="glass-input" style="width: 100%;" placeholder="e.g. trk.acmecpa.com">
            </div>

            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; padding: 12px; margin-bottom: 20px;">
                <p style="margin: 0; font-size: 11.5px; color: #FCA5A5; line-height: 1.4;">
                    <strong>⚠️ Required:</strong> You must also add these domains to your web server (e.g. BT Panel, aaPanel, cPanel) as Addon/Alias domains pointing to this website's folder, otherwise you will see "###" or the default server page.
                </p>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid rgba(139, 92, 246, 0.15); padding-top: 20px;">
                <button type="button" class="btn-premium" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); box-shadow: none;" onclick="document.getElementById('domainModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-premium">Save Domain Assignment</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tenantsTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 10,
        "language": {
            "search": "Filter Workspace:",
            "lengthMenu": "Display _MENU_ networks per page"
        }
    });
});

function openStatusModal(tenantId, currentStatus) {
    document.getElementById('statusTenantId').value = tenantId;
    document.getElementById('statusSelect').value = currentStatus;
    document.getElementById('statusModal').style.display = 'flex';
}

function openDomainModal(tenantId, mainDomain, trackingDomain) {
    document.getElementById('domainTenantId').value = tenantId;
    document.getElementById('domainMainInput').value = mainDomain;
    document.getElementById('domainTrackInput').value = trackingDomain;
    document.getElementById('domainModal').style.display = 'flex';
}
</script>

<?php require BASE_PATH . '/views/layouts/super_admin_footer.php'; ?>
