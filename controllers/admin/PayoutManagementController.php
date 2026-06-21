<?php
Auth::check('admin');
require_once BASE_PATH . '/core/NotificationHelper.php';
$pageTitle = 'Advanced Payout Management';

// ── Schema migrations ─────────────────────────────────────────────────────
$migrations = [
    "CREATE TABLE IF NOT EXISTS `payout_country_rules` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `country`    CHAR(2) NOT NULL,
        `revenue`    DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `payout`     DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_country` (`country`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `payout_device_rules` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `country`    CHAR(2) NOT NULL DEFAULT '',
        `device`     ENUM('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
        `revenue`    DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `payout`     DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_country_device` (`country`,`device`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `aff_daily_caps` (
        `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id` INT UNSIGNED NOT NULL,
        `daily_cap`    INT UNSIGNED NOT NULL DEFAULT 0,
        `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_aff` (`affiliate_id`),
        INDEX `idx_aff` (`affiliate_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `aff_custom_payouts` (
        `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id` INT UNSIGNED NOT NULL,
        `offer_id`     INT UNSIGNED NOT NULL,
        `revenue`      DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_aff_offer` (`affiliate_id`,`offer_id`),
        INDEX `idx_aff` (`affiliate_id`),
        INDEX `idx_offer` (`offer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `aff_country_payouts` (
        `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id` INT UNSIGNED NOT NULL,
        `country`      CHAR(2) NOT NULL,
        `revenue`      DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_aff_country` (`affiliate_id`,`country`),
        INDEX `idx_aff` (`affiliate_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `aff_country_device_payouts` (
        `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id` INT UNSIGNED NOT NULL,
        `country`      CHAR(2) NOT NULL,
        `device`       ENUM('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
        `revenue`      DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_aff_country_device` (`affiliate_id`,`country`,`device`),
        INDEX `idx_aff` (`affiliate_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `aff_smartlink_payouts` (
        `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id` INT UNSIGNED NOT NULL,
        `smartlink_id` INT UNSIGNED NOT NULL,
        `revenue`      DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
        `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_aff_sl` (`affiliate_id`,`smartlink_id`),
        INDEX `idx_aff` (`affiliate_id`),
        INDEX `idx_sl` (`smartlink_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `conversion_optimize_rules` (
        `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `offer_id`       INT UNSIGNED NOT NULL,
        `optimize_value` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
        `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
        `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_offer` (`offer_id`),
        INDEX `idx_offer` (`offer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];
foreach ($migrations as $sql) {
    try { Database::query($sql, []); } catch (\Throwable $e) {}
}
// Add offer_id to aff_custom_payouts for existing installs
try { Database::query("ALTER TABLE `aff_custom_payouts` ADD COLUMN `offer_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `affiliate_id`", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `aff_custom_payouts` ADD UNIQUE KEY `uq_aff_offer` (`affiliate_id`,`offer_id`)", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `aff_custom_payouts` ADD INDEX `idx_offer` (`offer_id`)", []); } catch (\Throwable $e) {}

// Add affiliate_id and offer_id to global country/device payout rules
try { Database::query("ALTER TABLE `payout_country_rules` ADD COLUMN `affiliate_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `payout_country_rules` ADD COLUMN `offer_id` INT UNSIGNED NULL DEFAULT NULL AFTER `affiliate_id`", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `payout_country_rules` DROP INDEX `uq_country`", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `payout_country_rules` ADD UNIQUE KEY `uq_aff_offer_country` (`affiliate_id`,`offer_id`,`country`)", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `payout_country_rules` ADD INDEX `idx_affiliate` (`affiliate_id`)", []); } catch (\Throwable $e) {}

try { Database::query("ALTER TABLE `payout_device_rules` ADD COLUMN `affiliate_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `payout_device_rules` ADD COLUMN `offer_id` INT UNSIGNED NULL DEFAULT NULL AFTER `affiliate_id`", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `payout_device_rules` DROP INDEX `uq_country_device`", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `payout_device_rules` ADD UNIQUE KEY `uq_aff_offer_country_device` (`affiliate_id`,`offer_id`,`country`,`device`)", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `payout_device_rules` ADD INDEX `idx_affiliate` (`affiliate_id`)", []); } catch (\Throwable $e) {}

// Add offer_id to aff_daily_caps
try { Database::query("ALTER TABLE `aff_daily_caps` ADD COLUMN `offer_id` INT UNSIGNED NULL DEFAULT NULL AFTER `affiliate_id`", []); } catch (\Throwable $e) {}

// Add affiliate_id to conversion_optimize_rules
try { Database::query("ALTER TABLE `conversion_optimize_rules` ADD COLUMN `affiliate_id` INT UNSIGNED NULL DEFAULT NULL AFTER `offer_id`", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `conversion_optimize_rules` DROP INDEX `uq_offer`", []); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `conversion_optimize_rules` ADD UNIQUE KEY `uq_offer_aff` (`offer_id`,`affiliate_id`)", []); } catch (\Throwable $e) {}

// ── Helpers ───────────────────────────────────────────────────────────────
$tab    = Helpers::get('tab') ?: 'country_payouts';
$errors = [];
$flash  = Helpers::getFlash();

// Shared lookup data
$affiliateList = Database::fetchAll(
    "SELECT af.id, CONCAT(u.first_name,' ',u.last_name,' (',af.affiliate_code,')') as label
     FROM affiliates af JOIN users u ON u.id=af.user_id
     WHERE u.role='affiliate' AND u.status='active'
     ORDER BY u.first_name, u.last_name"
);
$offerList = Database::fetchAll(
    "SELECT id, name, COALESCE(is_inhouse,0) as is_inhouse FROM offers WHERE status='active' ORDER BY is_inhouse ASC, name ASC"
);
$smartlinkList = Database::fetchAll(
    "SELECT id, name FROM smartlinks WHERE status='active' ORDER BY name"
);

// ── Handle POST ───────────────────────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $postAction = Helpers::post('payout_action');

    // ── Country Payouts ──────────────────────────────────────────────────
    if ($postAction === 'save_country_payout') {
        $tab     = 'country_payouts';
        $country = strtoupper(trim(Helpers::postRaw('country')));
        $revenue = (float)Helpers::postRaw('revenue');
        $payout  = (float)Helpers::postRaw('payout');
        $affId   = (int)Helpers::postRaw('affiliate_id') ?: null;
        $offerId = (int)Helpers::postRaw('offer_id') ?: null;
        if (!preg_match('/^[A-Z]{2}$/', $country)) $errors[] = 'Invalid country code (e.g. US).';
        if (!$errors) {
            $exists = Database::fetchOne(
                "SELECT id FROM payout_country_rules WHERE country=? AND affiliate_id<=>? AND offer_id<=>?",
                [$country, $affId, $offerId]
            );
            if ($exists) {
                Database::query(
                    "UPDATE payout_country_rules SET revenue=?, payout=? WHERE id=?",
                    [$revenue, $payout, $exists['id']]
                );
            } else {
                Database::insert('payout_country_rules', [
                    'affiliate_id' => $affId,
                    'offer_id'     => $offerId,
                    'country'      => $country,
                    'revenue'      => $revenue,
                    'payout'       => $payout,
                ]);
            }
            Helpers::flash('success', "Country payout for $country saved.");
            _notifyAffiliatesOfPayoutChange($affId, "A country payout rule for $country has been updated. Payout is now $" . number_format($payout, 2));
            Helpers::redirect('/admin/payout-management?tab=country_payouts');
        }
    }
    elseif ($postAction === 'delete_country_payout') {
        $id = (int)Helpers::postRaw('rule_id');
        $rule = Database::fetchOne("SELECT affiliate_id FROM payout_country_rules WHERE id=?", [$id]);
        Database::query("DELETE FROM payout_country_rules WHERE id=?", [$id]);
        if ($rule) _notifyAffiliatesOfPayoutChange($rule['affiliate_id'], "A country payout rule has been removed.");
        Helpers::flash('success', 'Country payout rule deleted.');
        Helpers::redirect('/admin/payout-management?tab=country_payouts');
    }

    // ── Device Payouts ───────────────────────────────────────────────────
    elseif ($postAction === 'save_device_payout') {
        $tab     = 'device_payouts';
        $country = strtoupper(trim(Helpers::postRaw('country')));
        $device  = Helpers::post('device');
        $revenue = (float)Helpers::postRaw('revenue');
        $payout  = (float)Helpers::postRaw('payout');
        $affId   = (int)Helpers::postRaw('affiliate_id') ?: null;
        $offerId = (int)Helpers::postRaw('offer_id') ?: null;
        if (!in_array($device, ['desktop','mobile','tablet'])) $errors[] = 'Invalid device type.';
        if (!$errors) {
            $exists = Database::fetchOne(
                "SELECT id FROM payout_device_rules WHERE country=? AND device=? AND affiliate_id<=>? AND offer_id<=>?",
                [$country, $device, $affId, $offerId]
            );
            if ($exists) {
                Database::query(
                    "UPDATE payout_device_rules SET revenue=?, payout=? WHERE id=?",
                    [$revenue, $payout, $exists['id']]
                );
            } else {
                Database::insert('payout_device_rules', [
                    'affiliate_id' => $affId,
                    'offer_id'     => $offerId,
                    'country'      => $country,
                    'device'       => $device,
                    'revenue'      => $revenue,
                    'payout'       => $payout,
                ]);
            }
            Helpers::flash('success', "Device payout rule saved.");
            _notifyAffiliatesOfPayoutChange($affId, "A device payout rule for {$country} ({$device}) has been updated. Payout is now $" . number_format($payout, 2));
            Helpers::redirect('/admin/payout-management?tab=device_payouts');
        }
    }
    elseif ($postAction === 'delete_device_payout') {
        $id = (int)Helpers::postRaw('rule_id');
        $rule = Database::fetchOne("SELECT affiliate_id FROM payout_device_rules WHERE id=?", [$id]);
        Database::query("DELETE FROM payout_device_rules WHERE id=?", [$id]);
        if ($rule) _notifyAffiliatesOfPayoutChange($rule['affiliate_id'], "A device payout rule has been removed.");
        Helpers::flash('success', 'Device payout rule deleted.');
        Helpers::redirect('/admin/payout-management?tab=device_payouts');
    }

    // ── Affiliate Capping ────────────────────────────────────────────────
    elseif ($postAction === 'save_aff_cap') {
        $tab     = 'aff_capping';
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $cap     = (int)Helpers::postRaw('daily_cap');
        $offerId = (int)Helpers::postRaw('offer_id') ?: null;
        if (!$affId) $errors[] = 'Select an affiliate.';
        if ($cap < 0) $errors[] = 'Daily cap must be 0 or greater.';
        if (!$errors) {
            $exists = Database::fetchOne(
                "SELECT id FROM aff_daily_caps WHERE affiliate_id=? AND offer_id<=>?",
                [$affId, $offerId]
            );
            if ($exists) {
                Database::query("UPDATE aff_daily_caps SET daily_cap=? WHERE id=?", [$cap, $exists['id']]);
            } else {
                Database::insert('aff_daily_caps', ['affiliate_id'=>$affId,'offer_id'=>$offerId,'daily_cap'=>$cap]);
            }
            Helpers::flash('success', 'Affiliate daily cap saved.');
            _notifyAffiliatesOfPayoutChange($affId, "Your daily cap has been updated to {$cap}.");
            Helpers::redirect('/admin/payout-management?tab=aff_capping');
        }
    }
    elseif ($postAction === 'delete_aff_cap') {
        $id = (int)Helpers::postRaw('rule_id');
        $rule = Database::fetchOne("SELECT affiliate_id FROM aff_daily_caps WHERE id=?", [$id]);
        Database::query("DELETE FROM aff_daily_caps WHERE id=?", [$id]);
        if ($rule) _notifyAffiliatesOfPayoutChange($rule['affiliate_id'], "A daily cap restriction has been removed.");
        Helpers::flash('success', 'Affiliate cap removed.');
        Helpers::redirect('/admin/payout-management?tab=aff_capping');
    }

    // ── Custom Affiliate Payouts ─────────────────────────────────────────
    elseif ($postAction === 'save_aff_payout') {
        $tab     = 'aff_payouts';
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $offerId = (int)Helpers::postRaw('offer_id');
        $revenue = (float)Helpers::postRaw('revenue');
        $payout  = (float)Helpers::postRaw('payout');
        if (!$affId)   $errors[] = 'Please select an affiliate.';
        if (!$offerId) $errors[] = 'Please select an offer.';
        if (!$errors) {
            // Save to aff_custom_payouts (used by click tracking)
            $exists = Database::fetchOne("SELECT id FROM aff_custom_payouts WHERE affiliate_id=? AND offer_id=?", [$affId, $offerId]);
            if ($exists) {
                Database::query("UPDATE aff_custom_payouts SET revenue=?, payout=? WHERE affiliate_id=? AND offer_id=?", [$revenue, $payout, $affId, $offerId]);
            } else {
                Database::insert('aff_custom_payouts', ['affiliate_id'=>$affId,'offer_id'=>$offerId,'revenue'=>$revenue,'payout'=>$payout]);
            }
            // Also sync custom_payout into affiliate_offers so existing JOIN queries reflect it
            $aoExists = Database::fetchOne("SELECT id FROM affiliate_offers WHERE affiliate_id=? AND offer_id=?", [$affId, $offerId]);
            if ($aoExists) {
                Database::query("UPDATE affiliate_offers SET custom_payout=? WHERE affiliate_id=? AND offer_id=?", [$payout, $affId, $offerId]);
            } else {
                // Create the affiliate_offers row (approved) so the affiliate can see the offer with the custom payout
                Database::query(
                    "INSERT IGNORE INTO affiliate_offers (affiliate_id, offer_id, status, custom_payout, approved_at, approved_by) VALUES (?,?,'approved',?,NOW(),0)",
                    [$affId, $offerId, $payout]
                );
            }
            Helpers::flash('success', 'Custom affiliate payout saved and applied.');
            _notifyAffiliatesOfPayoutChange($affId, "Your custom payout for an offer has been updated to $" . number_format($payout, 2));
            Helpers::redirect('/admin/payout-management?tab=aff_payouts');
        }
    }
    elseif ($postAction === 'delete_aff_payout') {
        $id = (int)Helpers::postRaw('rule_id');
        // Also clear from affiliate_offers.custom_payout
        $rule = Database::fetchOne("SELECT affiliate_id, offer_id FROM aff_custom_payouts WHERE id=?", [$id]);
        if ($rule) {
            Database::query("UPDATE affiliate_offers SET custom_payout=NULL WHERE affiliate_id=? AND offer_id=?", [$rule['affiliate_id'], $rule['offer_id']]);
        }
        Database::query("DELETE FROM aff_custom_payouts WHERE id=?", [$id]);
        if ($rule) _notifyAffiliatesOfPayoutChange($rule['affiliate_id'], "A custom offer payout rule has been removed.");
        Helpers::flash('success', 'Custom payout rule deleted.');
        Helpers::redirect('/admin/payout-management?tab=aff_payouts');
    }

    // ── Custom Country Payouts ───────────────────────────────────────────
    elseif ($postAction === 'save_aff_country_payout') {
        $tab     = 'aff_country_payouts';
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $country = strtoupper(trim(Helpers::postRaw('country')));
        $revenue = (float)Helpers::postRaw('revenue');
        $payout  = (float)Helpers::postRaw('payout');
        if (!$affId) $errors[] = 'Select an affiliate.';
        if (!preg_match('/^[A-Z]{2}$/', $country)) $errors[] = 'Invalid country code (e.g. US).';
        if (!$errors) {
            $exists = Database::fetchOne("SELECT id FROM aff_country_payouts WHERE affiliate_id=? AND country=?", [$affId,$country]);
            if ($exists) {
                Database::query("UPDATE aff_country_payouts SET revenue=?, payout=? WHERE affiliate_id=? AND country=?", [$revenue,$payout,$affId,$country]);
            } else {
                Database::insert('aff_country_payouts', ['affiliate_id'=>$affId,'country'=>$country,'revenue'=>$revenue,'payout'=>$payout]);
            }
            Helpers::flash('success', "Custom country payout saved.");
            _notifyAffiliatesOfPayoutChange($affId, "Your custom country payout for {$country} has been updated to $" . number_format($payout, 2));
            Helpers::redirect('/admin/payout-management?tab=aff_country_payouts');
        }
    }
    elseif ($postAction === 'delete_aff_country_payout') {
        $id = (int)Helpers::postRaw('rule_id');
        $rule = Database::fetchOne("SELECT affiliate_id FROM aff_country_payouts WHERE id=?", [$id]);
        Database::query("DELETE FROM aff_country_payouts WHERE id=?", [$id]);
        if ($rule) _notifyAffiliatesOfPayoutChange($rule['affiliate_id'], "A custom country payout rule has been removed.");
        Helpers::flash('success', 'Custom country payout deleted.');
        Helpers::redirect('/admin/payout-management?tab=aff_country_payouts');
    }

    // ── Custom Country Device Payouts ────────────────────────────────────
    elseif ($postAction === 'save_aff_country_device_payout') {
        $tab     = 'aff_country_device_payouts';
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $country = strtoupper(trim(Helpers::postRaw('country')));
        $device  = Helpers::post('device');
        $revenue = (float)Helpers::postRaw('revenue');
        $payout  = (float)Helpers::postRaw('payout');
        if (!$affId) $errors[] = 'Select an affiliate.';
        if (!preg_match('/^[A-Z]{2}$/', $country)) $errors[] = 'Invalid country code (e.g. US).';
        if (!in_array($device, ['desktop','mobile','tablet'])) $errors[] = 'Invalid device type.';
        if (!$errors) {
            $exists = Database::fetchOne("SELECT id FROM aff_country_device_payouts WHERE affiliate_id=? AND country=? AND device=?", [$affId,$country,$device]);
            if ($exists) {
                Database::query("UPDATE aff_country_device_payouts SET revenue=?, payout=? WHERE affiliate_id=? AND country=? AND device=?", [$revenue,$payout,$affId,$country,$device]);
            } else {
                Database::insert('aff_country_device_payouts', ['affiliate_id'=>$affId,'country'=>$country,'device'=>$device,'revenue'=>$revenue,'payout'=>$payout]);
            }
            Helpers::flash('success', "Custom country/device payout saved.");
            _notifyAffiliatesOfPayoutChange($affId, "Your custom payout for {$country} ({$device}) has been updated to $" . number_format($payout, 2));
            Helpers::redirect('/admin/payout-management?tab=aff_country_device_payouts');
        }
    }
    elseif ($postAction === 'delete_aff_country_device_payout') {
        $id = (int)Helpers::postRaw('rule_id');
        $rule = Database::fetchOne("SELECT affiliate_id FROM aff_country_device_payouts WHERE id=?", [$id]);
        Database::query("DELETE FROM aff_country_device_payouts WHERE id=?", [$id]);
        if ($rule) _notifyAffiliatesOfPayoutChange($rule['affiliate_id'], "A custom country/device payout rule has been removed.");
        Helpers::flash('success', 'Custom country/device payout deleted.');
        Helpers::redirect('/admin/payout-management?tab=aff_country_device_payouts');
    }

    // ── Smartlink Custom Payouts ─────────────────────────────────────────
    elseif ($postAction === 'save_aff_sl_payout') {
        $tab    = 'aff_payouts';
        $affId  = (int)Helpers::postRaw('affiliate_id');
        $slId   = (int)Helpers::postRaw('smartlink_id');
        $revenue = (float)Helpers::postRaw('sl_revenue');
        $payout  = (float)Helpers::postRaw('sl_payout');
        if (!$affId) $errors[] = 'Please select an affiliate.';
        if (!$slId)  $errors[] = 'Please select a smartlink.';
        if (!$errors) {
            $exists = Database::fetchOne("SELECT id FROM aff_smartlink_payouts WHERE affiliate_id=? AND smartlink_id=?", [$affId, $slId]);
            if ($exists) {
                Database::query("UPDATE aff_smartlink_payouts SET revenue=?, payout=? WHERE id=?", [$revenue, $payout, $exists['id']]);
            } else {
                Database::insert('aff_smartlink_payouts', ['affiliate_id'=>$affId,'smartlink_id'=>$slId,'revenue'=>$revenue,'payout'=>$payout]);
            }
            Helpers::flash('success', 'Smartlink custom payout saved.');
            _notifyAffiliatesOfPayoutChange($affId, "Your custom payout for a smartlink has been updated to $" . number_format($payout, 2));
            Helpers::redirect('/admin/payout-management?tab=aff_payouts');
        }
    }
    elseif ($postAction === 'delete_aff_sl_payout') {
        $id = (int)Helpers::postRaw('rule_id');
        $rule = Database::fetchOne("SELECT affiliate_id FROM aff_smartlink_payouts WHERE id=?", [$id]);
        Database::query("DELETE FROM aff_smartlink_payouts WHERE id=?", [$id]);
        if ($rule) _notifyAffiliatesOfPayoutChange($rule['affiliate_id'], "A smartlink custom payout rule has been removed.");
        Helpers::flash('success', 'Smartlink payout rule deleted.');
        Helpers::redirect('/admin/payout-management?tab=aff_payouts');
    }

    // ── Conversion Optimize Rules ────────────────────────────────────────
    elseif ($postAction === 'save_optimize_rule') {
        $tab     = 'conversion_optimize';
        $offerId = (int)Helpers::postRaw('offer_id');
        $affId   = (int)Helpers::postRaw('affiliate_id') ?: null;
        $value   = max(0.01, min(100, (float)Helpers::postRaw('optimize_value')));
        if (!$offerId) $errors[] = 'Select an offer.';
        if (!$errors) {
            $exists = Database::fetchOne(
                "SELECT id FROM conversion_optimize_rules WHERE offer_id=? AND affiliate_id<=>?",
                [$offerId, $affId]
            );
            if ($exists) {
                Database::query("UPDATE conversion_optimize_rules SET optimize_value=?, is_active=1 WHERE id=?", [$value, $exists['id']]);
            } else {
                Database::insert('conversion_optimize_rules', [
                    'offer_id'       => $offerId,
                    'affiliate_id'   => $affId,
                    'optimize_value' => $value,
                    'is_active'      => 1,
                ]);
            }
            Helpers::flash('success', 'Conversion optimize rule saved.');
            _notifyAffiliatesOfPayoutChange($affId, "A conversion optimize rule has been updated for your account.");
            Helpers::redirect('/admin/payout-management?tab=conversion_optimize');
        }
    }
    elseif ($postAction === 'toggle_optimize_rule') {
        $id       = (int)Helpers::postRaw('rule_id');
        $current  = Database::fetchOne("SELECT is_active FROM conversion_optimize_rules WHERE id=?", [$id]);
        $rule = Database::fetchOne("SELECT affiliate_id FROM conversion_optimize_rules WHERE id=?", [$id]);
        if ($current) {
            Database::query("UPDATE conversion_optimize_rules SET is_active=? WHERE id=?", [$current['is_active']?0:1, $id]);
            if ($rule) _notifyAffiliatesOfPayoutChange($rule['affiliate_id'], "A conversion optimize rule status was toggled.");
        }
        Helpers::flash('success', 'Rule status updated.');
        Helpers::redirect('/admin/payout-management?tab=conversion_optimize');
    }
    elseif ($postAction === 'delete_optimize_rule') {
        $id = (int)Helpers::postRaw('rule_id');
        $rule = Database::fetchOne("SELECT affiliate_id FROM conversion_optimize_rules WHERE id=?", [$id]);
        Database::query("DELETE FROM conversion_optimize_rules WHERE id=?", [$id]);
        if ($rule) _notifyAffiliatesOfPayoutChange($rule['affiliate_id'], "A conversion optimize rule has been deleted.");
        Helpers::flash('success', 'Optimize rule deleted.');
        Helpers::redirect('/admin/payout-management?tab=conversion_optimize');
    }
}

// ── Fetch data for current tab ────────────────────────────────────────────
$countryPayouts = Database::fetchAll(
    "SELECT pcr.*,
            CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
            o.name as offer_name
     FROM payout_country_rules pcr
     LEFT JOIN affiliates af ON af.id=pcr.affiliate_id
     LEFT JOIN users u ON u.id=af.user_id
     LEFT JOIN offers o ON o.id=pcr.offer_id
     ORDER BY pcr.country"
);
$devicePayouts  = Database::fetchAll(
    "SELECT pdr.*,
            CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
            o.name as offer_name
     FROM payout_device_rules pdr
     LEFT JOIN affiliates af ON af.id=pdr.affiliate_id
     LEFT JOIN users u ON u.id=af.user_id
     LEFT JOIN offers o ON o.id=pdr.offer_id
     ORDER BY pdr.country, pdr.device"
);
$affCaps = Database::fetchAll(
    "SELECT ac.*, CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code,
            o.name as offer_name
     FROM aff_daily_caps ac
     JOIN affiliates af ON af.id=ac.affiliate_id
     JOIN users u ON u.id=af.user_id
     LEFT JOIN offers o ON o.id=ac.offer_id
     ORDER BY aff_name"
);
$affPayouts           = Database::fetchAll(
    "SELECT ap.*, CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code, o.name as offer_name
     FROM aff_custom_payouts ap
     JOIN affiliates af ON af.id=ap.affiliate_id
     JOIN users u ON u.id=af.user_id
     LEFT JOIN offers o ON o.id=ap.offer_id
     ORDER BY aff_name, o.name"
);
$affSmartlinkPayouts  = Database::fetchAll(
    "SELECT asp.*, CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code, sl.name as sl_name
     FROM aff_smartlink_payouts asp
     JOIN affiliates af ON af.id=asp.affiliate_id
     JOIN users u ON u.id=af.user_id
     LEFT JOIN smartlinks sl ON sl.id=asp.smartlink_id
     ORDER BY aff_name, sl.name"
);
$affCountryPayouts    = Database::fetchAll(
    "SELECT acp.*, CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
     FROM aff_country_payouts acp
     JOIN affiliates af ON af.id=acp.affiliate_id
     JOIN users u ON u.id=af.user_id
     ORDER BY aff_name, acp.country"
);
$affCountryDevPayouts = Database::fetchAll(
    "SELECT acdp.*, CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
     FROM aff_country_device_payouts acdp
     JOIN affiliates af ON af.id=acdp.affiliate_id
     JOIN users u ON u.id=af.user_id
     ORDER BY aff_name, acdp.country, acdp.device"
);
$optimizeRules = Database::fetchAll(
    "SELECT cor.*, o.name as offer_name,
            CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
     FROM conversion_optimize_rules cor
     LEFT JOIN offers o ON o.id=cor.offer_id
     LEFT JOIN affiliates af ON af.id=cor.affiliate_id
     LEFT JOIN users u ON u.id=af.user_id
     ORDER BY o.name"
);

require BASE_PATH . '/views/admin/payout_management/index.php';

function _notifyAffiliatesOfPayoutChange(?int $affiliateId, string $message) {
    if ((Config::get('config', 'app.offer_link_notify') ?? '0') !== '1') return;

    if ($affiliateId) {
        // Notify specific affiliate
        $u = Database::fetchOne(
            "SELECT u.id FROM users u JOIN affiliates af ON af.user_id = u.id WHERE af.id = ? AND u.status='active'",
            [$affiliateId]
        );
        if ($u) {
            NotificationHelper::notifyUser(
                (int)$u['id'], 'Payout Rule Updated', $message,
                'payout', '/affiliate/reports', [],
                'payout_updated', 'notifications'
            );
        }
    } else {
        // Notify all affiliates via broadcast
        NotificationHelper::notifyRole(
            'affiliate', 'Payout Rule Updated', $message,
            'payout', '/affiliate/reports', [],
            'payout_updated', 'notifications'
        );
    }
}
