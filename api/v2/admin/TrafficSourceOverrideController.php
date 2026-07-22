<?php
/**
 * Admin App API — Traffic Source Override Rules & Options
 */
Auth::check('admin');
require_once BASE_PATH . '/core/AdvancedTrafficSourceOverride.php';
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
                'name' => $input['name'] ?? Helpers::post('name'),
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
            $affIds = $input['affiliate_ids'] ?? ($_POST['affiliate_ids'] ?? []);
            if (is_array($affIds)) $conditions['affiliate_ids'] = array_values(array_filter(array_map('intval', $affIds)));
            
            $offerIds = $input['offer_ids'] ?? ($_POST['offer_ids'] ?? []);
            if (is_array($offerIds)) $conditions['offer_ids'] = array_values(array_filter(array_map('intval', $offerIds)));

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
    $rules = AdvancedTrafficSourceOverride::getAllRules();

    $affiliates = Database::fetchAll("SELECT af.id, CONCAT(u.first_name, ' ', u.last_name) as name, af.affiliate_code FROM affiliates af JOIN users u ON u.id = af.user_id ORDER BY name") ?: [];
    $offers = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];

    $chatSources = ['telegram', 'whatsapp', 'messenger', 'discord', 'signal', 'viber'];
    $overrideDestinations = [
        ['key' => 'organic', 'label' => 'SEO / Organic'],
        ['key' => 'paid_ads', 'label' => 'Paid Ads'],
        ['key' => 'display', 'label' => 'Display'],
        ['key' => 'email', 'label' => 'Email'],
        ['key' => 'social', 'label' => 'Social'],
        ['key' => 'native_ads', 'label' => 'Native Ads'],
        ['key' => 'push', 'label' => 'Push'],
        ['key' => 'other', 'label' => 'Other']
    ];

    Helpers::json([
        'status' => 'success',
        'data' => [
            'global_enabled' => $globalEnabled,
            'rules' => $rules,
            'chat_sources' => $chatSources,
            'destinations' => $overrideDestinations,
            'affiliates' => $affiliates,
            'offers' => $offers
        ]
    ]);

} catch (\Throwable $e) {
    Helpers::json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
