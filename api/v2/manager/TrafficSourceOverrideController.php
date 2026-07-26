<?php
/**
 * Manager App API — Traffic Source Override Rules & Options (Permission Checked)
 */
Auth::check('affiliate_manager');
require_once BASE_PATH . '/core/ManagerPermissions.php';
require_once BASE_PATH . '/core/AdvancedTrafficSourceOverride.php';

ManagerPermissions::requirePermission('traffic_source_override');
AdvancedTrafficSourceOverride::ensureSchema();

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $_GET['action'] ?? ($input['action'] ?? 'list');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $action !== 'list') {
        if ($action === 'toggle_global') {
            $currentlyEnabled = AdvancedTrafficSourceOverride::isGlobalEnabled();
            AdvancedTrafficSourceOverride::setGlobalEnabled(!$currentlyEnabled);
            Helpers::json([
                'status' => 'success',
                'message' => 'Global override ' . (!$currentlyEnabled ? 'enabled' : 'disabled'),
                'global_enabled' => !$currentlyEnabled
            ]);
            exit;
        }

        if ($action === 'add_rule' || $action === 'edit_rule') {
            $data = [
                'name' => $input['name'] ?? Helpers::post('name', 'Override Rule'),
                'enabled' => (int)($input['enabled'] ?? Helpers::post('enabled', 1)),
                'priority' => (int)($input['priority'] ?? Helpers::post('priority', 0)),
                'override_source' => $input['override_source'] ?? Helpers::post('override_source'),
            ];

            $targetSources = $input['target_original_sources'] ?? ($_POST['target_original_sources'] ?? []);
            if (is_array($targetSources)) {
                $data['target_original_sources'] = array_values(array_filter(array_map('trim', $targetSources)));
            } else {
                $data['target_original_sources'] = !empty($targetSources) ? array_map('trim', explode(',', $targetSources)) : [];
            }

            $conditions = [];

            // Affiliates condition
            $affIds = $input['affiliate_ids'] ?? ($_POST['affiliate_ids'] ?? ($input['conditions']['affiliate_ids'] ?? []));
            if (is_array($affIds) && !empty($affIds)) {
                $conditions['affiliate_ids'] = array_values(array_filter(array_map('intval', $affIds)));
            }

            // Offers condition
            $offerIds = $input['offer_ids'] ?? ($_POST['offer_ids'] ?? ($input['conditions']['offer_ids'] ?? []));
            if (is_array($offerIds) && !empty($offerIds)) {
                $conditions['offer_ids'] = array_values(array_filter(array_map('intval', $offerIds)));
            }

            // Advertisers condition
            $advIds = $input['advertiser_ids'] ?? ($_POST['advertiser_ids'] ?? ($input['conditions']['advertiser_ids'] ?? []));
            if (is_array($advIds) && !empty($advIds)) {
                $conditions['advertiser_ids'] = array_values(array_filter(array_map('intval', $advIds)));
            }

            // Countries condition
            $countries = $input['countries'] ?? ($_POST['countries'] ?? ($input['conditions']['countries'] ?? []));
            if (is_array($countries) && !empty($countries)) {
                $conditions['countries'] = array_values(array_filter(array_map('trim', $countries)));
            }

            // Device Types condition
            $deviceTypes = $input['device_types'] ?? ($_POST['device_types'] ?? ($input['conditions']['device_types'] ?? []));
            if (is_array($deviceTypes) && !empty($deviceTypes)) {
                $conditions['device_types'] = array_values(array_filter(array_map('trim', $deviceTypes)));
            }

            $data['conditions'] = $conditions;

            if ($action === 'add_rule') {
                AdvancedTrafficSourceOverride::addRule($data);
                Helpers::json(['status' => 'success', 'message' => 'Override rule created successfully']);
            } else {
                $ruleId = (int)($input['rule_id'] ?? Helpers::post('rule_id'));
                AdvancedTrafficSourceOverride::updateRule($ruleId, $data);
                Helpers::json(['status' => 'success', 'message' => 'Override rule updated successfully']);
            }
            exit;
        }

        if ($action === 'toggle_rule') {
            $ruleId = (int)($input['rule_id'] ?? Helpers::post('rule_id'));
            AdvancedTrafficSourceOverride::toggleRule($ruleId);
            Helpers::json(['status' => 'success', 'message' => 'Rule toggled']);
            exit;
        }

        if ($action === 'delete_rule') {
            $ruleId = (int)($input['rule_id'] ?? Helpers::post('rule_id'));
            AdvancedTrafficSourceOverride::deleteRule($ruleId);
            Helpers::json(['status' => 'success', 'message' => 'Rule deleted']);
            exit;
        }
    }

    // List action
    $globalEnabled = AdvancedTrafficSourceOverride::isGlobalEnabled();
    $rawRules = AdvancedTrafficSourceOverride::getAllRules();

    $rules = [];
    foreach ($rawRules as $r) {
        $rules[] = [
            'id' => (int)$r['id'],
            'name' => $r['name'] ?? '',
            'enabled' => (int)($r['enabled'] ?? 1) === 1,
            'priority' => (int)($r['priority'] ?? 0),
            'target_original_sources' => json_decode($r['target_original_sources'] ?? '[]', true) ?: [],
            'override_source' => $r['override_source'] ?? '',
            'conditions' => json_decode($r['conditions'] ?? '{}', true) ?: (object)[]
        ];
    }

    $affiliates = Database::fetchAll("SELECT af.id, COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''), u.username, u.email, CONCAT('Affiliate #', af.id)) as name, COALESCE(af.affiliate_code, CONCAT('AFF', af.id)) as affiliate_code FROM affiliates af JOIN users u ON u.id = af.user_id ORDER BY name") ?: [];
    $offers = Database::fetchAll("SELECT id, COALESCE(NULLIF(TRIM(name), ''), CONCAT('Offer #', id)) as name FROM offers ORDER BY name") ?: [];
    $advertisers = Database::fetchAll("SELECT ad.id, COALESCE(NULLIF(TRIM(ad.company_name), ''), NULLIF(TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))), ''), u.username, u.email, CONCAT('Advertiser #', ad.id)) as name FROM advertisers ad JOIN users u ON u.id = ad.user_id ORDER BY name") ?: [];

    $chatSources = [
        'Unknown',
        'Direct',
        'WhatsApp',
        'Telegram',
        'Facebook Messenger',
        'Instagram Direct',
        'Threads',
        'Discord',
        'Skype',
        'Signal',
        'WeChat',
        'LINE',
        'Viber',
        'Reddit',
        'TikTok'
    ];

    $overrideDestinations = [
        ['key' => 'Gmail', 'label' => 'Gmail'],
        ['key' => 'Email', 'label' => 'Email'],
        ['key' => 'Google Search', 'label' => 'Google Search'],
        ['key' => 'Google Ads', 'label' => 'Google Ads'],
        ['key' => 'Paid Ads', 'label' => 'Paid Ads'],
        ['key' => 'Display Ads', 'label' => 'Display Ads'],
        ['key' => 'Organic', 'label' => 'SEO / Organic'],
        ['key' => 'Social', 'label' => 'Social'],
        ['key' => 'Native Ads', 'label' => 'Native Ads'],
        ['key' => 'Push', 'label' => 'Push'],
        ['key' => 'Other', 'label' => 'Other']
    ];

    $deviceTypes = ['Mobile', 'Desktop', 'Tablet'];
    $countries = [
        ['code' => 'US', 'name' => 'United States'],
        ['code' => 'GB', 'name' => 'United Kingdom'],
        ['code' => 'CA', 'name' => 'Canada'],
        ['code' => 'AU', 'name' => 'Australia'],
        ['code' => 'DE', 'name' => 'Germany'],
        ['code' => 'FR', 'name' => 'France'],
        ['code' => 'BD', 'name' => 'Bangladesh'],
        ['code' => 'IN', 'name' => 'India'],
        ['code' => 'BR', 'name' => 'Brazil'],
        ['code' => 'AE', 'name' => 'United Arab Emirates']
    ];

    Helpers::json([
        'status' => 'success',
        'data' => [
            'global_enabled' => $globalEnabled,
            'rules' => $rules,
            'chat_sources' => $chatSources,
            'destinations' => $overrideDestinations,
            'affiliates' => $affiliates,
            'offers' => $offers,
            'advertisers' => $advertisers,
            'countries' => $countries,
            'device_types' => $deviceTypes
        ]
    ]);

} catch (\Throwable $e) {
    Helpers::json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
