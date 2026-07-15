<?php
/**
 * Advanced Traffic Source Override — Admin Management Controller
 */
Auth::check('admin');
$pageTitle = 'Traffic Source Override';

require_once BASE_PATH . '/core/AdvancedTrafficSourceOverride.php';
AdvancedTrafficSourceOverride::ensureSchema();

$adminId = (int)(Auth::id() ?? 0);

// ── POST actions ─────────────────────────────────────────────────────────────
if (Helpers::isPost()) {
    Auth::verifyCsrf(Helpers::postRaw('_token'));
    $action = Helpers::post('action');

    if ($action === 'toggle_global') {
        $currentlyEnabled = AdvancedTrafficSourceOverride::isGlobalEnabled();
        AdvancedTrafficSourceOverride::setGlobalEnabled(!$currentlyEnabled);
        Helpers::flash('success', 'Traffic Source Override ' . ($currentlyEnabled ? 'disabled' : 'enabled') . ' globally.');
    }
    elseif ($action === 'add_rule' || $action === 'edit_rule') {
        $data = [
            'name'                    => Helpers::post('name'),
            'enabled'                 => (int)Helpers::post('enabled', 1),
            'priority'                => (int)Helpers::post('priority', 0),
            'override_source'         => Helpers::post('override_source'),
        ];
        
        // Parse target sources
        $targetSources = $_POST['target_original_sources'] ?? null;
        if (is_array($targetSources)) {
            $data['target_original_sources'] = array_values(array_filter(array_map('trim', $targetSources)));
        } else {
            $data['target_original_sources'] = !empty($targetSources) ? array_map('trim', explode(',', $targetSources)) : [];
        }
        
        // Parse conditions
        $conditions = [];
        
        $affIds = $_POST['affiliate_ids'] ?? null;
        if (is_array($affIds) && !empty($affIds)) {
            $conditions['affiliate_ids'] = array_values(array_filter(array_map('intval', $affIds)));
        } elseif (!empty($affIds)) {
            $conditions['affiliate_ids'] = array_map('intval', explode(',', $affIds));
        }
        
        $offerIds = $_POST['offer_ids'] ?? null;
        if (is_array($offerIds) && !empty($offerIds)) {
            $conditions['offer_ids'] = array_values(array_filter(array_map('intval', $offerIds)));
        } elseif (!empty($offerIds)) {
            $conditions['offer_ids'] = array_map('intval', explode(',', $offerIds));
        }
        
        $advIds = $_POST['advertiser_ids'] ?? null;
        if (is_array($advIds) && !empty($advIds)) {
            $conditions['advertiser_ids'] = array_values(array_filter(array_map('intval', $advIds)));
        } elseif (!empty($advIds)) {
            $conditions['advertiser_ids'] = array_map('intval', explode(',', $advIds));
        }
        
        $countries = $_POST['countries'] ?? null;
        if (is_array($countries) && !empty($countries)) {
            $conditions['countries'] = array_values(array_filter(array_map('trim', $countries)));
        } elseif (!empty($countries)) {
            $conditions['countries'] = array_map('trim', explode(',', $countries));
        }
        
        $deviceTypes = $_POST['device_types'] ?? null;
        if (is_array($deviceTypes) && !empty($deviceTypes)) {
            $conditions['device_types'] = array_values(array_filter(array_map('trim', $deviceTypes)));
        } elseif (!empty($deviceTypes)) {
            $conditions['device_types'] = array_map('trim', explode(',', $deviceTypes));
        }

        $data['conditions'] = $conditions;

        if ($action === 'add_rule') {
            AdvancedTrafficSourceOverride::addRule($data);
            Helpers::flash('success', 'Override rule added.');
        } else {
            $ruleId = (int)Helpers::post('rule_id');
            AdvancedTrafficSourceOverride::updateRule($ruleId, $data);
            Helpers::flash('success', 'Override rule updated.');
        }
    }
    elseif ($action === 'toggle_rule') {
        $ruleId = (int)Helpers::post('rule_id');
        AdvancedTrafficSourceOverride::toggleRule($ruleId);
        Helpers::flash('success', 'Rule toggled.');
    }
    elseif ($action === 'delete_rule') {
        $ruleId = (int)Helpers::post('rule_id');
        AdvancedTrafficSourceOverride::deleteRule($ruleId);
        Helpers::flash('success', 'Rule deleted.');
    }

    Helpers::redirect('/admin/traffic-source-override');
}

// ── Load data for view ───────────────────────────────────────────────────────
$globalEnabled = AdvancedTrafficSourceOverride::isGlobalEnabled();
$rules         = AdvancedTrafficSourceOverride::getAllRules();

$affiliates = Database::fetchAll("SELECT af.id, CONCAT(u.first_name, ' ', u.last_name) as name FROM affiliates af JOIN users u ON u.id = af.user_id ORDER BY name") ?: [];
$offers = Database::fetchAll("SELECT id, name FROM offers ORDER BY name") ?: [];
$advertisers = Database::fetchAll("SELECT adv.id, COALESCE(NULLIF(u.company, ''), CONCAT(u.first_name, ' ', u.last_name)) as name FROM advertisers adv JOIN users u ON u.id = adv.user_id ORDER BY name") ?: [];

require BASE_PATH . '/views/admin/traffic_source_override/index.php';
