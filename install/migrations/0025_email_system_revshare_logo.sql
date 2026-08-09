-- Migration: 0025_email_system_revshare_logo.sql
-- Description: Standardize email templates to display dynamic RevShare percentage and work cleanly with centralized logo layout

UPDATE `email_templates` SET `html_body` = '<p style="margin:0 0 14px;font-size:14px;color:#334155">Hi {{name}},</p>
<p style="margin:0 0 18px;font-size:14px;color:#334155">A new offer just went live on <strong>{{site_name}}</strong> and is ready for you to promote.</p>
<table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin:0 0 18px;font-size:13.5px">
    <tr><td style="padding:10px 0;color:#64748B;width:36%">Offer Name</td><td style="padding:10px 0;font-weight:700">{{offer_name}}</td></tr>
    {{#if_category}}<tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">Category</td><td style="padding:10px 0;border-top:1px solid #F1F5F9">{{category}}</td></tr>{{/if_category}}
    {{#if_geos}}<tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">GEO Targeting</td><td style="padding:10px 0;border-top:1px solid #F1F5F9">{{geos}}</td></tr>{{/if_geos}}
    <tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">Commission</td><td style="padding:10px 0;font-weight:700;color:#059669;border-top:1px solid #F1F5F9">{{commission}}</td></tr>
    <tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">RevShare</td><td style="padding:10px 0;font-weight:700;color:#7C3AED;border-top:1px solid #F1F5F9">{{revshare}}</td></tr>
    <tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">Status</td><td style="padding:10px 0;border-top:1px solid #F1F5F9"><span style="display:inline-block;padding:3px 10px;background:#DCFCE7;color:#166534;border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase">{{status_badge}}</span></td></tr>
</table>
<div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px 16px;margin:0 0 22px">
    <div style="font-size:11px;color:#64748B;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px">Offer Details</div>
    <div style="font-size:13.5px;color:#334155">{{offer_desc_html}}</div>
</div>
<div style="text-align:center;margin:24px 0 4px">
    <a href="{{offers_url}}" style="display:inline-block;background:#4F46E5;color:#fff;padding:13px 30px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px">View Offer in Dashboard &rarr;</a>
</div>' WHERE `event_type` = 'offer_new';

UPDATE `email_templates` SET `html_body` = '<p style="margin:0 0 14px;font-size:14px;color:#334155">Hi {{name}},</p>
<p style="margin:0 0 18px;font-size:14px;color:#334155">The status for the offer <strong>{{offer_name}}</strong> has been updated.</p>
<table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin:0 0 22px;font-size:13.5px">
    <tr><td style="padding:10px 0;color:#64748B;width:36%">Offer Name</td><td style="padding:10px 0;font-weight:700">{{offer_name}}</td></tr>
    <tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">Previous Status</td><td style="padding:10px 0;font-weight:700;color:#64748B;border-top:1px solid #F1F5F9">{{old_status}}</td></tr>
    <tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">New Status</td><td style="padding:10px 0;font-weight:700;color:#2563EB;border-top:1px solid #F1F5F9">{{new_status}}</td></tr>
    <tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">RevShare</td><td style="padding:10px 0;font-weight:700;color:#7C3AED;border-top:1px solid #F1F5F9">{{revshare}}</td></tr>
</table>
<div style="text-align:center;margin:14px 0 4px">
    <a href="{{offers_url}}" style="display:inline-block;background:#3B82F6;color:#fff;padding:13px 30px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px">View Offers in Dashboard &rarr;</a>
</div>' WHERE `event_type` = 'offer_status';

UPDATE `email_templates` SET `html_body` = '<p style="margin:0 0 14px;font-size:14px;color:#334155">Hi {{name}},</p>
<p style="margin:0 0 18px;font-size:14px;color:#334155">The tracking destination or landing pages for the offer <strong>{{offer_name}}</strong> have been updated.</p>
<table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin:0 0 18px;font-size:13.5px">
    <tr><td style="padding:10px 0;color:#64748B;width:36%">Offer Name</td><td style="padding:10px 0;font-weight:700">{{offer_name}}</td></tr>
    <tr><td style="padding:10px 0;color:#64748B;border-top:1px solid #F1F5F9">RevShare</td><td style="padding:10px 0;font-weight:700;color:#7C3AED;border-top:1px solid #F1F5F9">{{revshare}}</td></tr>
</table>
<div style="background:#FEE2E2;border:1px solid #F87171;border-radius:8px;padding:14px;margin-bottom:22px;">
    <p style="margin:0;font-size:13.5px;color:#991B1B">
        <strong>Important:</strong> You do NOT need to change your existing affiliate tracking link. Your current tracking link will automatically route traffic to the new destination.
    </p>
</div>
<div style="text-align:center;margin:14px 0 4px">
    <a href="{{offers_url}}" style="display:inline-block;background:#059669;color:#fff;padding:13px 30px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px">View Offer in Dashboard &rarr;</a>
</div>' WHERE `event_type` = 'offer_link';

UPDATE `email_templates` SET `html_body` = '<h2>Hello {{name}},</h2>
<p>Great news! Your request to promote the offer <strong>{{offer_name}}</strong> on <strong>{{site_name}}</strong> has been <strong>approved</strong>.</p>
<table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin:16px 0;font-size:13.5px">
    <tr><td style="padding:8px 0;color:#64748B;width:36%">Offer Name</td><td style="padding:8px 0;font-weight:700">{{offer_name}}</td></tr>
    <tr><td style="padding:8px 0;color:#64748B;border-top:1px solid #F1F5F9">RevShare</td><td style="padding:8px 0;font-weight:700;color:#7C3AED;border-top:1px solid #F1F5F9">{{revshare}}</td></tr>
</table>
<p><a href="{{app_url}}/affiliate/offers" style="background:#10B981;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;display:inline-block">View Offers</a></p>
<p>Thanks,<br>{{site_name}} Team</p>' WHERE `event_type` = 'offer_approved';

UPDATE `email_templates` SET `html_body` = '<h2>Offer Access Request</h2>
<p>Affiliate <strong>{{name}}</strong> ({{email}}) has requested access to the offer: <strong>{{offer_name}}</strong>.</p>
<table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin:16px 0;font-size:13.5px">
    <tr><td style="padding:8px 0;color:#64748B;width:36%">Offer Name</td><td style="padding:8px 0;font-weight:700">{{offer_name}}</td></tr>
    <tr><td style="padding:8px 0;color:#64748B;border-top:1px solid #F1F5F9">RevShare</td><td style="padding:8px 0;font-weight:700;color:#7C3AED;border-top:1px solid #F1F5F9">{{revshare}}</td></tr>
</table>
<p><a href="{{app_url}}/admin/affiliates/{{affiliate_id}}" style="background:#4F46E5;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;display:inline-block">Review Request</a></p>' WHERE `event_type` = 'offer_approval_requested';
