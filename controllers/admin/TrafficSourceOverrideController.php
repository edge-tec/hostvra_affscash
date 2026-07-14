<?php
/**
 * Traffic Source Override — Admin Management Controller
 *
 * Manages chat traffic source override rules. Only admins can access this page.
 * Follows the VpnProxySkipController pattern.
 */
Auth::check('admin');
$pageTitle = 'Traffic Source Override';

TrafficSourceOverride::ensureSchema();

$adminId = (int)(Auth::id() ?? 0);

// ── POST actions ─────────────────────────────────────────────────────────────
if (Helpers::isPost()) {
    Auth::verifyCsrf(Helpers::postRaw('_token'));
    $action = Helpers::post('action');

    // ── Toggle global setting ────────────────────────────────────────────
    if ($action === 'toggle_global') {
        $currentlyEnabled = TrafficSourceOverride::isGlobalEnabled();
        TrafficSourceOverride::setGlobalEnabled(!$currentlyEnabled);

        // Audit log for global toggle
        try {
            Database::insert('traffic_source_override_logs', [
                'rule_id'         => null,
                'affiliate_id'    => null,
                'action'          => $currentlyEnabled ? 'global_disabled' : 'global_enabled',
                'original_source' => null,
                'override_source' => null,
                'old_value'       => $currentlyEnabled ? 'enabled' : 'disabled',
                'new_value'       => $currentlyEnabled ? 'disabled' : 'enabled',
                'admin_id'        => $adminId,
                'admin_email'     => $_SESSION['email'] ?? null,
                'ip_address'      => substr(Helpers::getIp(), 0, 45),
            ]);
        } catch (\Throwable $e) {}

        Helpers::flash('success', 'Traffic Source Override ' . ($currentlyEnabled ? 'disabled' : 'enabled') . ' globally.');
    }

    // ── Add new rule ─────────────────────────────────────────────────────
    elseif ($action === 'add_rule') {
        $affId          = Helpers::post('affiliate_id');
        $originalSource = strtolower(trim((string)Helpers::post('original_source')));
        $overrideSource = strtolower(trim((string)Helpers::post('override_source')));

        // Validate original source
        if (!in_array($originalSource, TrafficSourceOverride::CHAT_SOURCES, true)) {
            Helpers::flash('error', 'Invalid original source selected.');
        }
        // Validate override destination
        elseif (!isset(TrafficSourceOverride::OVERRIDE_DESTINATIONS[$overrideSource])) {
            Helpers::flash('error', 'Invalid override destination selected.');
        }
        else {
            $affiliateId = ($affId === '' || $affId === null || $affId === '0') ? null : (int)$affId;

            // Check if affiliate exists (when specified)
            if ($affiliateId !== null) {
                $exists = Database::fetchOne("SELECT id FROM affiliates WHERE id=?", [$affiliateId]);
                if (!$exists) {
                    Helpers::flash('error', 'Affiliate not found.');
                    Helpers::redirect('/admin/traffic-source-override');
                    return;
                }
            }

            // Check for duplicate rule
            if ($affiliateId !== null) {
                $dup = Database::fetchOne(
                    "SELECT id FROM traffic_source_override_rules WHERE affiliate_id=? AND original_source=?",
                    [$affiliateId, $originalSource]
                );
            } else {
                $dup = Database::fetchOne(
                    "SELECT id FROM traffic_source_override_rules WHERE affiliate_id IS NULL AND original_source=?",
                    [$originalSource]
                );
            }

            if ($dup) {
                $label = $affiliateId ? "Affiliate #$affiliateId" : 'All Affiliates';
                Helpers::flash('error', "A rule for " . TrafficSourceOverride::sourceLabel($originalSource) . " already exists for $label.");
            } else {
                $newId = TrafficSourceOverride::addRule($affiliateId, $originalSource, $overrideSource, $adminId);
                if ($newId) {
                    $label = $affiliateId ? "Affiliate #$affiliateId" : 'All Affiliates';
                    Helpers::flash('success', "Override rule added: " . TrafficSourceOverride::sourceLabel($originalSource) . " → " . TrafficSourceOverride::destinationLabel($overrideSource) . " ($label)");
                } else {
                    Helpers::flash('error', 'Failed to add override rule.');
                }
            }
        }
    }

    // ── Edit rule (update override destination) ──────────────────────────
    elseif ($action === 'edit_rule') {
        $ruleId         = (int)Helpers::post('rule_id');
        $overrideSource = strtolower(trim((string)Helpers::post('override_source')));

        if (!isset(TrafficSourceOverride::OVERRIDE_DESTINATIONS[$overrideSource])) {
            Helpers::flash('error', 'Invalid override destination selected.');
        } else {
            if (TrafficSourceOverride::updateRule($ruleId, $overrideSource, $adminId)) {
                Helpers::flash('success', 'Override rule updated successfully.');
            } else {
                Helpers::flash('error', 'Failed to update rule.');
            }
        }
    }

    // ── Toggle rule enabled/disabled ─────────────────────────────────────
    elseif ($action === 'toggle_rule') {
        $ruleId = (int)Helpers::post('rule_id');
        if (TrafficSourceOverride::toggleRule($ruleId, $adminId)) {
            Helpers::flash('success', 'Rule toggled successfully.');
        } else {
            Helpers::flash('error', 'Failed to toggle rule.');
        }
    }

    // ── Delete rule ──────────────────────────────────────────────────────
    elseif ($action === 'delete_rule') {
        $ruleId = (int)Helpers::post('rule_id');
        if (TrafficSourceOverride::deleteRule($ruleId, $adminId)) {
            Helpers::flash('success', 'Override rule deleted.');
        } else {
            Helpers::flash('error', 'Failed to delete rule.');
        }
    }

    Helpers::redirect('/admin/traffic-source-override');
}

// ── Load data for view ───────────────────────────────────────────────────────
$globalEnabled = TrafficSourceOverride::isGlobalEnabled();
$rules         = TrafficSourceOverride::getRules();
$auditLog      = TrafficSourceOverride::getAuditLog(30);

// Affiliate dropdown — all active affiliates
try {
    $affiliates = Database::fetchAll(
        "SELECT a.id, a.affiliate_code, CONCAT(u.first_name,' ',u.last_name) AS name, u.email
           FROM affiliates a
      LEFT JOIN users u ON u.id = a.user_id
          WHERE u.status = 'active'
       ORDER BY a.id ASC"
    ) ?: [];
} catch (\Throwable $e) { $affiliates = []; }

require BASE_PATH . '/views/admin/traffic_source_override/index.php';
