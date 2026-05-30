<?php
require __DIR__ . '/bootstrap.php';
$templates = [
    [
        'offer_new', 'New Offer Notification', '🎯 New Offer Live: {{offer_name}} — {{site_name}}',
        '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#F8FAFC;font-family:-apple-system,Segoe UI,Roboto,sans-serif;color:#0F172A">
    <div style="max-width:600px;margin:0 auto;padding:24px">
        <div style="background:#fff;border-radius:14px;overflow:hidden;border:1px solid #E2E8F0">
            <div style="background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;padding:24px 28px">
                <div style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;opacity:.8">New Offer</div>
                <h1 style="margin:6px 0 0;font-size:22px;line-height:1.3">{{offer_name}}</h1>
                <div style="margin-top:6px;font-size:13px;opacity:.85">{{advertiser_name}}</div>
            </div>
            <div style="padding:22px 28px">
                <p style="margin:0 0 14px;font-size:14px;color:#334155">Hi {{name}},</p>
                <p style="margin:0 0 18px;font-size:14px;color:#334155">A new offer just went live on {{site_name}} and is ready for you to promote.</p>
                <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin:0 0 18px;font-size:13.5px">
                    <tr><td style="padding:10px 0;color:#64748B;width:36%">Offer Name</td><td style="padding:10px 0;font-weight:700">{{offer_name}}</td></tr>
                    {{#if_category}}<tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">Category</td><td style="padding:10px 0;border-top:1px solid #F1F5F9">{{category}}</td></tr>{{/if_category}}
                    {{#if_geos}}<tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">GEO Targeting</td><td style="padding:10px 0;border-top:1px solid #F1F5F9">{{geos}}</td></tr>{{/if_geos}}
                    <tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">Commission</td><td style="padding:10px 0;font-weight:700;color:#059669;border-top:1px solid #F1F5F9">{{commission}}</td></tr>
                    <tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">Status</td><td style="padding:10px 0;border-top:1px solid #F1F5F9"><span style="display:inline-block;padding:3px 10px;background:#DCFCE7;color:#166534;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase">{{status_badge}}</span></td></tr>
                </table>
                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px 16px;margin:0 0 22px">
                    <div style="font-size:11px;color:#64748B;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">Offer Details</div>
                    <div style="font-size:13.5px;color:#334155">{{offer_desc_html}}</div>
                </div>
                <div style="text-align:center;margin:24px 0 4px">
                    <a href="{{offers_url}}" style="display:inline-block;background:#4F46E5;color:#fff;padding:13px 30px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px">View Offer in Dashboard &rarr;</a>
                </div>
            </div>
            <div style="padding:16px 28px;background:#F8FAFC;border-top:1px solid #E2E8F0;text-align:center;font-size:11px;color:#94A3B8">
                You\'re receiving this because you\'re an active affiliate on {{site_name}}.<br>
                <a href="{{app_url}}/privacy-policy" style="color:#94A3B8">Privacy Policy</a>
            </div>
        </div>
    </div>
</body></html>'
    ],
    [
        'offer_status', 'Offer Status Change Notification', '🔄 Offer Status Update: {{offer_name}} — {{site_name}}',
        '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#F8FAFC;font-family:-apple-system,Segoe UI,Roboto,sans-serif;color:#0F172A">
    <div style="max-width:600px;margin:0 auto;padding:24px">
        <div style="background:#fff;border-radius:14px;overflow:hidden;border:1px solid #E2E8F0">
            <div style="background:linear-gradient(135deg,#3B82F6,#2563EB);color:#fff;padding:24px 28px">
                <div style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;opacity:.8">Status Update</div>
                <h1 style="margin:6px 0 0;font-size:22px;line-height:1.3">{{offer_name}}</h1>
                <div style="margin-top:6px;font-size:13px;opacity:.85">Changed from {{old_status}} to {{new_status}}</div>
            </div>
            <div style="padding:22px 28px">
                <p style="margin:0 0 14px;font-size:14px;color:#334155">Hi {{name}},</p>
                <p style="margin:0 0 18px;font-size:14px;color:#334155">The status for the offer <strong>{{offer_name}}</strong> has been updated.</p>
                <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin:0 0 22px;font-size:13.5px">
                    <tr><td style="padding:10px 0;color:#64748B;width:36%">Previous Status</td><td style="padding:10px 0;font-weight:700">{{old_status}}</td></tr>
                    <tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">New Status</td><td style="padding:10px 0;font-weight:700;color:#2563EB;border-top:1px solid #F1F5F9">{{new_status}}</td></tr>
                </table>
                <div style="text-align:center;margin:14px 0 4px">
                    <a href="{{offers_url}}" style="display:inline-block;background:#3B82F6;color:#fff;padding:13px 30px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px">View Offers in Dashboard &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</body></html>'
    ],
    [
        'offer_link', 'Tracking Link Change Notification', '🔗 Tracking Link Update: {{offer_name}} — {{site_name}}',
        '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#F8FAFC;font-family:-apple-system,Segoe UI,Roboto,sans-serif;color:#0F172A">
    <div style="max-width:600px;margin:0 auto;padding:24px">
        <div style="background:#fff;border-radius:14px;overflow:hidden;border:1px solid #E2E8F0">
            <div style="background:linear-gradient(135deg,#059669,#10B981);color:#fff;padding:24px 28px">
                <div style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;opacity:.8">Link Update</div>
                <h1 style="margin:6px 0 0;font-size:22px;line-height:1.3">{{offer_name}}</h1>
            </div>
            <div style="padding:22px 28px">
                <p style="margin:0 0 14px;font-size:14px;color:#334155">Hi {{name}},</p>
                <p style="margin:0 0 18px;font-size:14px;color:#334155">The tracking destination or landing pages for the offer <strong>{{offer_name}}</strong> have been updated.</p>
                <div style="background:#FEE2E2;border:1px solid #F87171;border-radius:8px;padding:14px;margin-bottom:22px;">
                    <p style="margin:0;font-size:13.5px;color:#991B1B">
                        <strong>Important:</strong> You do NOT need to change your existing affiliate tracking link. Your current tracking link will automatically route traffic to the new destination.
                    </p>
                </div>
                <div style="text-align:center;margin:14px 0 4px">
                    <a href="{{offers_url}}" style="display:inline-block;background:#059669;color:#fff;padding:13px 30px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px">View Offer in Dashboard &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</body></html>'
    ]
];

foreach ($templates as $t) {
    try {
        Database::insert('email_templates', [
            'event_type' => $t[0],
            'label' => $t[1],
            'subject' => $t[2],
            'html_body' => $t[3],
            'is_active' => 1
        ]);
        echo "Inserted {$t[0]}\n";
    } catch (\Exception $e) {
        echo "Failed {$t[0]}: " . $e->getMessage() . "\n";
    }
}
