<?php
/**
 * IpBanController.php
 * Admin — Manage affiliate login IP bans.
 */
Auth::check('admin');

// ── Ensure the table exists and has all required columns ──────────────────────
try {
    Database::query("CREATE TABLE IF NOT EXISTS `login_ip_bans` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `ip_address`  VARCHAR(50) NOT NULL,
        `reason`      VARCHAR(255) DEFAULT NULL,
        `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
        `created_by`  INT UNSIGNED DEFAULT NULL,
        `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_ip` (`ip_address`),
        INDEX `idx_ip` (`ip_address`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

// Add is_active column to existing installations that don't have it
try {
    Database::query("ALTER TABLE `login_ip_bans` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1");
} catch (Exception $e) {}

$action  = $_GET['action'] ?? '';
$success = '';
$error   = '';

// ── POST: Add, Delete, or Toggle ─────────────────────────────────────────────
if (Helpers::isPost()) {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $error = 'Invalid CSRF token.';
    } else {
        $postAction = Helpers::postRaw('action');

        // ── Add ban ──────────────────────────────────────────────────────────
        if ($postAction === 'add') {
            $raw    = trim(Helpers::postRaw('ip_address') ?? '');
            $reason = trim(Helpers::postRaw('reason') ?? '');

            if ($raw === '') {
                $error = 'IP address is required.';
            } else {
                // Validate: plain IPv4, IPv6, or CIDR
                $valid = false;
                if (strpos($raw, '/') !== false) {
                    [$ip, $prefix] = explode('/', $raw, 2);
                    $isV4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
                    $isV6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
                    $prefix = (int)$prefix;
                    if ($isV4 && $prefix >= 0 && $prefix <= 32)  $valid = true;
                    if ($isV6 && $prefix >= 0 && $prefix <= 128) $valid = true;
                } else {
                    $valid = (bool)filter_var($raw, FILTER_VALIDATE_IP);
                }

                if (!$valid) {
                    $error = 'Please enter a valid IPv4, IPv6, or CIDR address (e.g. 192.168.1.1 or 10.0.0.0/8).';
                } else {
                    try {
                        Database::query(
                            "INSERT INTO `login_ip_bans` (`ip_address`,`reason`,`is_active`,`created_by`)
                             VALUES (?,?,1,?)
                             ON DUPLICATE KEY UPDATE `reason`=VALUES(`reason`), `is_active`=1, `created_by`=VALUES(`created_by`)",
                            [$raw, $reason ?: null, Auth::id()]
                        );
                        $success = "IP <strong>" . htmlspecialchars($raw, ENT_QUOTES) . "</strong> has been banned.";
                    } catch (Exception $e) {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }

        // ── Remove ban ───────────────────────────────────────────────────────
        if ($postAction === 'delete') {
            $id = (int)(Helpers::postRaw('id') ?? 0);
            if ($id > 0) {
                $ban = Database::fetchOne("SELECT `ip_address` FROM `login_ip_bans` WHERE `id`=?", [$id]);
                if ($ban) {
                    Database::query("DELETE FROM `login_ip_bans` WHERE `id`=?", [$id]);
                    $success = "Ban on <strong>" . htmlspecialchars($ban['ip_address'], ENT_QUOTES) . "</strong> has been removed.";
                } else {
                    $error = 'Ban record not found.';
                }
            }
        }

        // ── Toggle active/inactive ───────────────────────────────────────────
        if ($postAction === 'toggle') {
            $id = (int)(Helpers::postRaw('id') ?? 0);
            if ($id > 0) {
                $ban = Database::fetchOne("SELECT `ip_address`, `is_active` FROM `login_ip_bans` WHERE `id`=?", [$id]);
                if ($ban) {
                    $newState = $ban['is_active'] ? 0 : 1;
                    Database::query("UPDATE `login_ip_bans` SET `is_active`=? WHERE `id`=?", [$newState, $id]);
                    $label   = $newState ? 'enabled' : 'disabled';
                    $success = "Ban on <strong>" . htmlspecialchars($ban['ip_address'], ENT_QUOTES) . "</strong> has been <strong>" . $label . "</strong>.";
                } else {
                    $error = 'Ban record not found.';
                }
            }
        }
    }
}

// ── Fetch all bans ────────────────────────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 25;
$offset   = ($page - 1) * $perPage;

$whereClause = '';
$params      = [];
if ($search !== '') {
    $whereClause = 'WHERE b.ip_address LIKE ?';
    $params[]    = '%' . $search . '%';
}

$totalRows = (int)(Database::fetchOne(
    "SELECT COUNT(*) AS c FROM `login_ip_bans` b $whereClause",
    $params
)['c'] ?? 0);

$activeBans = (int)(Database::fetchOne(
    "SELECT COUNT(*) AS c FROM `login_ip_bans` WHERE is_active = 1"
)['c'] ?? 0);

$bans = Database::fetchAll(
    "SELECT b.*, u.first_name, u.last_name
     FROM `login_ip_bans` b
     LEFT JOIN `users` u ON u.id = b.created_by
     $whereClause
     ORDER BY b.is_active DESC, b.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

$totalPages = max(1, ceil($totalRows / $perPage));
$pageTitle  = 'Login IP Bans';

require BASE_PATH . '/views/admin/ip_bans.php';
