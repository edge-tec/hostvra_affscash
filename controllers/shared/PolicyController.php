<?php
$slug = $_GET['slug'] ?? 'terms-conditions';

$policies = [
    'terms-conditions' => [
        'title' => 'Terms & Conditions', 
        'content' => "These are the generic terms and conditions for our affiliate network.\n\nBy accessing and using this dashboard, you agree to comply with our platform's rules and guidelines. Please review this document periodically for any updates."
    ],
    'privacy-policy' => [
        'title' => 'Privacy Policy', 
        'content' => "We value your privacy. We collect only the data necessary to provide our services.\n\nWe do not sell your personal information. Your data is encrypted and securely stored."
    ],
    'affiliate-agreement' => [
        'title' => 'Affiliate Agreement', 
        'content' => "This is the binding agreement between you (the Affiliate) and the Network.\n\nIt outlines payment terms, promotional restrictions, and your responsibilities as a partner."
    ],
    'anti-fraud-policy' => [
        'title' => 'Anti-Fraud Policy', 
        'content' => "We maintain a zero-tolerance policy against fraudulent activity.\n\nAny affiliate caught using bots, proxy traffic, or deceptive practices will face immediate termination and forfeiture of earnings."
    ],
    'gdpr-compliance' => [
        'title' => 'GDPR Compliance', 
        'content' => "We comply with the General Data Protection Regulation (GDPR).\n\nYou have the right to request access, modification, or deletion of your personal data at any time."
    ],
    'refund-policy' => [
        'title' => 'Refund Policy', 
        'content' => "This outlines our policy regarding refunds and chargebacks.\n\nIf a conversion is reversed by the advertiser due to a refund or chargeback, the corresponding commission will be deducted from your balance."
    ],
    'cookie-policy' => [
        'title' => 'Cookie Policy', 
        'content' => "We use cookies to enhance your experience and track conversions accurately.\n\nBy continuing to use this site, you consent to our use of essential and performance cookies."
    ],
    'dashboard-disclaimers' => [
        'title' => 'Dashboard Disclaimers', 
        'content' => "Statistics shown in real-time are estimates and may be subject to final verification by advertisers.\n\nWe are not responsible for temporary inaccuracies caused by network delays."
    ]
];

if (!isset($policies[$slug])) {
    http_response_code(404);
    die("Policy not found.");
}

$policy = $policies[$slug];
$appName = Config::get('config', 'app.name') ?? 'AffTracker';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helpers::e($policy['title']) ?> — <?= Helpers::e($appName) ?></title>
    <style>
        :root {
            --bg-body: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --primary: #4F46E5;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-body: #0F172A;
                --bg-card: #1E293B;
                --text-main: #F8FAFC;
                --text-muted: #94A3B8;
                --border: #334155;
                --primary: #818CF8;
            }
        }
        body { 
            font-family: system-ui, -apple-system, sans-serif; 
            padding: 40px 20px; 
            background: var(--bg-body); 
            color: var(--text-main); 
            line-height: 1.6; 
            margin: 0;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: var(--bg-card); 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        h1 { margin: 0; font-size: 24px; }
        .back-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .back-link:hover { text-decoration: underline; }
        .content { color: var(--text-muted); font-size: 15px; }
        .content p { margin-bottom: 16px; }
        .notice {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($policy['title']) ?></h1>
            <a href="javascript:history.back()" class="back-link">&larr; Go Back</a>
        </div>
        <div class="notice">
            <strong>Note:</strong> This is a standard policy template. The network administrator will update this content with the official policy text.
        </div>
        <div class="content">
            <p><?= nl2br(htmlspecialchars($policy['content'])) ?></p>
        </div>
    </div>
</body>
</html>
