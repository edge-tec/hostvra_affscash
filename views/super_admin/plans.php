<?php require BASE_PATH . '/views/layouts/super_admin.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2 style="font-family: var(--font-heading); font-weight: 700; color: #FFFFFF; margin: 0;">💎 Subscription Plans</h2>
    <button class="btn-premium" onclick="openCreatePlanModal()">+ Add New Subscription Tier</button>
</div>

<!-- Plans Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px;">
    <?php foreach ($plans as $p): ?>
        <div class="glass-card" style="display: flex; flex-direction: column;">
            <div class="glass-header" style="display: flex; justify-content: space-between; align-items: center; background: rgba(139, 92, 246, 0.08);">
                <h3 class="glass-title" style="font-size: 19px; color: #FFFFFF;"><?= Helpers::e($p['name']) ?></h3>
                <span style="font-size: 11px; padding: 2px 8px; border-radius: 8px; font-weight: 700; background: <?= $p['status'] === 'active' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $p['status'] === 'active' ? '#34D399' : '#FCA5A5' ?>;">
                    <?= strtoupper($p['status']) ?>
                </span>
            </div>
            
            <div style="padding: 24px; flex-grow: 1;">
                <!-- Pricing details -->
                <div style="text-align: center; margin-bottom: 24px; border-bottom: 1px solid rgba(139, 92, 246, 0.15); padding-bottom: 16px;">
                    <span style="font-size: 36px; font-weight: 800; color: #FFFFFF;">$<?= number_format($p['price'], 2) ?></span>
                    <span style="color: var(--text-muted); font-size: 14px;"> / <?= $p['duration'] ?> Days</span>
                </div>
                
                <!-- Plan limits list -->
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 13.5px; line-height: 2.2;">
                    <li style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 4px 0;">
                        <span style="color: var(--text-muted);">👥 Team Members</span>
                        <strong style="color: #F8FAFC;"><?= $p['max_users'] > 0 ? number_format($p['max_users']) . ' active users' : 'Unlimited' ?></strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 4px 0;">
                        <span style="color: var(--text-muted);">🏷️ Offers Tracking Limit</span>
                        <strong style="color: #F8FAFC;"><?= $p['max_offers'] > 0 ? number_format($p['max_offers']) . ' active offers' : 'Unlimited' ?></strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 4px 0;">
                        <span style="color: var(--text-muted);">🌐 Domain Links Allowed</span>
                        <strong style="color: #F8FAFC;"><?= $p['max_domains'] > 0 ? number_format($p['max_domains']) . ' custom domains' : 'Unlimited' ?></strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 4px 0;">
                        <span style="color: var(--text-muted);">⚡ Monthly Clicks Limit</span>
                        <strong style="color: #F8FAFC;"><?= $p['max_click_limits'] > 0 ? number_format($p['max_click_limits']) . ' clicks' : 'Unlimited' ?></strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; padding: 4px 0;">
                        <span style="color: var(--text-muted);">💾 Cloud Assets Storage</span>
                        <strong style="color: #F8FAFC;"><?= $p['max_storage'] > 0 ? number_format($p['max_storage']) . ' MB' : 'Unlimited' ?></strong>
                    </li>
                </ul>
            </div>
            
            <div style="padding: 16px 24px; background: rgba(11, 9, 26, 0.4); border-top: 1px solid var(--border-glass); display: flex; justify-content: flex-end;">
                <button class="btn-premium" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); box-shadow: none;" onclick='openEditPlanModal(<?= json_encode($p) ?>)'>Modify Limits</button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- PLAN MODAL (CREATE / EDIT) -->
<div id="planModal" style="display: none; position: fixed; inset: 0; background: rgba(9, 8, 22, 0.7); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
    <div class="glass-card" style="width: 100%; max-width: 500px;">
        <div class="glass-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="glass-title" id="modalTitle">Add Subscription Plan</h3>
            <button onclick="document.getElementById('planModal').style.display='none'" style="background: none; border: none; color: #FFFFFF; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="/super_admin/plans?action=create" method="POST" id="planForm" style="padding: 24px;">
            <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrf() ?>">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Plan Name</label>
                <input type="text" name="name" id="planName" required class="glass-input" style="width: 100%;" placeholder="e.g. Enterprise Monthly">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Price (USD)</label>
                    <input type="number" step="0.01" name="price" id="planPrice" required class="glass-input" style="width: 100%;" placeholder="199.00">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Billing Cycle (Days)</label>
                    <input type="number" name="duration" id="planDuration" required class="glass-input" style="width: 100%;" placeholder="30">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Max Users Limit</label>
                    <input type="number" name="max_users" id="planUsers" required class="glass-input" style="width: 100%;" placeholder="0 for Unlimited">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Max Offers Limit</label>
                    <input type="number" name="max_offers" id="planOffers" required class="glass-input" style="width: 100%;" placeholder="0 for Unlimited">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Max Domains Limit</label>
                    <input type="number" name="max_domains" id="planDomains" required class="glass-input" style="width: 100%;" placeholder="0 for Unlimited">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Max Monthly Clicks</label>
                    <input type="number" name="max_click_limits" id="planClicks" required class="glass-input" style="width: 100%;" placeholder="0 for Unlimited">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Max Storage (MB)</label>
                    <input type="number" name="max_storage" id="planStorage" required class="glass-input" style="width: 100%;" placeholder="0 for Unlimited">
                </div>
                <div>
                    <label style="display: block; font-size: 12.5px; margin-bottom: 6px; color: #C7D2FE;">Plan Status</label>
                    <select name="status" id="planStatus" class="glass-input glass-select" style="width: 100%;">
                        <option value="active">Active</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid rgba(139, 92, 246, 0.15); padding-top: 20px;">
                <button type="button" class="btn-premium" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); box-shadow: none;" onclick="document.getElementById('planModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-premium" id="btnSubmit">Save Subscription</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreatePlanModal() {
    document.getElementById('modalTitle').textContent = 'Add Subscription Plan';
    document.getElementById('planForm').action = '/super_admin/plans?action=create';
    document.getElementById('planName').value = '';
    document.getElementById('planPrice').value = '';
    document.getElementById('planDuration').value = '30';
    document.getElementById('planUsers').value = '0';
    document.getElementById('planOffers').value = '0';
    document.getElementById('planDomains').value = '0';
    document.getElementById('planClicks').value = '0';
    document.getElementById('planStorage').value = '0';
    document.getElementById('planStatus').value = 'active';
    document.getElementById('btnSubmit').textContent = 'Create Tier';
    document.getElementById('planModal').style.display = 'flex';
}

function openEditPlanModal(plan) {
    document.getElementById('modalTitle').textContent = 'Modify Plan: ' + plan.name;
    document.getElementById('planForm').action = '/super_admin/plans?action=edit&id=' + plan.id;
    document.getElementById('planName').value = plan.name;
    document.getElementById('planPrice').value = plan.price;
    document.getElementById('planDuration').value = plan.duration;
    document.getElementById('planUsers').value = plan.max_users;
    document.getElementById('planOffers').value = plan.max_offers;
    document.getElementById('planDomains').value = plan.max_domains;
    document.getElementById('planClicks').value = plan.max_click_limits;
    document.getElementById('planStorage').value = plan.max_storage;
    document.getElementById('planStatus').value = plan.status;
    document.getElementById('btnSubmit').textContent = 'Save Changes';
    document.getElementById('planModal').style.display = 'flex';
}
</script>

<?php require BASE_PATH . '/views/layouts/super_admin_footer.php'; ?>
