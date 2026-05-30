<?php
Auth::check('admin');
$pageTitle = 'Auto Hide Conversions';

// ── One-time schema migration ─────────────────────────────────────────────
try {
    Database::query("ALTER TABLE `conversions` ADD COLUMN `is_hidden` TINYINT(1) NOT NULL DEFAULT 0", []);
} catch (\Throwable $e) { /* column already exists */ }
try {
    Database::query("ALTER TABLE `conversions` ADD COLUMN `hide_reason` VARCHAR(500) DEFAULT ''", []);
} catch (\Throwable $e) { /* column already exists */ }
try {
    Database::query("ALTER TABLE `conversions` ADD INDEX `idx_is_hidden` (`is_hidden`)", []);
} catch (\Throwable $e) { /* index already exists */ }
try {
    Database::query("
        CREATE TABLE IF NOT EXISTS `conversion_autohide_rules` (
            `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name`         VARCHAR(255) NOT NULL,
            `type`         ENUM('global','offer','affiliate') NOT NULL DEFAULT 'global',
            `offer_id`     INT UNSIGNED NULL,
            `affiliate_id` INT UNSIGNED NULL,
            `hide_percent` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
            `reason`       VARCHAR(500) DEFAULT '',
            `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
            `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
            `activated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_type`      (`type`),
            INDEX `idx_offer`     (`offer_id`),
            INDEX `idx_affiliate` (`affiliate_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ", []);
} catch (\Throwable $e) { /* table already exists */ }
// Add activated_at to existing installations
try {
    Database::query("ALTER TABLE `conversion_autohide_rules` ADD COLUMN `activated_at` DATETIME DEFAULT NULL", []);
    // Seed activated_at for already-active rules so they don't get a NULL window
    Database::query("UPDATE `conversion_autohide_rules` SET `activated_at` = `created_at` WHERE `activated_at` IS NULL AND `is_active` = 1", []);
} catch (\Throwable $e) { /* column already exists */ }

$tab    = Helpers::get('tab') ?: 'rules';
$errors = [];

// ── Handle POST actions ───────────────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $postAction = Helpers::post('action');

    // Create new rule
    if ($postAction === 'create') {
        $name    = trim(Helpers::post('name'));
        $type    = Helpers::post('type');
        $offerId = (int)Helpers::postRaw('offer_id');
        $affId   = (int)Helpers::postRaw('affiliate_id');
        $pct     = max(0.01, min(100, (float)Helpers::postRaw('hide_percent')));
        $reason  = trim(Helpers::postRaw('reason'));

        if (!$name)  $errors[] = 'Rule name is required.';
        if (!in_array($type, ['global','offer','affiliate'])) $errors[] = 'Invalid type.';
        if ($type === 'offer' && !$offerId) $errors[] = 'Select an offer.';
        if ($type === 'affiliate' && !$affId) $errors[] = 'Select an affiliate.';

        if (!$errors) {
            Database::insert('conversion_autohide_rules', [
                'name'         => $name,
                'type'         => $type,
                'offer_id'     => $type === 'offer'     ? $offerId : null,
                'affiliate_id' => $type === 'affiliate' ? $affId   : null,
                'hide_percent' => $pct,
                'reason'       => $reason,
                'is_active'    => 1,
                'activated_at' => date('Y-m-d H:i:s'),
            ]);

            // Retroactive apply if requested
            if (Helpers::postRaw('apply_existing') === '1') {
                $where  = [];
                $params = [];
                if ($type === 'offer')     { $where[] = 'offer_id=?';     $params[] = $offerId; }
                if ($type === 'affiliate') { $where[] = 'affiliate_id=?'; $params[] = $affId; }
                $where[] = 'is_hidden=0';
                $where[] = 'status IN (\'approved\',\'pending\')';
                $whereStr = implode(' AND ', $where);

                // Fetch IDs that should be hidden based on percentage
                $existing = Database::fetchAll(
                    "SELECT id, affiliate_id, payout, status FROM conversions WHERE $whereStr ORDER BY converted_at DESC",
                    $params
                );

                $total     = count($existing);
                $hideCount = (int)floor($total * $pct / 100);
                // Space the hidden conversions evenly across the list
                $hideEvery = $hideCount > 0 ? max(1, (int)round($total / $hideCount)) : PHP_INT_MAX;
                $hidden    = 0;
                foreach ($existing as $i => $cv) {
                    if ($hidden >= $hideCount) break;
                    if ($hideEvery === 1 || ($i % $hideEvery) === ($hideEvery - 1)) {
                        Database::query(
                            "UPDATE conversions SET is_hidden=1, hide_reason=? WHERE id=?",
                            [$reason, $cv['id']]
                        );
                        // Only reverse balance for approved conversions — pending
                        // conversions have not had their balance credited yet.
                        if ($cv['status'] === 'approved' && $cv['payout'] > 0) {
                            Database::query(
                                "UPDATE affiliates SET balance=GREATEST(0, balance-?) WHERE id=?",
                                [$cv['payout'], $cv['affiliate_id']]
                            );
                        }
                        $hidden++;
                    }
                }
            }

            Helpers::flash('success', 'Auto-hide rule created.');
            Helpers::redirect('/admin/autohide?tab=rules');
        }
    }

    // Toggle rule active/inactive
    if ($postAction === 'toggle') {
        $ruleId = (int)Helpers::postRaw('rule_id');
        $rule   = Database::fetchOne("SELECT * FROM conversion_autohide_rules WHERE id=?", [$ruleId]);
        if ($rule) {
            $nowActive = $rule['is_active'] ? 0 : 1;
            $updateFields = ['is_active' => $nowActive];
            // Reset the count window to NOW when re-activating so previously
            // accumulated conversions don't trigger a burst of hidden conversions.
            if ($nowActive === 1) {
                $updateFields['activated_at'] = date('Y-m-d H:i:s');
            }
            Database::update('conversion_autohide_rules', $updateFields, 'id=?', [$ruleId]);
            Helpers::flash('success', 'Rule ' . ($rule['is_active'] ? 'disabled' : 'enabled') . '.');
        }
        Helpers::redirect('/admin/autohide?tab=rules');
    }

    // Delete rule
    if ($postAction === 'delete') {
        $ruleId = (int)Helpers::postRaw('rule_id');
        Database::query("DELETE FROM conversion_autohide_rules WHERE id=?", [$ruleId]);
        Helpers::flash('success', 'Rule deleted.');
        Helpers::redirect('/admin/autohide?tab=rules');
    }

    // Manually hide a specific conversion
    if ($postAction === 'hide_one') {
        $convId = Helpers::postRaw('conversion_id');
        $reason = trim(Helpers::postRaw('reason'));
        $cv = Database::fetchOne("SELECT * FROM conversions WHERE conversion_id=?", [$convId]);
        if ($cv && !$cv['is_hidden']) {
            Database::query("UPDATE conversions SET is_hidden=1, hide_reason=? WHERE conversion_id=?", [$reason, $convId]);
            if ($cv['status'] === 'approved' && $cv['payout'] > 0) {
                Database::query("UPDATE affiliates SET balance=GREATEST(0,balance-?) WHERE id=?", [$cv['payout'], $cv['affiliate_id']]);
            }
            Helpers::flash('success', 'Conversion hidden.');
        }
        Helpers::redirect('/admin/autohide?tab=hidden');
    }

    // Unhide a conversion
    if ($postAction === 'unhide') {
        $convId = Helpers::postRaw('conversion_id');
        $cv = Database::fetchOne("SELECT * FROM conversions WHERE conversion_id=?", [$convId]);
        if ($cv && $cv['is_hidden']) {
            Database::query("UPDATE conversions SET is_hidden=0, hide_reason='' WHERE conversion_id=?", [$convId]);
            if ($cv['status'] === 'approved' && $cv['payout'] > 0) {
                Database::query("UPDATE affiliates SET balance=balance+? WHERE id=?", [$cv['payout'], $cv['affiliate_id']]);
            }
            Helpers::flash('success', 'Conversion unhidden.');
        }
        Helpers::redirect('/admin/autohide?tab=hidden');
    }
}

// ── Load rules ────────────────────────────────────────────────────────────
$rules = Database::fetchAll(
    "SELECT r.*,
            o.name as offer_name,
            CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
     FROM conversion_autohide_rules r
     LEFT JOIN offers o ON o.id=r.offer_id
     LEFT JOIN affiliates af ON af.id=r.affiliate_id
     LEFT JOIN users u ON u.id=af.user_id
     ORDER BY r.created_at DESC"
);

// ── Load hidden conversions ───────────────────────────────────────────────
$hiddenFrom = Helpers::get('from') ?: date('Y-m-01');
$hiddenTo   = Helpers::get('to')   ?: date('Y-m-d');

$hiddenConversions = Database::fetchAll(
    "SELECT cv.*, o.name as offer_name,
            CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
     FROM conversions cv
     JOIN offers o ON o.id=cv.offer_id
     JOIN affiliates af ON af.id=cv.affiliate_id
     JOIN users u ON u.id=af.user_id
     WHERE cv.is_hidden=1
       AND cv.converted_at BETWEEN ? AND ?
     ORDER BY cv.converted_at DESC
     LIMIT 1000",
    [date('Y-m-d 00:00:00', strtotime($hiddenFrom)), date('Y-m-d 23:59:59', strtotime($hiddenTo))]
);

$hiddenTotal  = count($hiddenConversions);
$hiddenPayout = array_sum(array_column($hiddenConversions, 'payout'));

// ── Dropdown data for create form ─────────────────────────────────────────
$offerList = Database::fetchAll("SELECT id, name FROM offers WHERE status='active' ORDER BY name");
$affList   = Database::fetchAll(
    "SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code
     FROM affiliates af JOIN users u ON u.id=af.user_id ORDER BY name"
);

// ── Summary stats ─────────────────────────────────────────────────────────
$totalHiddenAll  = Database::count('conversions', 'is_hidden=1');
$totalHiddenPaid = Database::fetchOne("SELECT SUM(payout) as total FROM conversions WHERE is_hidden=1")['total'] ?? 0;
$activeRules     = Database::count('conversion_autohide_rules', 'is_active=1');

require BASE_PATH . '/views/admin/autohide/index.php';
