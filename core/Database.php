<?php
/**
 * Database - PDO singleton wrapper
 */
class Database {
    private static ?PDO $instance = null;
    private static ?int $currentTenantId = null;

    public static function setTenantId(?int $id): void {
        self::$currentTenantId = $id;
    }

    public static function getTenantId(): ?int {
        return self::$currentTenantId;
    }

    private static function isTenantTable(string $table): bool {
        $table = str_replace('`', '', $table);
        $tenantTables = [
            'users', 'affiliates', 'affiliate_managers', 'advertisers', 'offers',
            'affiliate_offers', 'smartlinks', 'smartlink_offers', 'clicks', 'conversions',
            'postbacks', 'postback_logs', 'global_postbacks', 'registration_questions',
            'notifications', 'activity_log', 'stats_daily', 'payments', 'invoices',
            'tracking_domains', 'email_templates', 'email_logs', 'news', 'news_reads',
            'referral_codes', 'referral_signups', 'referral_commissions', 'offer_links',
            'vpn_blocked_log', 'aff_daily_caps', 'aff_custom_payouts', 'aff_country_payouts',
            'aff_country_device_payouts', 'conversion_optimize_rules', 'postback_test_logs'
        ];
        return in_array($table, $tenantTables);
    }

    private static function injectTenantId(string $sql, int $tenantId): string {
        if (preg_match('/^(SHOW|CREATE|ALTER|DROP|DESCRIBE|EXPLAIN)\b/i', ltrim($sql))) {
            return $sql;
        }

        $cleanSql = str_replace('`', '', $sql);
        
        $tenantTables = [
            'users', 'affiliates', 'affiliate_managers', 'advertisers', 'offers',
            'affiliate_offers', 'smartlinks', 'smartlink_offers', 'clicks', 'conversions',
            'postbacks', 'postback_logs', 'global_postbacks', 'registration_questions',
            'notifications', 'activity_log', 'stats_daily', 'payments', 'invoices',
            'tracking_domains', 'email_templates', 'email_logs', 'news', 'news_reads',
            'referral_codes', 'referral_signups', 'referral_commissions', 'offer_links',
            'vpn_blocked_log', 'aff_daily_caps', 'aff_custom_payouts', 'aff_country_payouts',
            'aff_country_device_payouts', 'conversion_optimize_rules', 'postback_test_logs'
        ];

        $hasTenantTable = false;
        foreach ($tenantTables as $tbl) {
            if (preg_match('/\b' . preg_quote($tbl, '/') . '\b/i', $cleanSql)) {
                $hasTenantTable = true;
                break;
            }
        }
        if (!$hasTenantTable) return $sql;

        if (stripos($sql, 'tenant_id') !== false) {
            return $sql;
        }

        $parts = preg_split('/\b(GROUP\s+BY|ORDER\s+BY|LIMIT|UNION|HAVING)\b/i', $sql, 2, PREG_SPLIT_DELIM_CAPTURE);
        
        $baseQuery = $parts[0];
        $suffix = isset($parts[1]) ? $parts[1] . $parts[2] : '';
        
        if (stripos($baseQuery, ' WHERE ') !== false) {
            $baseQuery .= " AND tenant_id = " . (int)$tenantId;
        } else {
            $baseQuery .= " WHERE tenant_id = " . (int)$tenantId;
        }
        
        return $baseQuery . (empty($suffix) ? '' : ' ' . $suffix);
    }

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $cfg = Config::get('config', 'database');
            $charset = !empty($cfg['charset']) ? $cfg['charset'] : 'utf8mb4';
            if ($charset === 'utf8') $charset = 'utf8mb4'; // Upgrade utf8 to utf8mb4 for full unicode support
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$charset}";
            self::$instance = new PDO($dsn, $cfg['user'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Sync MySQL session timezone with PHP's configured timezone so that
            // NOW(), CURDATE(), DATE_FORMAT() etc. use the same offset as PHP.
            // Try the named timezone first (requires MySQL timezone tables); fall back
            // to the UTC offset which works on all MySQL installations.
            $appTz = Config::get('config', 'app.timezone') ?? 'UTC';
            try {
                self::$instance->exec("SET time_zone = '" . addslashes($appTz) . "'");
            } catch (\Throwable $e) {
                try {
                    self::$instance->exec("SET time_zone = '" . date('P') . "'");
                } catch (\Throwable $e2) { /* ignore */ }
            }
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): PDOStatement {
        if (self::$currentTenantId !== null) {
            $sql = self::injectTenantId($sql, self::$currentTenantId);
        }
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int {
        if (self::isTenantTable($table) && !isset($data['tenant_id']) && self::$currentTenantId !== null) {
            $data['tenant_id'] = self::$currentTenantId;
        }
        $cols = implode(',', array_map(fn($c) => "`$c`", array_keys($data)));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        self::query("INSERT INTO `$table` ($cols) VALUES ($placeholders)", array_values($data));
        return (int) self::getInstance()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        if (self::isTenantTable($table) && self::$currentTenantId !== null) {
            $where = "($where) AND tenant_id = ?";
            $whereParams[] = self::$currentTenantId;
        }
        $set = implode(',', array_map(fn($c) => "`$c`=?", array_keys($data)));
        $stmt = self::query("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int {
        if (self::isTenantTable($table) && self::$currentTenantId !== null) {
            $where = "($where) AND tenant_id = ?";
            $params[] = self::$currentTenantId;
        }
        return self::query("DELETE FROM `$table` WHERE $where", $params)->rowCount();
    }

    public static function count(string $table, string $where = '1', array $params = []): int {
        if (self::isTenantTable($table) && self::$currentTenantId !== null) {
            $where = "($where) AND tenant_id = ?";
            $params[] = self::$currentTenantId;
        }
        $row = self::fetchOne("SELECT COUNT(*) as cnt FROM `$table` WHERE $where", $params);
        return (int) ($row['cnt'] ?? 0);
    }

    public static function begin(): void { self::getInstance()->beginTransaction(); }
    public static function commit(): void { self::getInstance()->commit(); }
    public static function rollback(): void { self::getInstance()->rollBack(); }

    public static function upsertStats(string $date, int $affiliateId, int $offerId, array $increments): void {
        $sets = implode(',', array_map(fn($k) => "`$k`=`$k`+?", array_keys($increments)));
        $insertCols = '`stat_date`,`affiliate_id`,`offer_id`,' . implode(',', array_map(fn($k) => "`$k`", array_keys($increments)));
        $insertVals = implode(',', array_fill(0, count($increments) + 3, '?'));
        $sql = "INSERT INTO `stats_daily` ($insertCols) VALUES ($insertVals) ON DUPLICATE KEY UPDATE $sets";
        $params = array_merge([$date, $affiliateId, $offerId], array_values($increments), array_values($increments));
        self::query($sql, $params);
    }
}
