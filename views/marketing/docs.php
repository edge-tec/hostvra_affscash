<?php require BASE_PATH . '/views/layouts/marketing.php'; ?>

<!-- Developer API Documentation Page -->
<div style="display: grid; grid-template-columns: 260px 1fr; gap: 40px; margin-bottom: 80px;">
    
    <!-- Sidebar Navigation Index -->
    <div style="position: sticky; top: 100px; height: calc(100vh - 140px); overflow-y: auto;">
        <h4 style="font-family: 'Rajdhani', sans-serif; font-size: 15px; font-weight: 700; color: #FFFFFF; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 20px;">
            Documentation Index
        </h4>
        <ul style="list-style: none; padding: 0; margin: 0; font-size: 13.5px; line-height: 2.2;">
            <li><a href="#getting-started" style="color: var(--primary-color); text-decoration: none; font-weight: 700;">Getting Started</a></li>
            
            <?php if (Tenant::getTenantId() === null): ?>
            <!-- SaaS platform Docs -->
            <li><a href="#admin-provision" style="color: #94A3B8; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFFFFF'" onmouseout="this.style.color='#94A3B8'">Admin Tenant Provisioning</a></li>
            <li><a href="#billing-webhooks" style="color: #94A3B8; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFFFFF'" onmouseout="this.style.color='#94A3B8'">Billing Webhooks Sync</a></li>
            <li><a href="#security-audit" style="color: #94A3B8; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFFFFF'" onmouseout="this.style.color='#94A3B8'">Security Audit Logs API</a></li>
            <?php else: ?>
            <!-- Resolved Tenant Docs -->
            <li><a href="#click-tracking" style="color: #94A3B8; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFFFFF'" onmouseout="this.style.color='#94A3B8'">Click Tracking integration</a></li>
            <li><a href="#postback-triggers" style="color: #94A3B8; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFFFFF'" onmouseout="this.style.color='#94A3B8'">Postback Conversion Triggers</a></li>
            <li><a href="#smartlink-nodes" style="color: #94A3B8; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFFFFF'" onmouseout="this.style.color='#94A3B8'">Smartlink Rotation Nodes</a></li>
            <?php endif; ?>
            
            <li><a href="#error-handling" style="color: #94A3B8; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#FFFFFF'" onmouseout="this.style.color='#94A3B8'">Error Handling Codes</a></li>
        </ul>
    </div>

    <!-- Main documentation body -->
    <div>
        <!-- Section: Getting Started -->
        <section id="getting-started" style="margin-bottom: 60px; scroll-margin-top: 100px;">
            <span style="background: rgba(139, 92, 246, 0.15); color: #C7D2FE; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(139, 92, 246, 0.3); text-transform: uppercase; letter-spacing: 0.1em; display: inline-block; margin-bottom: 12px;">v2.0 Core REST API</span>
            <h2 style="font-size: 32px; font-weight: 800; font-family: 'Rajdhani', sans-serif; margin: 0 0 16px;">Getting Started &amp; Integration</h2>
            <p style="color: #94A3B8; font-size: 14.5px; line-height: 1.6; margin-bottom: 20px;">
                Welcome to the developer API guides. All programmatic interactions expect custom JSON payloads, secure SSL connections, and authorization keys passed in the request header index.
            </p>
            <div class="glass-card" style="background: rgba(10, 8, 24, 0.55); border-left: 4px solid var(--primary-color); padding: 20px; font-family: monospace; font-size: 13px; color: #E2E8F0; margin-bottom: 20px;">
                Authorization: Bearer &lt;YOUR_API_TOKEN_KEY&gt;<br>
                Content-Type: application/json<br>
                Accept: application/json
            </div>
        </section>

        <?php if (Tenant::getTenantId() === null): ?>
        <!-- SaaS platform Docs -->
        <section id="admin-provision" style="margin-bottom: 60px; scroll-margin-top: 100px;">
            <h2 style="font-size: 26px; font-weight: 800; font-family: 'Rajdhani', sans-serif; margin: 0 0 16px;">1. Admin Tenant Provisioning</h2>
            <p style="color: #94A3B8; font-size: 14.5px; line-height: 1.6; margin-bottom: 20px;">
                Create and manage independent client admin workspaces programmatically during registration flows.
            </p>
            
            <div style="background: rgba(15, 12, 38, 0.6); padding: 10px 20px; border-radius: 8px; font-family: monospace; font-size: 13px; margin-bottom: 12px; display: inline-block; border: 1px solid rgba(16, 185, 129, 0.3); color: #34D399; font-weight: bold;">
                POST /api/v1/tenants
            </div>
            
            <div class="glass-card" style="background: #090714; padding: 20px; font-family: monospace; font-size: 13px; color: #A5B4FC; overflow-x: auto;">
<pre>{
  "tenant_name": "Phoenix Media LTD",
  "admin_email": "billing@phoenix.net",
  "plan_id": 3,
  "custom_domain": "tracker.phoenix.net"
}</pre>
            </div>
        </section>

        <section id="billing-webhooks" style="margin-bottom: 60px; scroll-margin-top: 100px;">
            <h2 style="font-size: 26px; font-weight: 800; font-family: 'Rajdhani', sans-serif; margin: 0 0 16px;">2. Billing Webhooks Sync</h2>
            <p style="color: #94A3B8; font-size: 14.5px; line-height: 1.6; margin-bottom: 20px;">
                Sync active subscription updates, monthly click billing counts, and cancel events dynamically from payment processors.
            </p>
            <div style="background: rgba(15, 12, 38, 0.6); padding: 10px 20px; border-radius: 8px; font-family: monospace; font-size: 13px; margin-bottom: 12px; display: inline-block; border: 1px solid rgba(16, 185, 129, 0.3); color: #34D399; font-weight: bold;">
                POST /api/v1/billing/sync
            </div>
            <div class="glass-card" style="background: #090714; padding: 20px; font-family: monospace; font-size: 13px; color: #A5B4FC; overflow-x: auto;">
<pre>{
  "tenant_id": 18,
  "event": "subscription.renewed",
  "amount_paid": 149.00,
  "expires_at": "2026-06-25 19:59:00"
}</pre>
            </div>
        </section>

        <section id="security-audit" style="margin-bottom: 60px; scroll-margin-top: 100px;">
            <h2 style="font-size: 26px; font-weight: 800; font-family: 'Rajdhani', sans-serif; margin: 0 0 16px;">3. Security Audit Logs API</h2>
            <p style="color: #94A3B8; font-size: 14.5px; line-height: 1.6; margin-bottom: 20px;">
                Query global security audit trails, super admin overrides, and proxy blocker threat logs programmatically.
            </p>
            <div style="background: rgba(15, 12, 38, 0.6); padding: 10px 20px; border-radius: 8px; font-family: monospace; font-size: 13px; margin-bottom: 12px; display: inline-block; border: 1px solid rgba(99, 102, 241, 0.3); color: #818CF8; font-weight: bold;">
                GET /api/v1/security/logs
            </div>
        </section>

        <?php else: ?>
        <!-- Resolved Tenant Docs -->
        <section id="click-tracking" style="margin-bottom: 60px; scroll-margin-top: 100px;">
            <h2 style="font-size: 26px; font-weight: 800; font-family: 'Rajdhani', sans-serif; margin: 0 0 16px;">1. Click Tracking Integration</h2>
            <p style="color: #94A3B8; font-size: 14.5px; line-height: 1.6; margin-bottom: 20px;">
                Publishers and affiliates should format tracking links as follows to record clicks and capture unique sub-affiliate parameters.
            </p>
            <div class="glass-card" style="background: #090714; padding: 20px; font-family: monospace; font-size: 13px; color: #A5B4FC; border-left: 4px solid var(--primary-color);">
                https://<?= $_SERVER['HTTP_HOST'] ?>/click?aff_id=<strong>{AFFILIATE_ID}</strong>&amp;offer_id=<strong>{OFFER_ID}</strong>&amp;sub_id=<strong>{SUB_AFFILIATE_CLICK_ID}</strong>
            </div>
        </section>

        <section id="postback-triggers" style="margin-bottom: 60px; scroll-margin-top: 100px;">
            <h2 style="font-size: 26px; font-weight: 800; font-family: 'Rajdhani', sans-serif; margin: 0 0 16px;">2. Postback Conversion Triggers</h2>
            <p style="color: #94A3B8; font-size: 14.5px; line-height: 1.6; margin-bottom: 20px;">
                Trigger conversions dynamically from advertiser desks or tracking pixels. The reliability engine supports deduplication immediately.
            </p>
            <div class="glass-card" style="background: #090714; padding: 20px; font-family: monospace; font-size: 13px; color: #A5B4FC; border-left: 4px solid var(--primary-color); margin-bottom: 20px;">
                https://<?= $_SERVER['HTTP_HOST'] ?>/postback?click_id=<strong>{SUB_AFFILIATE_CLICK_ID}</strong>&amp;payout=<strong>{CONVERSION_REVENUE_AMOUNT}</strong>
            </div>
        </section>

        <section id="smartlink-nodes" style="margin-bottom: 60px; scroll-margin-top: 100px;">
            <h2 style="font-size: 26px; font-weight: 800; font-family: 'Rajdhani', sans-serif; margin: 0 0 16px;">3. Smartlink Rotation Nodes</h2>
            <p style="color: #94A3B8; font-size: 14.5px; line-height: 1.6; margin-bottom: 20px;">
                Route dynamic bulk traffic to unified smartlink nodes for automated device and geo-targeted rotations.
            </p>
            <div class="glass-card" style="background: #090714; padding: 20px; font-family: monospace; font-size: 13px; color: #A5B4FC; border-left: 4px solid var(--primary-color);">
                https://<?= $_SERVER['HTTP_HOST'] ?>/smart?key=<strong>{SMARTLINK_NODE_KEY}</strong>&amp;sub_id=<strong>{SUB_ID}</strong>
            </div>
        </section>
        <?php endif; ?>

        <!-- Section: Error Handling -->
        <section id="error-handling" style="margin-bottom: 40px; scroll-margin-top: 100px;">
            <h2 style="font-size: 26px; font-weight: 800; font-family: 'Rajdhani', sans-serif; margin: 0 0 16px;">System Response Codes</h2>
            <p style="color: #94A3B8; font-size: 14.5px; line-height: 1.6; margin-bottom: 20px;">
                The platform utilizes standardized HTTP response status codes to indicate API success or failure limits.
            </p>
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.08); color: #FFFFFF;">
                        <th style="padding: 10px 0;">HTTP Code</th>
                        <th style="padding: 10px 0;">Description</th>
                        <th style="padding: 10px 0;">Trigger Event</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); color: #94A3B8;">
                        <td style="padding: 12px 0; color: #34D399; font-weight: bold;">200 OK</td>
                        <td style="padding: 12px 0;">Action successfully executed and returned.</td>
                        <td style="padding: 12px 0;">Valid clicks, updates, or pixel triggers.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); color: #94A3B8;">
                        <td style="padding: 12px 0; color: #FBBF24; font-weight: bold;">400 Bad Request</td>
                        <td style="padding: 12px 0;">Missing required request metrics (e.g. click_id).</td>
                        <td style="padding: 12px 0;">Invalid postback trigger query.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); color: #94A3B8;">
                        <td style="padding: 12px 0; color: #EF4444; font-weight: bold;">403 Forbidden</td>
                        <td style="padding: 12px 0;">Unauthorized or suspended access limit breached.</td>
                        <td style="padding: 12px 0;">Breached subscription volume cap limits.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); color: #94A3B8;">
                        <td style="padding: 12px 0; color: #EF4444; font-weight: bold;">429 Rate Limit</td>
                        <td style="padding: 12px 0;">Excessive clicks rate triggered from an IP.</td>
                        <td style="padding: 12px 0;">Proxy blocker ban triggering firewalls.</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</div>

<?php require BASE_PATH . '/views/layouts/marketing_footer.php'; ?>
