<?php
/**
 * AiSeoController — Admin Controller for Enterprise AI SEO & GEO Management System
 */
Auth::check('admin');
$pageTitle = 'Enterprise AI SEO Manager';

// Ensure all core AI SEO classes are loaded
require_once BASE_PATH . '/core/AiSeoEngine.php';
require_once BASE_PATH . '/core/AiSchemaGenerator.php';
require_once BASE_PATH . '/core/GeoOptimizer.php';
require_once BASE_PATH . '/core/AiCrawlerManager.php';
require_once BASE_PATH . '/core/LlmsTxtGenerator.php';
require_once BASE_PATH . '/core/XmlSitemapGenerator.php';
require_once BASE_PATH . '/core/PerformanceOptimizer.php';

AiSeoEngine::initSchema();

$activeTab = Helpers::get('tab') ?: 'dashboard';
$subAction = Helpers::post('action') ?: Helpers::get('action');

// ── AJAX POST Actions ────────────────────────────────────────────────────────
if (Helpers::isPost()) {
    $response = ['ok' => false, 'message' => 'Invalid action'];

    // 1. Save Global Settings
    if ($subAction === 'save_global_settings') {
        $keys = [
            'ai_seo_enabled', 'auto_schema_enabled', 'geo_optimization_enabled', 'llms_txt_enabled',
            'brand_organization_name', 'brand_organization_url', 'brand_logo_url',
            'default_meta_title', 'default_meta_description'
        ];
        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                AiSeoEngine::setSetting($k, trim($_POST[$k]));
            }
        }
        Helpers::flash('success', 'Global AI SEO settings saved successfully.');
        Helpers::redirect('/admin/ai-seo?tab=global_settings');
    }

    // 2. Save Page Metadata
    elseif ($subAction === 'save_page_meta') {
        $pageUrl = '/' . ltrim(trim(Helpers::postRaw('page_url')), '/');
        $title = trim(Helpers::postRaw('title'));
        $desc = trim(Helpers::postRaw('meta_description'));
        $canonical = trim(Helpers::postRaw('canonical_url'));
        $robots = trim(Helpers::postRaw('robots_meta')) ?: 'index, follow';
        $primaryEntity = trim(Helpers::postRaw('primary_entity'));
        $aiSummary = trim(Helpers::postRaw('ai_summary'));

        Database::query(
            "INSERT INTO `ai_seo_pages` (`page_url`, `title`, `meta_description`, `canonical_url`, `robots_meta`, `primary_entity`, `ai_summary`)
             VALUES (?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
               `title` = VALUES(`title`),
               `meta_description` = VALUES(`meta_description`),
               `canonical_url` = VALUES(`canonical_url`),
               `robots_meta` = VALUES(`robots_meta`),
               `primary_entity` = VALUES(`primary_entity`),
               `ai_summary` = VALUES(`ai_summary`)",
            [$pageUrl, $title, $desc, $canonical, $robots, $primaryEntity, $aiSummary]
        );

        Helpers::flash('success', 'Page SEO metadata saved for ' . htmlspecialchars($pageUrl));
        Helpers::redirect('/admin/ai-seo?tab=pages');
    }

    // 3. Save Keyword
    elseif ($subAction === 'save_keyword') {
        $kw = trim(Helpers::postRaw('keyword'));
        $target = trim(Helpers::postRaw('target_url'));
        $entity = trim(Helpers::postRaw('entity_name'));
        $vol = (int)Helpers::post('search_volume');

        if ($kw) {
            Database::query(
                "INSERT INTO `ai_seo_keywords` (`keyword`, `target_url`, `entity_name`, `search_volume`)
                 VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE `target_url`=VALUES(`target_url`), `entity_name`=VALUES(`entity_name`), `search_volume`=VALUES(`search_volume`)",
                [$kw, $target, $entity, $vol]
            );
            Helpers::flash('success', 'Keyword saved successfully.');
        }
        Helpers::redirect('/admin/ai-seo?tab=keywords');
    }

    // 4. Delete Keyword
    elseif ($subAction === 'delete_keyword') {
        $id = (int)Helpers::post('id');
        Database::query("DELETE FROM `ai_seo_keywords` WHERE `id` = ?", [$id]);
        Helpers::flash('success', 'Keyword removed.');
        Helpers::redirect('/admin/ai-seo?tab=keywords');
    }

    // 5. Save Entity
    elseif ($subAction === 'save_entity') {
        $name = trim(Helpers::postRaw('name'));
        $type = trim(Helpers::postRaw('entity_type')) ?: 'Topic';
        $desc = trim(Helpers::postRaw('description'));
        $wiki = trim(Helpers::postRaw('wikipedia_url'));
        $cat  = trim(Helpers::postRaw('category')) ?: 'General';

        if ($name) {
            Database::query(
                "INSERT INTO `ai_seo_entities` (`name`, `entity_type`, `description`, `wikipedia_url`, `category`)
                 VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE `entity_type`=VALUES(`entity_type`), `description`=VALUES(`description`), `wikipedia_url`=VALUES(`wikipedia_url`), `category`=VALUES(`category`)",
                [$name, $type, $desc, $wiki, $cat]
            );
            Helpers::flash('success', 'Entity saved successfully.');
        }
        Helpers::redirect('/admin/ai-seo?tab=entities');
    }

    // 6. Delete Entity
    elseif ($subAction === 'delete_entity') {
        $id = (int)Helpers::post('id');
        Database::query("DELETE FROM `ai_seo_entities` WHERE `id` = ?", [$id]);
        Helpers::flash('success', 'Entity deleted.');
        Helpers::redirect('/admin/ai-seo?tab=entities');
    }

    // 7. Save FAQ Item
    elseif ($subAction === 'save_faq') {
        $target = '/' . ltrim(trim(Helpers::postRaw('target_url')), '/');
        $q = trim(Helpers::postRaw('question'));
        $a = trim(Helpers::postRaw('answer'));

        if ($q && $a) {
            Database::query(
                "INSERT INTO `ai_seo_faqs` (`target_url`, `question`, `answer`) VALUES (?,?,?)",
                [$target, $q, $a]
            );
            Helpers::flash('success', 'FAQ question added successfully.');
        }
        Helpers::redirect('/admin/ai-seo?tab=faqs');
    }

    // 8. Delete FAQ Item
    elseif ($subAction === 'delete_faq') {
        $id = (int)Helpers::post('id');
        Database::query("DELETE FROM `ai_seo_faqs` WHERE `id` = ?", [$id]);
        Helpers::flash('success', 'FAQ question deleted.');
        Helpers::redirect('/admin/ai-seo?tab=faqs');
    }

    // 9. Save HowTo Item
    elseif ($subAction === 'save_howto') {
        $target = '/' . ltrim(trim(Helpers::postRaw('target_url')), '/');
        $title = trim(Helpers::postRaw('title'));
        $desc = trim(Helpers::postRaw('description'));
        $stepsRaw = trim(Helpers::postRaw('steps_json'));

        if ($title && $stepsRaw) {
            Database::query(
                "INSERT INTO `ai_seo_howtos` (`target_url`, `title`, `description`, `steps_json`) VALUES (?,?,?,?)",
                [$target, $title, $desc, $stepsRaw]
            );
            Helpers::flash('success', 'HowTo guide created successfully.');
        }
        Helpers::redirect('/admin/ai-seo?tab=howtos');
    }

    // 10. Save Redirect Rule
    elseif ($subAction === 'save_redirect') {
        $src = '/' . ltrim(trim(Helpers::postRaw('source_url')), '/');
        $tgt = trim(Helpers::postRaw('target_url'));
        $code = (int)Helpers::post('status_code') ?: 301;

        if ($src && $tgt) {
            Database::query(
                "INSERT INTO `ai_seo_redirects` (`source_url`, `target_url`, `status_code`) VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE `target_url`=VALUES(`target_url`), `status_code`=VALUES(`status_code`)",
                [$src, $tgt, $code]
            );
            Helpers::flash('success', 'Redirect rule saved.');
        }
        Helpers::redirect('/admin/ai-seo?tab=redirects');
    }

    // 11. Delete Redirect
    elseif ($subAction === 'delete_redirect') {
        $id = (int)Helpers::post('id');
        Database::query("DELETE FROM `ai_seo_redirects` WHERE `id` = ?", [$id]);
        Helpers::flash('success', 'Redirect deleted.');
        Helpers::redirect('/admin/ai-seo?tab=redirects');
    }

    // 12. Save AI Citation Snippet
    elseif ($subAction === 'save_citation') {
        $target = '/' . ltrim(trim(Helpers::postRaw('target_url')), '/');
        $title  = trim(Helpers::postRaw('topic_title'));
        $def    = trim(Helpers::postRaw('definition'));
        $quick  = trim(Helpers::postRaw('quick_answer'));
        $facts  = trim(Helpers::postRaw('key_facts'));
        $pros   = trim(Helpers::postRaw('pros_cons'));

        if ($target && $title) {
            Database::query(
                "INSERT INTO `ai_seo_citations` (`target_url`, `topic_title`, `definition`, `quick_answer`, `key_facts`, `pros_cons`)
                 VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
                 `topic_title`=VALUES(`topic_title`), `definition`=VALUES(`definition`), `quick_answer`=VALUES(`quick_answer`), `key_facts`=VALUES(`key_facts`), `pros_cons`=VALUES(`pros_cons`)",
                [$target, $title, $def, $quick, $facts, $pros]
            );
            Helpers::flash('success', 'AI Citation block saved.');
        }
        Helpers::redirect('/admin/ai-seo?tab=citations');
    }

    // 13. Save AI Crawler Rules
    elseif ($subAction === 'save_crawlers') {
        $crawlers = AiCrawlerManager::getCrawlers();
        foreach ($crawlers as $k => $c) {
            $val = Helpers::post($c['setting_key']) === 'block' ? 'block' : 'allow';
            AiSeoEngine::setSetting($c['setting_key'], $val);
        }
        Helpers::flash('success', 'AI Crawler rules updated.');
        Helpers::redirect('/admin/ai-seo?tab=crawlers');
    }

    // 14. Save Internal Link Rule
    elseif ($subAction === 'save_internal_link') {
        $kw = trim(Helpers::postRaw('keyword'));
        $tgt = trim(Helpers::postRaw('target_url'));

        if ($kw && $tgt) {
            Database::query(
                "INSERT INTO `ai_seo_internal_links` (`keyword`, `target_url`) VALUES (?,?)
                 ON DUPLICATE KEY UPDATE `target_url`=VALUES(`target_url`)",
                [$kw, $tgt]
            );
            Helpers::flash('success', 'Internal link rule added.');
        }
        Helpers::redirect('/admin/ai-seo?tab=internal_links');
    }
}

// Data loaders for tabs
$scores     = AiSeoEngine::getScores();
$pagesList  = Database::fetchAll("SELECT * FROM `ai_seo_pages` ORDER BY id DESC LIMIT 200");
$keywords   = Database::fetchAll("SELECT * FROM `ai_seo_keywords` ORDER BY id DESC LIMIT 200");
$entities   = Database::fetchAll("SELECT * FROM `ai_seo_entities` ORDER BY id DESC LIMIT 200");
$faqsList   = Database::fetchAll("SELECT * FROM `ai_seo_faqs` ORDER BY id DESC LIMIT 200");
$howtosList = Database::fetchAll("SELECT * FROM `ai_seo_howtos` ORDER BY id DESC LIMIT 200");
$redirects  = Database::fetchAll("SELECT * FROM `ai_seo_redirects` ORDER BY id DESC LIMIT 200");
$citations  = Database::fetchAll("SELECT * FROM `ai_seo_citations` ORDER BY id DESC LIMIT 200");
$linksList  = Database::fetchAll("SELECT * FROM `ai_seo_internal_links` ORDER BY id DESC LIMIT 200");
$auditLogs  = Database::fetchAll("SELECT * FROM `ai_seo_audit_logs` WHERE `is_resolved`=0 ORDER BY id DESC LIMIT 200");

require BASE_PATH . '/views/admin/ai_seo/index.php';
