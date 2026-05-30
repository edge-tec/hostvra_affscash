<?php
/**
 * Admin Portal - Landing Page Customizer Builder Controller
 */
Auth::check('admin');

$tenantId = (int)$_SESSION['tenant_id'];

// Expiry / Suspension Check
if (!Tenant::isSubscriptionActive($tenantId)) {
    Helpers::redirect('/admin/subscription/renew');
}

// Access Control check: If disabled by Super Admin, block page access!
if (!Tenant::isLandingEnabled()) {
    Helpers::redirect('/admin/dashboard?error=' . urlencode('Landing Page Access is disabled by the Administrator.'));
}

$error = '';
$success = '';

// Resolve or Create default landing page record
$lp = Database::fetchOne("SELECT * FROM `landing_pages` WHERE tenant_id = ?", [$tenantId]);
if (!$lp) {
    $lpId = Database::insert('landing_pages', [
        'tenant_id'       => $tenantId,
        'logo_path'       => '/logoo.png',
        'hero_headline'   => 'Premier Performance CPA Network',
        'hero_sub'        => 'Join us to access exclusive high-converting tracking campaigns and fast payouts.',
        'primary_color'   => '#6366F1',
        'secondary_color' => '#8B5CF6',
        'dark_mode'       => 1,
        'created_at'      => date('Y-m-d H:i:s')
    ]);
    $lp = Database::fetchOne("SELECT * FROM `landing_pages` WHERE id = ?", [$lpId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !Auth::verifyCsrf($_POST['csrf_token'])) {
        $error = 'CSRF token verification failed.';
    } else {
        $headline       = trim($_POST['hero_headline'] ?? '');
        $subheadline    = trim($_POST['hero_sub'] ?? '');
        $primaryColor   = trim($_POST['primary_color'] ?? '#6366F1');
        $secondaryColor = trim($_POST['secondary_color'] ?? '#8B5CF6');
        $darkMode       = isset($_POST['dark_mode']) ? 1 : 0;
        $logoPath       = trim($_POST['logo_path'] ?? '');

        // File upload handling for Logo
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['logo_file']['tmp_name'];
            $fileName = $_FILES['logo_file']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg', 'webp', 'svg'];
            if (in_array($fileExtension, $allowedfileExtensions)) {
                $uploadFileDir = BASE_PATH . '/uploads/landing/';
                if (!is_dir($uploadFileDir)) {
                    @mkdir($uploadFileDir, 0755, true);
                }
                $newFileName = 'logo_' . $tenantId . '_' . time() . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $logoPath = '/uploads/landing/' . $newFileName;
                }
            }
        }

        try {
            Database::begin();

            // 1. Update landing_pages
            Database::update('landing_pages', [
                'logo_path'       => $logoPath ?: ($lp['logo_path'] ?? '/logoo.png'),
                'hero_headline'   => $headline,
                'hero_sub'        => $subheadline,
                'primary_color'   => $primaryColor,
                'secondary_color' => $secondaryColor,
                'dark_mode'       => $darkMode
            ], 'tenant_id = ?', [$tenantId]);

            // 2. Save Custom Content & Feature Items
            $contents = [
                'item_1_title' => trim($_POST['item_1_title'] ?? 'Real-time Tracking'),
                'item_1_desc'  => trim($_POST['item_1_desc'] ?? ''),
                'item_2_title' => trim($_POST['item_2_title'] ?? 'Smart Rotators'),
                'item_2_desc'  => trim($_POST['item_2_desc'] ?? ''),
                'item_3_title' => trim($_POST['item_3_title'] ?? 'Safe Payments'),
                'item_3_desc'  => trim($_POST['item_3_desc'] ?? ''),
                'q_1'          => trim($_POST['q_1'] ?? 'How to register?'),
                'a_1'          => trim($_POST['a_1'] ?? 'Click Create Admin Account to start now.')
            ];

            foreach ($contents as $key => $val) {
                // Determine section key
                $secKey = (strpos($key, 'item_') === 0) ? 'features' : 'faq';
                
                // Safe upsert into page_content
                $existingContent = Database::fetchOne(
                    "SELECT id FROM `page_content` WHERE tenant_id = ? AND section_key = ? AND content_key = ?",
                    [$tenantId, $secKey, $key]
                );
                
                if ($existingContent) {
                    Database::update('page_content', [
                        'content_value' => $val
                    ], 'id = ?', [$existingContent['id']]);
                } else {
                    Database::insert('page_content', [
                        'tenant_id'     => $tenantId,
                        'section_key'   => $secKey,
                        'content_key'   => $key,
                        'content_value' => $val
                    ]);
                }
            }

            // 3. Save SEO configs
            $seoKeys = ['seo_title', 'seo_description', 'seo_keywords'];
            foreach ($seoKeys as $skey) {
                $sval = trim($_POST[$skey] ?? '');
                
                $existingSetting = Database::fetchOne(
                    "SELECT id FROM `landing_page_settings` WHERE tenant_id = ? AND setting_key = ?",
                    [$tenantId, $skey]
                );
                
                if ($existingSetting) {
                    Database::update('landing_page_settings', [
                        'setting_value' => $sval
                    ], 'id = ?', [$existingSetting['id']]);
                } else {
                    Database::insert('landing_page_settings', [
                        'tenant_id'     => $tenantId,
                        'setting_key'   => $skey,
                        'setting_value' => $sval
                    ]);
                }
            }

            Database::commit();
            $success = 'Landing page changes successfully saved!';
            Helpers::redirect('/admin/landing/builder?success=' . urlencode($success));
        } catch (\Throwable $e) {
            Database::rollback();
            $error = 'Failed to save landing page: ' . $e->getMessage();
        }
    }
}

// Fetch content & settings
$seoTitle = Database::fetchOne("SELECT setting_value FROM `landing_page_settings` WHERE tenant_id = ? AND setting_key = ?", [$tenantId, 'seo_title'])['setting_value'] ?? '';
$seoDesc = Database::fetchOne("SELECT setting_value FROM `landing_page_settings` WHERE tenant_id = ? AND setting_key = ?", [$tenantId, 'seo_description'])['setting_value'] ?? '';
$seoKeywords = Database::fetchOne("SELECT setting_value FROM `landing_page_settings` WHERE tenant_id = ? AND setting_key = ?", [$tenantId, 'seo_keywords'])['setting_value'] ?? '';

// Fetch dynamic contents
$contentRows = Database::fetchAll("SELECT * FROM `page_content` WHERE tenant_id = ?", [$tenantId]);
$content = [];
foreach ($contentRows as $row) {
    $content[$row['content_key']] = $row['content_value'];
}

$pageTitle = 'Appearance & Page Builder';
require BASE_PATH . '/views/admin/landing/builder.php';
