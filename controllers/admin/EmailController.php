<?php
Auth::check('admin');
$pageTitle = 'Email Notifications';

if (!function_exists('wrapWithLogo')) {
    function wrapWithLogo(string $htmlBody): string {
        $logo     = Config::get('config', 'app.logo');
        $siteName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
        $appUrl   = rtrim(Config::get('config', 'app.url') ?? '', '/');

        $logoHtml = $logo
            ? '<img src="' . $appUrl . '/' . ltrim($logo, '/') . '" alt="' . htmlspecialchars($siteName) . '" style="max-height:50px;max-width:200px">'
            : '<strong style="font-size:20px">' . htmlspecialchars($siteName) . '</strong>';

        return '
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
        <div style="background:linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%);padding:20px;text-align:center;border-radius:8px 8px 0 0">
            ' . $logoHtml . '
        </div>
        <div style="padding:24px;border:1px solid #E2E8F0;border-top:none;border-radius:0 0 8px 8px">
            ' . $htmlBody . '
        </div>
        <div style="text-align:center;font-size:11px;color:#94A3B8;margin-top:12px">
            &copy; ' . date('Y') . ' ' . htmlspecialchars($siteName) . '
        </div>
    </div>';
    }
}

$action = Helpers::get('action') ?: 'templates';

// ── Templates list / edit ────────────────────────────────────────────────────
if ($action === 'templates') {
    $templates = Database::fetchAll("SELECT * FROM email_templates ORDER BY event_type");
    require BASE_PATH . '/views/admin/email/index.php';
}

elseif ($action === 'edit') {
    $id  = (int)Helpers::get('id');
    $tpl = $id ? Database::fetchOne("SELECT * FROM email_templates WHERE id=?", [$id]) : null;
    if (!$tpl) { Helpers::redirect('/admin/email?action=templates'); }

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $subject  = trim(Helpers::post('subject'));
        $htmlBody = trim(Helpers::postRaw('html_body'));
        $isActive = Helpers::post('is_active') ? 1 : 0;
        if ($subject && $htmlBody) {
            Database::update('email_templates', [
                'subject'   => $subject,
                'html_body' => $htmlBody,
                'is_active' => $isActive,
            ], 'id=?', [$id]);
            Helpers::flash('success', 'Template saved.');
            Helpers::redirect('/admin/email?action=templates');
        } else {
            Helpers::flash('error', 'Subject and body are required.');
        }
    }
    require BASE_PATH . '/views/admin/email/edit.php';
}

// ── Promotional blast ────────────────────────────────────────────────────────
elseif ($action === 'blast') {
    $affiliates = Database::fetchAll(
        "SELECT u.id, u.email, u.first_name, u.last_name FROM users u WHERE u.role='affiliate' AND u.status='active' ORDER BY u.first_name"
    );

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $subject  = trim(Helpers::post('subject'));
        $htmlBody = trim(Helpers::postRaw('html_body'));
        $targets  = Helpers::post('targets'); // 'all' or 'selected'
        $selected = $_POST['affiliate_ids'] ?? [];

        if (!$subject || !$htmlBody) {
            Helpers::flash('error', 'Subject and body are required.');
            Helpers::redirect('/admin/email?action=blast');
        }

        // Handle image upload
        $imageHtml = '';
        if (!empty($_FILES['blast_image']['tmp_name']) && $_FILES['blast_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/assets/uploads/email/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['blast_image']['tmp_name']);
            finfo_close($finfo);
            $allowedMimes = ['image/jpeg','image/png','image/gif','image/webp'];
            $fileSize = $_FILES['blast_image']['size'];

            if (in_array($mimeType, $allowedMimes) && $fileSize <= 2 * 1024 * 1024) {
                $ext      = pathinfo($_FILES['blast_image']['name'], PATHINFO_EXTENSION);
                $filename = 'blast_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['blast_image']['tmp_name'], $uploadDir . $filename)) {
                    $appUrlBase = rtrim(Config::get('config', 'app.url') ?? '', '/');
                    $imageHtml  = '<div style="text-align:center;margin-bottom:16px"><img src="' . $appUrlBase . '/assets/uploads/email/' . $filename . '" style="max-width:100%;height:auto" alt=""></div>';
                }
            }
        }

        // Prepend image to body if uploaded
        if ($imageHtml) {
            $htmlBody = $imageHtml . $htmlBody;
        }

        $siteName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
        $appUrl   = rtrim(Config::get('config', 'app.url') ?? '', '/');
        $sent = 0; $failed = 0;

        $list = ($targets === 'all') ? $affiliates : array_filter($affiliates, fn($a) => in_array($a['id'], $selected));

        foreach ($list as $aff) {
            $body = str_replace(
                ['{{name}}', '{{email}}', '{{site_name}}', '{{app_url}}'],
                [Helpers::e($aff['first_name'] . ' ' . $aff['last_name']), $aff['email'], $siteName, $appUrl],
                $htmlBody
            );
            $wrappedBody = wrapWithLogo($body);
            $ok = Mailer::sendRaw($aff['email'], $aff['first_name'] . ' ' . $aff['last_name'], $subject, $wrappedBody, 'blast');
            $ok ? $sent++ : $failed++;
        }

        Helpers::flash('success', "Blast sent: $sent delivered, $failed failed.");
        Helpers::redirect('/admin/email?action=blast');
    }

    require BASE_PATH . '/views/admin/email/blast.php';
}

elseif ($action === 'logs') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $offerNew = isset($_POST['offer_new_notify']) ? '1' : '0';
        $offerStatus = isset($_POST['offer_status_notify']) ? '1' : '0';
        $offerLink = isset($_POST['offer_link_notify']) ? '1' : '0';
        
        Config::set('config', 'app.offer_new_notify', $offerNew);
        Config::set('config', 'app.offer_status_notify', $offerStatus);
        Config::set('config', 'app.offer_link_notify', $offerLink);
        
        Helpers::flash('success', 'Notification settings updated successfully.');
        Helpers::redirect('/admin/email?action=logs');
    }

    $logs = Database::fetchAll("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 200");
    require BASE_PATH . '/views/admin/email/logs.php';
}
