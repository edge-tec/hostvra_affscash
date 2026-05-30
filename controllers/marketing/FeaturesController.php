<?php
/**
 * Marketing - Features Page Controller
 */
$tenantId = Tenant::getTenantId();

if ($tenantId !== null) {
    // Resolved as Tenant Admin Custom Page
    $lp = Database::fetchOne("SELECT * FROM `landing_pages` WHERE tenant_id = ?", [$tenantId]);
    if (!$lp) {
        $lp = [
            'logo_path' => '/logoo.png',
            'hero_headline' => 'CPA Performance & Smartlink Network',
            'hero_sub' => 'Optimize conversions and maximize ROI through hand-picked tracking campaigns.',
            'primary_color' => '#6366F1',
            'secondary_color' => '#8B5CF6',
            'dark_mode' => 1
        ];
    }
    $pageTitle = Database::fetchOne("SELECT setting_value FROM `landing_page_settings` WHERE tenant_id = ? AND setting_key = ?", [$tenantId, 'seo_title'])['setting_value'] ?? 'CPA Performance Network';
    $seoDesc = Database::fetchOne("SELECT setting_value FROM `landing_page_settings` WHERE tenant_id = ? AND setting_key = ?", [$tenantId, 'seo_description'])['setting_value'] ?? '';
    
    // Fetch page contents
    $contentRows = Database::fetchAll("SELECT * FROM `page_content` WHERE tenant_id = ?", [$tenantId]);
    $content = [];
    foreach ($contentRows as $row) {
        $content[$row['content_key']] = $row['content_value'];
    }
} else {
    // Global Super Admin SaaS platform context
    $pageTitle = 'Enterprise Features & Performance';
}

require BASE_PATH . '/views/marketing/features.php';

