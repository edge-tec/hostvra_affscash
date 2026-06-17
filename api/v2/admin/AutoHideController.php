<?php
/**
 * Admin App API — Auto Hide Conversions
 */
Auth::requireRole('admin');

try {
    $action = $_GET['action'] ?? 'stats';

    // ── Ensure schema exists (same as web) ──────────────────────────────────
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `is_hidden` TINYINT(1) NOT NULL DEFAULT 0", []); } catch (\Throwable $e) {}
    try { Database::query("ALTER TABLE `conversions` ADD COLUMN `hide_reason` VARCHAR(500) DEFAULT ''", []); } catch (\Throwable $e) {}
    try { Database::query("ALTER TABLE `conversions` ADD INDEX `idx_is_hidden` (`is_hidden`)", []); } catch (\Throwable $e) {}
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
    } catch (\Throwable $e) {}

    // ── Handle GET actions ──────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'stats') {
            $totalHiddenAll  = Database::count('conversions', 'is_hidden=1');
            $totalHiddenPaid = Database::fetchOne("SELECT SUM(payout) as total FROM conversions WHERE is_hidden=1")['total'] ?? 0;
            $activeRules     = Database::count('conversion_autohide_rules', 'is_active=1');
            $totalRules      = Database::count('conversion_autohide_rules');

            echo json_encode([
                'status' => 'success',
                'stats' => [
                    'total_hidden' => $totalHiddenAll,
                    'payout_saved' => (float)$totalHiddenPaid,
                    'active_rules' => $activeRules,
                    'total_rules'  => $totalRules
                ]
            ]);
            exit;
        }

        if ($action === 'rules') {
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
            echo json_encode(['status' => 'success', 'rules' => $rules]);
            exit;
        }

        if ($action === 'hidden_conversions') {
            $limit = min((int)($_GET['limit'] ?? 100), 1000);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $hiddenConversions = Database::fetchAll(
                "SELECT cv.id, cv.conversion_id, cv.status, cv.payout, cv.revenue, cv.converted_at, cv.hide_reason,
                        o.name as offer_name,
                        CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
                 FROM conversions cv
                 JOIN offers o ON o.id=cv.offer_id
                 JOIN affiliates af ON af.id=cv.affiliate_id
                 JOIN users u ON u.id=af.user_id
                 WHERE cv.is_hidden=1
                 ORDER BY cv.converted_at DESC
                 LIMIT $limit OFFSET $offset"
            );
            echo json_encode(['status' => 'success', 'conversions' => $hiddenConversions]);
            exit;
        }

        if ($action === 'filters') {
            $offers = Database::fetchAll("SELECT id, name FROM offers WHERE status='active' ORDER BY name");
            $affiliates = Database::fetchAll("SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name, af.affiliate_code FROM affiliates af JOIN users u ON u.id=af.user_id ORDER BY name");
            echo json_encode([
                'status' => 'success',
                'offers' => $offers,
                'affiliates' => $affiliates
            ]);
            exit;
        }
    }

    // ── Handle POST actions ─────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if ($action === 'create') {
            $name    = trim($input['name'] ?? '');
            $type    = $input['type'] ?? 'global';
            $offerId = (int)($input['offer_id'] ?? 0);
            $affId   = (int)($input['affiliate_id'] ?? 0);
            $pct     = max(0.01, min(100, (float)($input['hide_percent'] ?? 10.0)));
            $reason  = trim($input['reason'] ?? '');
            $applyExisting = (bool)($input['apply_existing'] ?? false);

            if (!$name) throw new Exception('Rule name is required.');
            if (!in_array($type, ['global','offer','affiliate'])) throw new Exception('Invalid type.');
            if ($type === 'offer' && !$offerId) throw new Exception('Select an offer.');
            if ($type === 'affiliate' && !$affId) throw new Exception('Select an affiliate.');

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

            // Retroactive apply
            if ($applyExisting) {
                $where  = [];
                $params = [];
                if ($type === 'offer')     { $where[] = 'offer_id=?';     $params[] = $offerId; }
                if ($type === 'affiliate') { $where[] = 'affiliate_id=?'; $params[] = $affId; }
                $where[] = 'is_hidden=0';
                $where[] = 'status IN (\'approved\',\'pending\')';
                $whereStr = implode(' AND ', $where);

                $existing = Database::fetchAll(
                    "SELECT id, affiliate_id, payout, status FROM conversions WHERE $whereStr ORDER BY converted_at DESC",
                    $params
                );

                $total     = count($existing);
                $hideCount = (int)floor($total * $pct / 100);
                $hideEvery = $hideCount > 0 ? max(1, (int)round($total / $hideCount)) : PHP_INT_MAX;
                $hidden    = 0;
                foreach ($existing as $i => $cv) {
                    if ($hidden >= $hideCount) break;
                    if ($hideEvery === 1 || ($i % $hideEvery) === ($hideEvery - 1)) {
                        Database::query("UPDATE conversions SET is_hidden=1, hide_reason=? WHERE id=?", [$reason, $cv['id']]);
                        if ($cv['status'] === 'approved' && $cv['payout'] > 0) {
                            Database::query("UPDATE affiliates SET balance=GREATEST(0, balance-?) WHERE id=?", [$cv['payout'], $cv['affiliate_id']]);
                        }
                        $hidden++;
                    }
                }
            }

            echo json_encode(['status' => 'success', 'message' => 'Auto-hide rule created.']);
            exit;
        }

        if ($action === 'toggle') {
            $ruleId = (int)($input['rule_id'] ?? 0);
            $rule   = Database::fetchOne("SELECT * FROM conversion_autohide_rules WHERE id=?", [$ruleId]);
            if ($rule) {
                $nowActive = $rule['is_active'] ? 0 : 1;
                $updateFields = ['is_active' => $nowActive];
                if ($nowActive === 1) {
                    $updateFields['activated_at'] = date('Y-m-d H:i:s');
                }
                Database::update('conversion_autohide_rules', $updateFields, 'id=?', [$ruleId]);
                echo json_encode(['status' => 'success', 'message' => 'Rule toggled.']);
                exit;
            }
            throw new Exception('Rule not found.');
        }

        if ($action === 'delete') {
            $ruleId = (int)($input['rule_id'] ?? 0);
            Database::query("DELETE FROM conversion_autohide_rules WHERE id=?", [$ruleId]);
            echo json_encode(['status' => 'success', 'message' => 'Rule deleted.']);
            exit;
        }

        if ($action === 'unhide') {
            $convId = trim($input['conversion_id'] ?? '');
            $cv = Database::fetchOne("SELECT * FROM conversions WHERE conversion_id=?", [$convId]);
            if ($cv && $cv['is_hidden']) {
                Database::query("UPDATE conversions SET is_hidden=0, hide_reason='' WHERE conversion_id=?", [$convId]);
                if ($cv['status'] === 'approved' && $cv['payout'] > 0) {
                    Database::query("UPDATE affiliates SET balance=balance+? WHERE id=?", [$cv['payout'], $cv['affiliate_id']]);
                }
                echo json_encode(['status' => 'success', 'message' => 'Conversion unhidden.']);
                exit;
            }
            throw new Exception('Conversion not found or not hidden.');
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} catch (\Throwable $e) {
    error_log("Admin AutoHide API Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
