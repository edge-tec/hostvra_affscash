<?php
/**
 * AutoInvoiceEngine — Enterprise Billing & Automatic Invoice Generation Engine for Affscash.net
 *
 * Handles:
 *  - Global schedule management
 *  - Affiliate & Offer rule priorities (Affiliate Override > Offer Rule > Global Schedule)
 *  - Period calculations (Monthly, Every X Days, Weekly, Custom)
 *  - Uninvoiced conversion aggregation & grouping
 *  - Duplicate prevention (Unique Affiliate + Period)
 *  - Transaction-safe invoice creation & conversion linking
 *  - PDF generation & disk storage
 *  - Automatic email notifications
 *  - Audit logging & status management
 */
class AutoInvoiceEngine
{
    private static bool $tablesChecked = false;

    /**
     * Ensure all required database tables & columns exist (Self-healing).
     */
    public static function ensureSchema(): void
    {
        if (self::$tablesChecked) return;

        try {
            // Global schedule table
            Database::query("
                CREATE TABLE IF NOT EXISTS `invoice_schedules` (
                    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `schedule_name`        VARCHAR(100) NOT NULL DEFAULT 'Default Global Schedule',
                    `enabled`              TINYINT(1) NOT NULL DEFAULT 0,
                    `frequency`            ENUM('monthly','every_x_days','weekly','custom') NOT NULL DEFAULT 'monthly',
                    `monthly_day`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
                    `interval_days`        SMALLINT UNSIGNED NOT NULL DEFAULT 15,
                    `invoice_time`         TIME NOT NULL DEFAULT '00:00:00',
                    `timezone`             VARCHAR(64) NOT NULL DEFAULT 'UTC',
                    `auto_pdf`             TINYINT(1) NOT NULL DEFAULT 1,
                    `auto_email`           TINYINT(1) NOT NULL DEFAULT 1,
                    `invoice_prefix`       VARCHAR(32) NOT NULL DEFAULT 'AFFSCASH-INV-',
                    `starting_number`      INT UNSIGNED NOT NULL DEFAULT 100001,
                    `min_payout_threshold` DECIMAL(12,4) NOT NULL DEFAULT 50.0000,
                    `payment_terms`        VARCHAR(50) NOT NULL DEFAULT 'net15',
                    `currency`             VARCHAR(10) NOT NULL DEFAULT 'USD',
                    `last_run_at`          DATETIME NULL DEFAULT NULL,
                    `next_run_at`          DATETIME NULL DEFAULT NULL,
                    `created_by`           INT UNSIGNED NULL DEFAULT NULL,
                    `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Affiliate billing rules table
            Database::query("
                CREATE TABLE IF NOT EXISTS `affiliate_invoice_rules` (
                    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `affiliate_id`     INT UNSIGNED NOT NULL,
                    `override_global`  TINYINT(1) NOT NULL DEFAULT 1,
                    `frequency`        ENUM('monthly','every_x_days','weekly','custom') NOT NULL DEFAULT 'monthly',
                    `monthly_day`      TINYINT UNSIGNED NOT NULL DEFAULT 1,
                    `interval_days`    SMALLINT UNSIGNED NOT NULL DEFAULT 15,
                    `minimum_amount`   DECIMAL(12,4) NOT NULL DEFAULT 50.0000,
                    `currency`         VARCHAR(10) NOT NULL DEFAULT 'USD',
                    `payment_method`   VARCHAR(50) NULL DEFAULT NULL,
                    `payment_terms`    VARCHAR(50) NOT NULL DEFAULT 'net15',
                    `start_date`       DATE NULL DEFAULT NULL,
                    `end_date`         DATE NULL DEFAULT NULL,
                    `enabled`          TINYINT(1) NOT NULL DEFAULT 1,
                    `notes`            TEXT NULL DEFAULT NULL,
                    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `uniq_affiliate_id` (`affiliate_id`),
                    INDEX `idx_enabled` (`enabled`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Offer billing rules table
            Database::query("
                CREATE TABLE IF NOT EXISTS `offer_invoice_rules` (
                    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `offer_id`            INT UNSIGNED NOT NULL,
                    `override_global`     TINYINT(1) NOT NULL DEFAULT 1,
                    `frequency`           ENUM('monthly','every_x_days','weekly','custom') NOT NULL DEFAULT 'monthly',
                    `monthly_day`         TINYINT UNSIGNED NOT NULL DEFAULT 1,
                    `interval_days`       SMALLINT UNSIGNED NOT NULL DEFAULT 15,
                    `minimum_conversions` INT UNSIGNED NOT NULL DEFAULT 0,
                    `minimum_revenue`     DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
                    `start_date`          DATE NULL DEFAULT NULL,
                    `end_date`            DATE NULL DEFAULT NULL,
                    `enabled`             TINYINT(1) NOT NULL DEFAULT 1,
                    `notes`               TEXT NULL DEFAULT NULL,
                    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `unique_offer` (`offer_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Advertiser invoice rules table
            Database::query("
                CREATE TABLE IF NOT EXISTS `advertiser_invoice_rules` (
                    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `advertiser_id`       INT UNSIGNED NOT NULL,
                    `payment_terms`       VARCHAR(50) NOT NULL DEFAULT 'net30',
                    `minimum_payout`      DECIMAL(12,4) NOT NULL DEFAULT 50.0000,
                    `minimum_conversions` INT UNSIGNED NOT NULL DEFAULT 0,
                    `frequency`           ENUM('monthly','every_x_days','weekly','custom') NOT NULL DEFAULT 'monthly',
                    `monthly_day`         TINYINT UNSIGNED NOT NULL DEFAULT 1,
                    `interval_days`       SMALLINT UNSIGNED NOT NULL DEFAULT 15,
                    `currency`            VARCHAR(10) NOT NULL DEFAULT 'USD',
                    `enabled`             TINYINT(1) NOT NULL DEFAULT 1,
                    `notes`               TEXT NULL DEFAULT NULL,
                    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `unique_advertiser` (`advertiser_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Invoice items table
            Database::query("
                CREATE TABLE IF NOT EXISTS `invoice_items` (
                    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `invoice_id`       INT UNSIGNED NOT NULL,
                    `conversion_id`    VARCHAR(64) NULL DEFAULT NULL,
                    `conversion_db_id` INT UNSIGNED NULL DEFAULT NULL,
                    `offer_id`         INT UNSIGNED NULL DEFAULT NULL,
                    `offer_name`       VARCHAR(255) NULL DEFAULT NULL,
                    `geo`              VARCHAR(10) NULL DEFAULT NULL,
                    `payout`           DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
                    `conversion_date`  DATETIME NULL DEFAULT NULL,
                    `currency`         VARCHAR(10) NOT NULL DEFAULT 'USD',
                    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_invoice_id` (`invoice_id`),
                    INDEX `idx_conversion_id` (`conversion_id`),
                    INDEX `idx_offer_id` (`offer_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Invoice logs table
            Database::query("
                CREATE TABLE IF NOT EXISTS `invoice_logs` (
                    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `invoice_id`   INT UNSIGNED NULL DEFAULT NULL,
                    `affiliate_id` INT UNSIGNED NULL DEFAULT NULL,
                    `action`       VARCHAR(64) NOT NULL,
                    `offer_count`  INT UNSIGNED NOT NULL DEFAULT 0,
                    `amount`       DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
                    `is_auto`      TINYINT(1) NOT NULL DEFAULT 0,
                    `created_by`   INT UNSIGNED NULL DEFAULT NULL,
                    `details`      TEXT NULL DEFAULT NULL,
                    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_invoice_id` (`invoice_id`),
                    INDEX `idx_affiliate_id` (`affiliate_id`),
                    INDEX `idx_action` (`action`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Invoices & rules column additions
            $colsToAdd = [
                'offer_scope'         => "ALTER TABLE `affiliate_invoice_rules` ADD COLUMN `offer_scope` VARCHAR(20) NOT NULL DEFAULT 'all' AFTER `override_global`",
                'specific_offers'     => "ALTER TABLE `affiliate_invoice_rules` ADD COLUMN `specific_offers` TEXT NULL DEFAULT NULL AFTER `offer_scope`",
                'period_type'         => "ALTER TABLE `invoice_schedules` ADD COLUMN `period_type` VARCHAR(50) NOT NULL DEFAULT 'prev_month' AFTER `frequency`",
                'custom_start_day'    => "ALTER TABLE `invoice_schedules` ADD COLUMN `custom_start_day` TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER `period_type`",
                'custom_end_day'      => "ALTER TABLE `invoice_schedules` ADD COLUMN `custom_end_day` TINYINT UNSIGNED NOT NULL DEFAULT 31 AFTER `custom_start_day`",
                'custom_period_start' => "ALTER TABLE `invoice_schedules` ADD COLUMN `custom_period_start` DATE NULL DEFAULT NULL AFTER `custom_end_day`",
                'custom_period_end'   => "ALTER TABLE `invoice_schedules` ADD COLUMN `custom_period_end` DATE NULL DEFAULT NULL AFTER `custom_period_start`",
                'billing_start'       => "ALTER TABLE `invoices` ADD COLUMN `billing_start` DATE NULL DEFAULT NULL AFTER `period_end`",
                'billing_end'         => "ALTER TABLE `invoices` ADD COLUMN `billing_end` DATE NULL DEFAULT NULL AFTER `billing_start`",
                'adjustment'          => "ALTER TABLE `invoices` ADD COLUMN `adjustment` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `subtotal`",
                'chargeback_amount'   => "ALTER TABLE `invoices` ADD COLUMN `chargeback_amount` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `adjustment`",
                'currency'            => "ALTER TABLE `invoices` ADD COLUMN `currency` VARCHAR(10) NOT NULL DEFAULT 'USD' AFTER `total`",
                'generated_at'        => "ALTER TABLE `invoices` ADD COLUMN `generated_at` DATETIME NULL DEFAULT NULL AFTER `paid_at`",
                'pdf_path'            => "ALTER TABLE `invoices` ADD COLUMN `pdf_path` VARCHAR(255) NULL DEFAULT NULL AFTER `generated_at`",
                'is_auto'             => "ALTER TABLE `invoices` ADD COLUMN `is_auto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `pdf_path`",
                'viewed_at'           => "ALTER TABLE `invoices` ADD COLUMN `viewed_at` DATETIME NULL DEFAULT NULL AFTER `is_auto`",
                'email_sent_at'       => "ALTER TABLE `invoices` ADD COLUMN `email_sent_at` DATETIME NULL DEFAULT NULL AFTER `viewed_at`",
                'balance_deducted'    => "ALTER TABLE `invoices` ADD COLUMN `balance_deducted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `email_sent_at`",
                'period_hash'         => "ALTER TABLE `invoices` ADD COLUMN `period_hash` VARCHAR(64) NULL DEFAULT NULL AFTER `balance_deducted`",
            ];

            foreach ($colsToAdd as $sql) {
                try { Database::query($sql); } catch (\Throwable $_e) {}
            }

            // Conversions column additions
            try { Database::query("ALTER TABLE `conversions` ADD COLUMN `invoice_id` INT UNSIGNED NULL DEFAULT NULL"); } catch (\Throwable $_e) {}
            try { Database::query("ALTER TABLE `conversions` ADD COLUMN `is_hidden` TINYINT(1) NOT NULL DEFAULT 0"); } catch (\Throwable $_e) {}

            // Ensure uploads directory exists
            $dir = BASE_PATH . '/uploads/invoices';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            self::$tablesChecked = true;
        } catch (\Throwable $e) {
            error_log('[AutoInvoiceEngine] Schema ensure error: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 1. GLOBAL SCHEDULE
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get active global schedule or default fallback.
     */
    public static function getGlobalSchedule(): array
    {
        self::ensureSchema();
        $row = Database::fetchOne("SELECT * FROM `invoice_schedules` ORDER BY `id` ASC LIMIT 1");
        if (!$row) {
            $default = [
                'id'                   => 1,
                'schedule_name'        => 'Default Global Schedule',
                'enabled'              => 1,
                'frequency'            => 'monthly',
                'period_type'          => 'prev_month',
                'custom_start_day'     => 1,
                'custom_end_day'       => 31,
                'custom_period_start'  => null,
                'custom_period_end'    => null,
                'monthly_day'          => 1,
                'interval_days'        => 15,
                'invoice_time'         => '00:00:00',
                'timezone'             => 'UTC',
                'auto_pdf'             => 1,
                'auto_email'           => 1,
                'invoice_prefix'       => 'AFFSCASH-INV-',
                'starting_number'      => 100001,
                'min_payout_threshold' => 50.00,
                'payment_terms'        => 'net15',
                'currency'             => 'USD',
                'last_run_at'          => null,
                'next_run_at'          => null,
            ];
            try {
                Database::insert('invoice_schedules', $default);
                return $default;
            } catch (\Throwable $e) {
                return $default;
            }
        }
        return $row;
    }

    /**
     * Save global schedule settings.
     */
    public static function saveGlobalSchedule(array $data, ?int $adminId = null): array
    {
        self::ensureSchema();
        $current = self::getGlobalSchedule();

        $updateData = [
            'schedule_name'        => trim($data['schedule_name'] ?? 'Default Global Schedule'),
            'enabled'              => !empty($data['enabled']) ? 1 : 0,
            'frequency'            => in_array($data['frequency'] ?? '', ['monthly', 'every_x_days', 'weekly', 'custom']) ? $data['frequency'] : 'monthly',
            'period_type'          => in_array($data['period_type'] ?? '', ['prev_month', 'current_month', 'bi_monthly_1_15', 'bi_monthly_16_end', 'custom_days', 'custom_dates', 'rolling_days']) ? $data['period_type'] : 'prev_month',
            'custom_start_day'     => max(1, min(31, (int)($data['custom_start_day'] ?? 1))),
            'custom_end_day'       => max(1, min(31, (int)($data['custom_end_day'] ?? 31))),
            'custom_period_start'  => !empty($data['custom_period_start']) ? date('Y-m-d', strtotime($data['custom_period_start'])) : null,
            'custom_period_end'    => !empty($data['custom_period_end']) ? date('Y-m-d', strtotime($data['custom_period_end'])) : null,
            'monthly_day'          => max(1, min(31, (int)($data['monthly_day'] ?? 1))),
            'interval_days'        => max(1, min(365, (int)($data['interval_days'] ?? 15))),
            'invoice_time'         => !empty($data['invoice_time']) ? date('H:i:s', strtotime($data['invoice_time'])) : '00:00:00',
            'timezone'             => !empty($data['timezone']) && in_array($data['timezone'], timezone_identifiers_list()) ? $data['timezone'] : 'UTC',
            'auto_pdf'             => isset($data['auto_pdf']) ? (int)$data['auto_pdf'] : 1,
            'auto_email'           => isset($data['auto_email']) ? (int)$data['auto_email'] : 1,
            'invoice_prefix'       => trim($data['invoice_prefix'] ?? 'AFFSCASH-INV-'),
            'starting_number'      => max(1, (int)($data['starting_number'] ?? 100001)),
            'min_payout_threshold' => max(0, (float)($data['min_payout_threshold'] ?? 50.00)),
            'payment_terms'        => trim($data['payment_terms'] ?? 'net15'),
            'currency'             => strtoupper(trim($data['currency'] ?? 'USD')),
            'created_by'           => $adminId,
        ];

        // Compute next run timestamp
        $updateData['next_run_at'] = self::computeNextRunTimestamp(
            $updateData['frequency'],
            $updateData['monthly_day'],
            $updateData['interval_days'],
            $updateData['invoice_time'],
            $updateData['timezone']
        );

        if (!empty($current['id'])) {
            Database::update('invoice_schedules', $updateData, 'id = ?', [$current['id']]);
            $updateData['id'] = $current['id'];
        } else {
            $updateData['id'] = Database::insert('invoice_schedules', $updateData);
        }

        self::logAction(null, null, 'update_global_schedule', 0, 0, 0, $adminId, 'Updated global invoice schedule configuration');
        return $updateData;
    }

    /**
     * Compute next run datetime string (Y-m-d H:i:s) in UTC.
     */
    public static function computeNextRunTimestamp(
        string $frequency,
        int $monthlyDay,
        int $intervalDays,
        string $timeStr = '00:00:00',
        string $tzName = 'UTC'
    ): string {
        try {
            $tz = new DateTimeZone($tzName);
            $now = new DateTime('now', $tz);
        } catch (\Throwable $e) {
            $tz = new DateTimeZone('UTC');
            $now = new DateTime('now', $tz);
        }

        [$hour, $min, $sec] = array_pad(explode(':', $timeStr), 3, '00');
        $target = clone $now;
        $target->setTime((int)$hour, (int)$min, (int)$sec);

        switch ($frequency) {
            case 'monthly':
                $target->setDate((int)$now->format('Y'), (int)$now->format('m'), min((int)$monthlyDay, (int)$now->format('t')));
                if ($target <= $now) {
                    $target->modify('+1 month');
                    $target->setDate((int)$target->format('Y'), (int)$target->format('m'), min((int)$monthlyDay, (int)$target->format('t')));
                }
                break;
            case 'weekly':
                $target->modify('next monday');
                break;
            case 'every_x_days':
                $days = max(1, $intervalDays);
                $target->modify("+{$days} days");
                break;
            default:
                $target->modify('+1 day');
                break;
        }

        // Return in UTC
        $target->setTimezone(new DateTimeZone('UTC'));
        return $target->format('Y-m-d H:i:s');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. AFFILIATE BILLING RULES
    // ──────────────────────────────────────────────────────────────────────────

    public static function getAffiliateRules(array $filters = []): array
    {
        self::ensureSchema();
        $sql = "
            SELECT r.*, u.first_name, u.last_name, u.email, u.status AS user_status,
                   af.balance, af.payment_method AS aff_payment_method
            FROM `affiliate_invoice_rules` r
            JOIN `affiliates` af ON af.id = r.affiliate_id
            JOIN `users` u ON u.id = af.user_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR r.affiliate_id = ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = (int)$filters['search'];
        }
        if (isset($filters['enabled']) && $filters['enabled'] !== '') {
            $sql .= " AND r.enabled = ?";
            $params[] = (int)$filters['enabled'];
        }
        if (!empty($filters['frequency'])) {
            $sql .= " AND r.frequency = ?";
            $params[] = $filters['frequency'];
        }

        $sql .= " ORDER BY r.updated_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function getAffiliateRule(int $affiliateId): ?array
    {
        self::ensureSchema();
        return Database::fetchOne("SELECT * FROM `affiliate_invoice_rules` WHERE `affiliate_id` = ?", [$affiliateId]);
    }

    public static function saveAffiliateRule(array $data, ?int $adminId = null): int
    {
        self::ensureSchema();
        $affiliateId = (int)($data['affiliate_id'] ?? 0);
        if ($affiliateId <= 0) {
            throw new InvalidArgumentException('Invalid affiliate ID');
        }

        $offerScope = in_array($data['offer_scope'] ?? '', ['all', 'specific']) ? $data['offer_scope'] : 'all';
        $specificOffers = null;
        if ($offerScope === 'specific') {
            $rawOffers = $data['specific_offers'] ?? $data['offer_ids'] ?? [];
            if (is_string($rawOffers)) {
                $rawOffers = array_filter(array_map('intval', explode(',', $rawOffers)));
            } elseif (is_array($rawOffers)) {
                $rawOffers = array_filter(array_map('intval', $rawOffers));
            }
            if (!empty($rawOffers)) {
                $specificOffers = json_encode(array_values($rawOffers));
            }
        }

        $ruleData = [
            'affiliate_id'    => $affiliateId,
            'override_global' => !empty($data['override_global']) ? 1 : 0,
            'offer_scope'     => $offerScope,
            'specific_offers' => $specificOffers,
            'frequency'       => in_array($data['frequency'] ?? '', ['monthly', 'every_x_days', 'weekly', 'custom']) ? $data['frequency'] : 'monthly',
            'monthly_day'     => max(1, min(31, (int)($data['monthly_day'] ?? 1))),
            'interval_days'   => max(1, min(365, (int)($data['interval_days'] ?? 15))),
            'minimum_amount'  => max(0, (float)($data['minimum_amount'] ?? 50.00)),
            'currency'        => strtoupper(trim($data['currency'] ?? 'USD')),
            'payment_method'  => trim($data['payment_method'] ?? ''),
            'payment_terms'   => trim($data['payment_terms'] ?? 'net15'),
            'start_date'      => !empty($data['start_date']) ? date('Y-m-d', strtotime($data['start_date'])) : null,
            'end_date'        => !empty($data['end_date']) ? date('Y-m-d', strtotime($data['end_date'])) : null,
            'enabled'         => !empty($data['enabled']) ? 1 : 0,
            'notes'           => trim($data['notes'] ?? ''),
        ];

        $existing = self::getAffiliateRule($affiliateId);
        if ($existing) {
            Database::update('affiliate_invoice_rules', $ruleData, 'id = ?', [$existing['id']]);
            $id = $existing['id'];
        } else {
            $id = Database::insert('affiliate_invoice_rules', $ruleData);
        }

        self::logAction(null, $affiliateId, 'save_affiliate_rule', 0, 0, 0, $adminId, 'Configured affiliate billing rule for Aff #' . $affiliateId);
        return $id;
    }

    public static function batchSaveAffiliateRules(array $affiliateIds, array $ruleData, ?int $adminId = null): int
    {
        $saved = 0;
        foreach ($affiliateIds as $affId) {
            $affId = (int)$affId;
            if ($affId <= 0) continue;
            $data = $ruleData;
            $data['affiliate_id'] = $affId;
            self::saveAffiliateRule($data, $adminId);
            $saved++;
        }
        return $saved;
    }

    public static function deleteAffiliateRule(int $id, ?int $adminId = null): bool
    {
        self::ensureSchema();
        $rule = Database::fetchOne("SELECT * FROM `affiliate_invoice_rules` WHERE `id` = ?", [$id]);
        if ($rule) {
            Database::query("DELETE FROM `affiliate_invoice_rules` WHERE `id` = ?", [$id]);
            self::logAction(null, $rule['affiliate_id'], 'delete_affiliate_rule', 0, 0, 0, $adminId, 'Deleted affiliate billing rule ID ' . $id);
            return true;
        }
        return false;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 3. OFFER BILLING RULES
    // ──────────────────────────────────────────────────────────────────────────

    public static function getOfferRules(array $filters = []): array
    {
        self::ensureSchema();
        $sql = "
            SELECT r.*, o.name AS offer_name, o.status AS offer_status, o.payout_amount AS payout, o.payout_type
            FROM `offer_invoice_rules` r
            JOIN `offers` o ON o.id = r.offer_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (o.name LIKE ? OR r.offer_id = ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = (int)$filters['search'];
        }
        if (isset($filters['enabled']) && $filters['enabled'] !== '') {
            $sql .= " AND r.enabled = ?";
            $params[] = (int)$filters['enabled'];
        }
        if (!empty($filters['frequency'])) {
            $sql .= " AND r.frequency = ?";
            $params[] = $filters['frequency'];
        }

        $sql .= " ORDER BY r.updated_at DESC";
        return Database::fetchAll($sql, $params);
    }

    public static function getOfferRule(int $offerId): ?array
    {
        self::ensureSchema();
        return Database::fetchOne("SELECT * FROM `offer_invoice_rules` WHERE `offer_id` = ?", [$offerId]);
    }

    public static function saveOfferRule(array $data, ?int $adminId = null): int
    {
        self::ensureSchema();
        $offerId = (int)($data['offer_id'] ?? 0);
        if ($offerId <= 0) {
            throw new InvalidArgumentException('Invalid offer ID');
        }

        $ruleData = [
            'offer_id'            => $offerId,
            'override_global'     => !empty($data['override_global']) ? 1 : 0,
            'frequency'           => in_array($data['frequency'] ?? '', ['monthly', 'every_x_days', 'weekly', 'custom']) ? $data['frequency'] : 'monthly',
            'monthly_day'         => max(1, min(31, (int)($data['monthly_day'] ?? 1))),
            'interval_days'       => max(1, min(365, (int)($data['interval_days'] ?? 15))),
            'minimum_conversions' => max(0, (int)($data['minimum_conversions'] ?? 1)),
            'minimum_revenue'     => max(0, (float)($data['minimum_revenue'] ?? 0.00)),
            'start_date'          => !empty($data['start_date']) ? date('Y-m-d', strtotime($data['start_date'])) : null,
            'end_date'            => !empty($data['end_date']) ? date('Y-m-d', strtotime($data['end_date'])) : null,
            'enabled'             => !empty($data['enabled']) ? 1 : 0,
            'notes'               => trim($data['notes'] ?? ''),
        ];

        $existing = self::getOfferRule($offerId);
        if ($existing) {
            Database::update('offer_invoice_rules', $ruleData, 'id = ?', [$existing['id']]);
            $id = $existing['id'];
        } else {
            $id = Database::insert('offer_invoice_rules', $ruleData);
        }

        self::logAction(null, null, 'save_offer_rule', 1, 0, 0, $adminId, 'Configured offer billing rule for Offer #' . $offerId);
        return $id;
    }

    public static function batchSaveOfferRules(array $offerIds, array $ruleData, ?int $adminId = null): int
    {
        $saved = 0;
        foreach ($offerIds as $offId) {
            $offId = (int)$offId;
            if ($offId <= 0) continue;
            $data = $ruleData;
            $data['offer_id'] = $offId;
            self::saveOfferRule($data, $adminId);
            $saved++;
        }
        return $saved;
    }

    public static function deleteOfferRule(int $id, ?int $adminId = null): bool
    {
        self::ensureSchema();
        $rule = Database::fetchOne("SELECT * FROM `offer_invoice_rules` WHERE `id` = ?", [$id]);
        if ($rule) {
            Database::query("DELETE FROM `offer_invoice_rules` WHERE `id` = ?", [$id]);
            self::logAction(null, null, 'delete_offer_rule', 1, 0, 0, $adminId, 'Deleted offer billing rule ID ' . $id);
            return true;
        }
        return false;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 3b. ADVERTISER BILLING RULES
    // ──────────────────────────────────────────────────────────────────────────

    public static function getAdvertiserRules(array $filters = []): array
    {
        self::ensureSchema();
        $sql = "
            SELECT r.*, u.company AS adv_company, u.first_name, u.last_name, u.email,
                   (SELECT COUNT(*) FROM `offers` o WHERE o.advertiser_id = r.advertiser_id) AS total_offers
            FROM `advertiser_invoice_rules` r
            JOIN `advertisers` adv ON adv.id = r.advertiser_id
            JOIN `users` u ON u.id = adv.user_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (u.company LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR r.advertiser_id = ?)";
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = (int)$filters['search'];
        }
        if (isset($filters['enabled']) && $filters['enabled'] !== '') {
            $sql .= " AND r.enabled = ?";
            $params[] = (int)$filters['enabled'];
        }

        $sql .= " ORDER BY r.updated_at DESC";
        try {
            return Database::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('[AutoInvoiceEngine] getAdvertiserRules error: ' . $e->getMessage());
            return [];
        }
    }

    public static function getAdvertiserRule(int $advertiserId): ?array
    {
        self::ensureSchema();
        try {
            return Database::fetchOne("SELECT * FROM `advertiser_invoice_rules` WHERE `advertiser_id` = ?", [$advertiserId]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function saveAdvertiserRule(array $data, ?int $adminId = null): int
    {
        self::ensureSchema();
        $advertiserId = (int)($data['advertiser_id'] ?? 0);
        if ($advertiserId <= 0) {
            throw new InvalidArgumentException('Invalid advertiser ID');
        }

        $rawFreq = $data['frequency'] ?? 'monthly';
        $freq = in_array($rawFreq, ['monthly', 'every_x_days', 'every_14_days', 'weekly', 'custom']) ? $rawFreq : 'monthly';
        $intervalDays = max(1, min(365, (int)($data['interval_days'] ?? 15)));
        if ($freq === 'every_14_days') {
            $freq = 'every_x_days';
            $intervalDays = 14;
        }

        $ruleData = [
            'advertiser_id'       => $advertiserId,
            'payment_terms'       => trim($data['payment_terms'] ?? 'net30'),
            'minimum_payout'      => max(0, (float)($data['minimum_payout'] ?? 50.00)),
            'minimum_conversions' => max(0, (int)($data['minimum_conversions'] ?? 0)),
            'frequency'           => $freq,
            'monthly_day'         => max(1, min(31, (int)($data['monthly_day'] ?? 1))),
            'interval_days'       => $intervalDays,
            'currency'            => strtoupper(trim($data['currency'] ?? 'USD')),
            'enabled'             => !empty($data['enabled']) ? 1 : 0,
            'notes'               => trim($data['notes'] ?? ''),
        ];

        $existing = self::getAdvertiserRule($advertiserId);
        if ($existing) {
            Database::update('advertiser_invoice_rules', $ruleData, 'id = ?', [$existing['id']]);
            $id = $existing['id'];
        } else {
            $id = Database::insert('advertiser_invoice_rules', $ruleData);
        }

        self::logAction(null, null, 'save_advertiser_rule', 0, 0, 0, $adminId, 'Configured advertiser billing rule for Adv #' . $advertiserId);
        return $id;
    }

    public static function batchSaveAdvertiserRules(array $advertiserIds, array $ruleData, ?int $adminId = null): int
    {
        $saved = 0;
        foreach ($advertiserIds as $advId) {
            $advId = (int)$advId;
            if ($advId <= 0) continue;
            $data = $ruleData;
            $data['advertiser_id'] = $advId;
            self::saveAdvertiserRule($data, $adminId);
            $saved++;
        }
        return $saved;
    }

    public static function deleteAdvertiserRule(int $id, ?int $adminId = null): bool
    {
        self::ensureSchema();
        $rule = Database::fetchOne("SELECT * FROM `advertiser_invoice_rules` WHERE `id` = ?", [$id]);
        if ($rule) {
            Database::query("DELETE FROM `advertiser_invoice_rules` WHERE `id` = ?", [$id]);
            self::logAction(null, null, 'delete_advertiser_rule', 0, 0, 0, $adminId, 'Deleted advertiser billing rule ID ' . $id);
            return true;
        }
        return false;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 4. PRIORITY RULE ENGINE & PERIOD CALCULATION
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Resolve effective billing rule configuration for an Affiliate (and optionally an Offer context).
     * Priority: 1. Affiliate Override -> 2. Offer Override (if single offer context) -> 3. Global Schedule.
     */
    public static function resolveEffectiveRule(int $affiliateId, ?int $offerId = null): array
    {
        $global = self::getGlobalSchedule();

        // 1. Check Affiliate Rule
        $affRule = self::getAffiliateRule($affiliateId);
        if ($affRule && !empty($affRule['enabled']) && !empty($affRule['override_global'])) {
            $scopeApplies = true;
            if (($affRule['offer_scope'] ?? 'all') === 'specific' && $offerId) {
                $allowed = !empty($affRule['specific_offers']) ? json_decode($affRule['specific_offers'], true) : [];
                if (!empty($allowed) && !in_array((int)$offerId, $allowed)) {
                    $scopeApplies = false;
                }
            }

            if ($scopeApplies) {
                return [
                    'source'              => 'affiliate',
                    'offer_scope'         => $affRule['offer_scope'] ?? 'all',
                    'specific_offers'     => $affRule['specific_offers'] ?? null,
                    'frequency'           => $affRule['frequency'],
                    'period_type'         => $affRule['period_type'] ?? $global['period_type'] ?? 'all_unbilled',
                    'custom_start_day'    => (int)($affRule['custom_start_day'] ?? $global['custom_start_day'] ?? 1),
                    'custom_end_day'      => (int)($affRule['custom_end_day'] ?? $global['custom_end_day'] ?? 31),
                    'custom_period_start' => $affRule['custom_period_start'] ?? $global['custom_period_start'] ?? null,
                    'custom_period_end'   => $affRule['custom_period_end'] ?? $global['custom_period_end'] ?? null,
                    'monthly_day'         => (int)$affRule['monthly_day'],
                    'interval_days'       => (int)$affRule['interval_days'],
                    'minimum_amount'      => (float)$affRule['minimum_amount'],
                    'currency'            => $affRule['currency'] ?: $global['currency'],
                    'payment_method'      => $affRule['payment_method'],
                    'payment_terms'       => $affRule['payment_terms'] ?: $global['payment_terms'],
                    'start_date'          => $affRule['start_date'],
                    'end_date'            => $affRule['end_date'],
                ];
            }
        }

        // 2. Check Offer Rule (if specified)
        if ($offerId) {
            $offerRule = self::getOfferRule($offerId);
            if ($offerRule && !empty($offerRule['enabled']) && !empty($offerRule['override_global'])) {
                return [
                    'source'          => 'offer',
                    'frequency'       => $offerRule['frequency'],
                    'monthly_day'     => (int)$offerRule['monthly_day'],
                    'interval_days'   => (int)$offerRule['interval_days'],
                    'minimum_amount'  => (float)$global['min_payout_threshold'],
                    'minimum_revenue' => (float)$offerRule['minimum_revenue'],
                    'minimum_convs'   => (int)$offerRule['minimum_conversions'],
                    'currency'        => $global['currency'],
                    'payment_method'  => null,
                    'payment_terms'   => $global['payment_terms'],
                    'start_date'      => $offerRule['start_date'],
                    'end_date'        => $offerRule['end_date'],
                ];
            }
        }

        // 3. Fallback to Global Schedule
        return [
            'source'              => 'global',
            'frequency'           => $global['frequency'],
            'period_type'         => $global['period_type'] ?? 'prev_month',
            'custom_start_day'    => (int)($global['custom_start_day'] ?? 1),
            'custom_end_day'      => (int)($global['custom_end_day'] ?? 31),
            'custom_period_start' => $global['custom_period_start'] ?? null,
            'custom_period_end'   => $global['custom_period_end'] ?? null,
            'monthly_day'         => (int)$global['monthly_day'],
            'interval_days'       => (int)$global['interval_days'],
            'minimum_amount'      => (float)$global['min_payout_threshold'],
            'currency'            => $global['currency'],
            'payment_method'      => null,
            'payment_terms'       => $global['payment_terms'],
            'start_date'          => null,
            'end_date'            => null,
        ];
    }

    /**
     * Compute billing period [start_date, end_date, due_date] given frequency, terms, and custom period configuration.
     */
    public static function computePeriod(
        string $frequency,
        int $monthlyDay = 1,
        int $intervalDays = 15,
        string $paymentTerms = 'net15',
        ?string $refDateStr = null,
        string $periodType = 'prev_month',
        int $customStartDay = 1,
        int $customEndDay = 31,
        ?string $customStartDate = null,
        ?string $customEndDate = null
    ): array {
        $today = $refDateStr ? new DateTime($refDateStr) : new DateTime();
        $dayOfMonth = (int)$today->format('j');

        // Check if explicit custom start/end dates are provided
        if ($periodType === 'all_unbilled') {
            $start = new DateTime('2020-01-01');
            $end   = clone $today;
        } elseif ($periodType === 'custom_dates' && !empty($customStartDate) && !empty($customEndDate)) {
            $start = new DateTime($customStartDate);
            $end   = new DateTime($customEndDate);
        } elseif ($periodType === 'current_month') {
            $start = new DateTime('first day of this month');
            $end   = clone $today;
        } elseif ($periodType === 'bi_monthly_1_15') {
            $start = new DateTime('first day of this month');
            $end   = clone $start;
            $end->modify('+14 days');
        } elseif ($periodType === 'bi_monthly_16_end') {
            $start = new DateTime('first day of last month');
            $start->modify('+15 days');
            $end = new DateTime('last day of last month');
        } elseif ($periodType === 'custom_days') {
            $sDay = max(1, min(31, $customStartDay));
            $eDay = max(1, min(31, $customEndDay));
            if ($sDay <= $eDay) {
                $prevMonth = new DateTime('first day of last month');
                $maxDays = (int)$prevMonth->format('t');
                $start = clone $prevMonth;
                $start->setDate((int)$prevMonth->format('Y'), (int)$prevMonth->format('m'), min($sDay, $maxDays));
                $end = clone $prevMonth;
                $end->setDate((int)$prevMonth->format('Y'), (int)$prevMonth->format('m'), min($eDay, $maxDays));
            } else {
                $prevMonth = new DateTime('first day of last month');
                $thisMonth = new DateTime('first day of this month');
                $start = clone $prevMonth;
                $start->setDate((int)$prevMonth->format('Y'), (int)$prevMonth->format('m'), min($sDay, (int)$prevMonth->format('t')));
                $end = clone $thisMonth;
                $end->setDate((int)$thisMonth->format('Y'), (int)$thisMonth->format('m'), min($eDay, (int)$thisMonth->format('t')));
            }
        } elseif ($periodType === 'bi_weekly_14') {
            if ($dayOfMonth <= 14) {
                $start = new DateTime('first day of this month');
                $end = clone $start;
                $end->modify('+13 days'); // 14th
            } else {
                $start = new DateTime('first day of this month');
                $start->modify('+14 days'); // 15th
                $end = clone $start;
                $end->modify('+13 days'); // 28th
            }
        } elseif ($periodType === 'last_14_days') {
            $end = clone $today;
            $end->modify('-1 day');
            $start = clone $end;
            $start->modify('-13 days');
        } elseif ($periodType === 'rolling_days') {
            $days = max(1, $intervalDays);
            $end = clone $today;
            $end->modify('-1 day');
            $start = clone $end;
            $start->modify('-' . ($days - 1) . ' days');
        } else {
            switch ($frequency) {
                case 'monthly':
                    $start = new DateTime('first day of last month');
                    $end   = new DateTime('last day of last month');
                    break;

                case 'every_x_days':
                    $days = max(1, $intervalDays);
                    if ($days == 14) {
                        $end = clone $today;
                        $end->modify('-1 day');
                        $start = clone $end;
                        $start->modify('-13 days');
                    } elseif ($days == 15) {
                        if ($dayOfMonth <= 15) {
                            $start = new DateTime('first day of last month');
                            $start->modify('+15 days');
                            $end = new DateTime('last day of last month');
                        } else {
                            $start = new DateTime('first day of this month');
                            $end   = clone $start;
                            $end->modify('+14 days');
                        }
                    } else {
                        $end = clone $today;
                        $end->modify('-1 day');
                        $start = clone $end;
                        $start->modify('-' . ($days - 1) . ' days');
                    }
                    break;

                case 'weekly':
                    $end = clone $today;
                    $end->modify('last sunday');
                    $start = clone $end;
                    $start->modify('-6 days');
                    break;

                case 'custom':
                default:
                    $start = new DateTime('first day of last month');
                    $end   = new DateTime('last day of last month');
                    break;
            }
        }

        // Calculate Due Date based on payment terms (Paid time)
        $due = clone $today;
        $termLower = strtolower(str_replace([' ', '-', '_'], '', $paymentTerms));
        if (str_contains($termLower, 'every14') || str_contains($termLower, '14days') || str_contains($termLower, 'net14') || str_contains($termLower, 'biweekly14')) {
            // Every 14 Days generation -> Paid time is 2 days after invoice generation
            $due->modify('+2 days');
        } elseif (str_contains($termLower, 'net60')) {
            $due->modify('+60 days');
        } elseif (str_contains($termLower, 'net45')) {
            $due->modify('+45 days');
        } elseif (str_contains($termLower, 'net30')) {
            $due->modify('+30 days');
        } elseif (str_contains($termLower, 'net15')) {
            $due->modify('+15 days');
        } elseif (str_contains($termLower, 'net7')) {
            $due->modify('+7 days');
        } elseif (str_contains($termLower, 'biweekly')) {
            $due->modify('+2 days');
        } elseif (str_contains($termLower, 'weekly')) {
            $due->modify('+2 days');
        } elseif (str_contains($termLower, 'immediate')) {
            // Immediate
        } else {
            $due->modify('+2 days');
        }

        return [
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            $due->format('Y-m-d'),
        ];
    }

    /**
     * Check if an active invoice already exists for the given affiliate and exact period (Duplicate Protection).
     */
    public static function isDuplicate(int $affiliateId, string $periodStart, string $periodEnd, ?int $excludeInvoiceId = null): bool
    {
        self::ensureSchema();
        if ($periodStart === '2020-01-01') {
            // Lifetime unbilled calculation relies on conversion level (c.invoice_id IS NULL) check
            return false;
        }

        $sql = "
            SELECT id, invoice_number, is_auto FROM `invoices`
            WHERE `affiliate_id` = ?
              AND `period_start` = ?
              AND `period_end` = ?
              AND `status` NOT IN ('void', 'cancelled')
        ";
        $params = [$affiliateId, $periodStart, $periodEnd];
        if ($excludeInvoiceId) {
            $sql .= " AND `id` != ?";
            $params[] = $excludeInvoiceId;
        }

        $row = Database::fetchOne($sql, $params);
        return !empty($row);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 5. CONVERSION RETRIEVAL & GROUPING
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Retrieve eligible approved uninvoiced conversions for an affiliate in a date range.
     * Excludes: pending, rejected, chargeback, refunded, already invoiced (invoice_id > 0), hidden.
     */
    public static function getEligibleConversions(
        int $affiliateId,
        string $pStart,
        string $pEnd,
        array $filters = []
    ): array {
        self::ensureSchema();
        $dateCondition = "c.converted_at BETWEEN ? AND ?";
        $params = [$affiliateId, $pStart . ' 00:00:00', $pEnd . ' 23:59:59'];
        if ($pStart === '2020-01-01' || $pStart <= '2020-01-01') {
            $dateCondition = "c.converted_at <= ?";
            $params = [$affiliateId, $pEnd . ' 23:59:59'];
        }

        $sql = "
            SELECT c.id AS db_id, c.conversion_id, c.payout, c.country,
                   c.offer_id, o.name AS offer_name, o.advertiser_id,
                   COALESCE(adv_u.company, CONCAT(adv_u.first_name, ' ', adv_u.last_name), 'Direct') AS advertiser_name,
                   c.converted_at, c.status
            FROM `conversions` c
            JOIN `offers` o ON o.id = c.offer_id
            LEFT JOIN `advertisers` adv ON adv.id = o.advertiser_id
            LEFT JOIN `users` adv_u ON adv_u.id = adv.user_id
            WHERE c.affiliate_id = ?
              AND {$dateCondition}
              AND c.status = 'approved'
              AND COALESCE(c.is_hidden, 0) = 0
              AND COALESCE(c.is_fraud, 0) = 0
              AND (c.fraud_score IS NULL OR c.fraud_score < 80)
              AND (c.invoice_id IS NULL OR c.invoice_id = 0)
        ";

        if (!empty($filters['offer_id'])) {
            if (is_array($filters['offer_id'])) {
                $placeholders = implode(',', array_fill(0, count($filters['offer_id']), '?'));
                $sql .= " AND c.offer_id IN ($placeholders)";
                foreach ($filters['offer_id'] as $oid) $params[] = (int)$oid;
            } else {
                $sql .= " AND c.offer_id = ?";
                $params[] = (int)$filters['offer_id'];
            }
        }
        if (!empty($filters['country'])) {
            $sql .= " AND c.country = ?";
            $params[] = strtoupper(trim($filters['country']));
        }

        $sql .= " ORDER BY o.id ASC, c.converted_at ASC";

        try {
            return Database::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('[AutoInvoiceEngine] Error fetching conversions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Group conversions into invoice line items and check offer/affiliate threshold constraints.
     */
    public static function buildInvoiceItemsFromConversions(array $conversions): array
    {
        if (empty($conversions)) {
            return ['items' => [], 'raw_items' => [], 'total' => 0.0, 'conversion_count' => 0, 'db_ids' => []];
        }

        $byOffer = [];
        $rawItems = [];
        $dbIds = [];
        $grandTotal = 0.0;
        $totalConvs = 0;

        foreach ($conversions as $c) {
            $oid = (int)$c['offer_id'];
            $payout = (float)$c['payout'];
            $dbId = (int)$c['db_id'];
            $advId = (int)($c['advertiser_id'] ?? 0);
            $advName = $c['advertiser_name'] ?? 'Direct';

            if (!isset($byOffer[$oid])) {
                $byOffer[$oid] = [
                    'offer_id'        => $oid,
                    'offer_name'      => $c['offer_name'],
                    'advertiser_id'   => $advId,
                    'advertiser_name' => $advName,
                    'count'           => 0,
                    'total'           => 0.0,
                    'geos'            => [],
                    'conversion_ids'  => [],
                    'db_ids'          => [],
                ];
            }

            $byOffer[$oid]['count']++;
            $byOffer[$oid]['total'] += $payout;
            $byOffer[$oid]['conversion_ids'][] = $c['conversion_id'];
            $byOffer[$oid]['db_ids'][] = $dbId;
            if (!empty($c['country']) && !in_array($c['country'], $byOffer[$oid]['geos'])) {
                $byOffer[$oid]['geos'][] = strtoupper($c['country']);
            }

            $rawItems[] = [
                'conversion_id'    => $c['conversion_id'],
                'conversion_db_id' => $dbId,
                'offer_id'         => $oid,
                'offer_name'       => $c['offer_name'],
                'advertiser_id'    => $advId,
                'advertiser_name'  => $advName,
                'geo'              => $c['country'] ?? '',
                'payout'           => $payout,
                'conversion_date'  => $c['converted_at'],
            ];

            $dbIds[] = $dbId;
            $grandTotal += $payout;
            $totalConvs++;
        }

        $items = [];
        foreach ($byOffer as $oid => $off) {
            $count = $off['count'];
            $total = round($off['total'], 4);
            $rate  = $count > 0 ? round($total / $count, 4) : 0.0;
            $geosStr = !empty($off['geos']) ? ' [' . implode(', ', array_slice($off['geos'], 0, 5)) . ']' : '';
            $offCode = 'OFF-' . str_pad((string)$off['offer_id'], 4, '0', STR_PAD_LEFT);

            $items[] = [
                'offer_id'         => $off['offer_id'],
                'offer_name'       => $off['offer_name'],
                'advertiser_id'    => $off['advertiser_id'],
                'advertiser_name'  => $off['advertiser_name'],
                'campaign_id'      => $offCode,
                'description'      => $off['offer_name'] . ' (' . $offCode . ')' . $geosStr,
                'geo'              => implode(',', $off['geos']),
                'conversion_count' => $count,
                'qty'              => $count,
                'rate'             => $rate,
                'amount'           => $total,
                'conversion_ids'   => $off['conversion_ids'],
                'db_ids'           => $off['db_ids'],
            ];
        }

        return [
            'items'            => $items,
            'raw_items'        => $rawItems,
            'total'            => round($grandTotal, 4),
            'conversion_count' => $totalConvs,
            'db_ids'           => $dbIds,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 6. INVOICE GENERATION TRANSACTION
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Generate a complete invoice for an affiliate with transaction safety, PDF, email, and logs.
     */
    public static function createInvoice(array $payload, ?int $adminId = null, bool $isAuto = false): array
    {
        self::ensureSchema();

        $affiliateId = (int)($payload['affiliate_id'] ?? 0);
        if ($affiliateId <= 0) {
            return ['success' => false, 'error' => 'Invalid affiliate ID'];
        }

        $aff = Database::fetchOne(
            "SELECT af.*, u.email, u.first_name, u.last_name, u.status as user_status
             FROM `affiliates` af
             JOIN `users` u ON u.id = af.user_id
             WHERE af.id = ?",
            [$affiliateId]
        );
        if (!$aff) {
            return ['success' => false, 'error' => 'Affiliate not found'];
        }

        // Resolve rule
        $rule = self::resolveEffectiveRule($affiliateId);
        $periodStart = !empty($payload['period_start']) ? $payload['period_start'] : null;
        $periodEnd   = !empty($payload['period_end'])   ? $payload['period_end']   : null;
        $dueDate     = !empty($payload['due_date'])     ? $payload['due_date']     : null;

        if (!$periodStart || !$periodEnd) {
            [$calcStart, $calcEnd, $calcDue] = self::computePeriod(
                $rule['frequency'],
                $rule['monthly_day'] ?? 1,
                $rule['interval_days'] ?? 15,
                $rule['payment_terms'] ?? 'net15',
                null,
                $rule['period_type'] ?? 'prev_month',
                $rule['custom_start_day'] ?? 1,
                $rule['custom_end_day'] ?? 31,
                $rule['custom_period_start'] ?? null,
                $rule['custom_period_end'] ?? null
            );
            $periodStart = $periodStart ?: $calcStart;
            $periodEnd   = $periodEnd   ?: $calcEnd;
            $dueDate     = $dueDate     ?: $calcDue;
        }

        // Duplicate Check
        if (self::isDuplicate($affiliateId, $periodStart, $periodEnd)) {
            return [
                'success' => false,
                'error'   => "Duplicate invoice: An invoice for Affiliate #{$affiliateId} covering {$periodStart} to {$periodEnd} already exists.",
                'skipped' => true,
            ];
        }

        // Gather conversions or use manual items
        $taxRate    = max(0, (float)($payload['tax_rate'] ?? 0));
        $adjustment = (float)($payload['adjustment'] ?? 0);
        $chargeback = (float)($payload['chargeback_amount'] ?? 0);
        $currency   = strtoupper(trim($payload['currency'] ?? $rule['currency'] ?? 'USD'));
        $notes      = trim($payload['notes'] ?? '');

        if (!empty($payload['manual_items'])) {
            $items     = $payload['manual_items'];
            $rawItems  = $payload['manual_raw_items'] ?? [];
            $dbIds     = $payload['manual_db_ids'] ?? [];
            $subtotal  = (float)($payload['subtotal'] ?? array_sum(array_column($items, 'amount')));
        } else {
            // Apply specific offers scope if configured in affiliate rule
            $offerFilter = !empty($payload['offer_ids']) ? $payload['offer_ids'] : null;
            if (empty($offerFilter) && ($rule['offer_scope'] ?? '') === 'specific' && !empty($rule['specific_offers'])) {
                $offerFilter = json_decode($rule['specific_offers'], true);
            }

            $convs = self::getEligibleConversions($affiliateId, $periodStart, $periodEnd, [
                'offer_id' => $offerFilter,
                'country'  => $payload['country'] ?? null,
            ]);
            $built = self::buildInvoiceItemsFromConversions($convs);

            if (!empty($built['items'])) {
                // Group items by Advertiser and enforce Advertiser Rules (Minimum Payout & Minimum Conversions)
                $byAdv = [];
                foreach ($built['items'] as $it) {
                    $aId = (int)($it['advertiser_id'] ?? 0);
                    if (!isset($byAdv[$aId])) {
                        $byAdv[$aId] = ['amount' => 0.0, 'conversions' => 0, 'items' => []];
                    }
                    $byAdv[$aId]['amount'] += (float)$it['amount'];
                    $byAdv[$aId]['conversions'] += (int)$it['conversion_count'];
                    $byAdv[$aId]['items'][] = $it;
                }

                $validItems = [];
                $validDbIds = [];
                $validSubtotal = 0.0;

                foreach ($byAdv as $aId => $advGroup) {
                    if ($aId > 0) {
                        $advRule = self::getAdvertiserRule($aId);
                        if ($advRule && !empty($advRule['enabled'])) {
                            $minPayout = (float)($advRule['minimum_payout'] ?? 0);
                            $minConvs  = (int)($advRule['minimum_conversions'] ?? 0);

                            if ($minPayout > 0 && $advGroup['amount'] < $minPayout) {
                                // Affiliate has not reached this Advertiser's minimum payout threshold yet -> Hold
                                continue;
                            }
                            if ($minConvs > 0 && $advGroup['conversions'] < $minConvs) {
                                // Minimum conversions not reached -> Hold
                                continue;
                            }
                        }
                    }

                    // Also check individual offer rules
                    foreach ($advGroup['items'] as $it) {
                        $itemOfferId = (int)$it['offer_id'];
                        $offerRule = self::getOfferRule($itemOfferId);
                        if ($offerRule && !empty($offerRule['enabled'])) {
                            $minRev   = (float)($offerRule['minimum_revenue'] ?? 0);
                            $minConvs = (int)($offerRule['minimum_conversions'] ?? 0);

                            if ($minRev > 0 && (float)$it['amount'] < $minRev) {
                                continue;
                            }
                            if ($minConvs > 0 && (int)$it['conversion_count'] < $minConvs) {
                                continue;
                            }
                        }

                        $validItems[] = $it;
                        $validSubtotal += (float)$it['amount'];
                        if (!empty($it['db_ids'])) {
                            $validDbIds = array_merge($validDbIds, $it['db_ids']);
                        }
                    }
                }

                if (!empty($validItems)) {
                    $validDbMap = array_flip($validDbIds);
                    $validRawItems = [];
                    foreach ($built['raw_items'] as $ri) {
                        if (isset($validDbMap[$ri['conversion_db_id']])) {
                            $validRawItems[] = $ri;
                        }
                    }

                    $items    = $validItems;
                    $rawItems = $validRawItems;
                    $dbIds    = $validDbIds;
                    $subtotal = round($validSubtotal, 4);
                } else {
                    return [
                        'success' => false,
                        'error'   => 'Conversions held: Advertiser or Offer minimum payout threshold has not been reached yet.',
                        'skipped' => true,
                    ];
                }
            } else {
                // Fallback: If affiliate has available account balance >= minimum threshold
                $availBalance = (float)$aff['balance'];
                $minRequired  = isset($payload['min_threshold']) ? (float)$payload['min_threshold'] : (float)($rule['minimum_amount'] ?? 50.00);

                if ($availBalance >= $minRequired && ($rule['offer_scope'] ?? 'all') === 'all') {
                    $items = [[
                        'offer_id'         => 0,
                        'offer_name'       => 'Affiliate Commission Balance',
                        'advertiser_id'    => 0,
                        'advertiser_name'  => 'Network Payout',
                        'campaign_id'      => 'AFF-BALANCE',
                        'description'      => "Affiliate Approved Commission Balance ({$periodStart} to {$periodEnd})",
                        'geo'              => 'ALL',
                        'conversion_count' => 1,
                        'qty'              => 1,
                        'rate'             => $availBalance,
                        'amount'           => $availBalance,
                        'conversion_ids'   => [],
                        'db_ids'           => [],
                    ]];
                    $rawItems = [];
                    $dbIds    = [];
                    $subtotal = $availBalance;
                } else {
                    return [
                        'success' => false,
                        'error'   => 'No unbilled approved conversions found in period (already invoiced manually or 0 conversions).',
                        'skipped' => true,
                    ];
                }
            }
        }

        // Minimum threshold check (Affiliate Rule / Global Schedule)
        $minThreshold = isset($payload['min_threshold']) ? (float)$payload['min_threshold'] : (float)($rule['minimum_amount'] ?? 50.00);
        if ($subtotal < $minThreshold) {
            return [
                'success' => false,
                'error'   => "Subtotal ($" . number_format($subtotal, 2) . ") is below the minimum payable threshold ($" . number_format($minThreshold, 2) . ").",
                'skipped' => true,
            ];
        }

        // Calculate tax and total
        $taxAmount  = round($subtotal * ($taxRate / 100), 4);
        $total      = round($subtotal + $taxAmount + $adjustment - $chargeback, 4);

        // Generate unique invoice number
        $global = self::getGlobalSchedule();
        $prefix = !empty($payload['prefix']) ? $payload['prefix'] : ($global['invoice_prefix'] ?: 'AFFSCASH-INV-');
        $invNum = self::generateUniqueInvoiceNumber($prefix);

        // Begin Transaction
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $invoiceData = [
                'invoice_number'    => $invNum,
                'type'              => 'affiliate_payout',
                'affiliate_id'      => $affiliateId,
                'advertiser_id'     => null,
                'manager_id'        => null,
                'balance_before'    => (float)$aff['balance'],
                'balance_after'     => max(0, (float)$aff['balance'] - $total),
                'period_start'      => $periodStart,
                'period_end'        => $periodEnd,
                'billing_start'     => $periodStart,
                'billing_end'       => $periodEnd,
                'items'             => json_encode($items),
                'subtotal'          => $subtotal,
                'adjustment'        => $adjustment,
                'chargeback_amount' => $chargeback,
                'tax_rate'          => $taxRate,
                'tax_amount'        => $taxAmount,
                'total'             => $total,
                'currency'          => $currency,
                'status'            => 'sent',
                'notes'             => $notes,
                'due_date'          => $dueDate,
                'paid_at'           => null,
                'generated_at'      => date('Y-m-d H:i:s'),
                'pdf_path'          => null,
                'is_auto'           => $isAuto ? 1 : 0,
                'period_hash'       => md5($affiliateId . '|' . $periodStart . '|' . $periodEnd),
                'balance_deducted'  => 1,
                'created_by'        => $adminId,
                'created_at'        => date('Y-m-d H:i:s'),
            ];

            $invoiceId = Database::insert('invoices', $invoiceData);

            // Insert invoice items
            if (!empty($rawItems)) {
                foreach ($rawItems as $ri) {
                    Database::insert('invoice_items', [
                        'invoice_id'       => $invoiceId,
                        'conversion_id'    => $ri['conversion_id'] ?? null,
                        'conversion_db_id' => $ri['conversion_db_id'] ?? null,
                        'offer_id'         => $ri['offer_id'] ?? null,
                        'offer_name'       => $ri['offer_name'] ?? null,
                        'geo'              => $ri['geo'] ?? null,
                        'payout'           => (float)($ri['payout'] ?? 0),
                        'conversion_date'  => $ri['conversion_date'] ?? date('Y-m-d H:i:s'),
                        'currency'         => $currency,
                    ]);
                }
            } else {
                foreach ($items as $it) {
                    Database::insert('invoice_items', [
                        'invoice_id'       => $invoiceId,
                        'conversion_id'    => null,
                        'conversion_db_id' => null,
                        'offer_id'         => $it['offer_id'] ?? null,
                        'offer_name'       => $it['offer_name'] ?? ($it['description'] ?? 'Item'),
                        'geo'              => $it['geo'] ?? null,
                        'payout'           => (float)($it['amount'] ?? 0),
                        'conversion_date'  => date('Y-m-d H:i:s'),
                        'currency'         => $currency,
                    ]);
                }
            }

            // Link conversions to this invoice
            if (!empty($dbIds)) {
                $chunks = array_chunk($dbIds, 500);
                foreach ($chunks as $chunk) {
                    $ph = implode(',', array_fill(0, count($chunk), '?'));
                    $params = array_merge([$invoiceId], $chunk);
                    Database::query("UPDATE `conversions` SET `invoice_id` = ? WHERE `id` IN ($ph)", $params);
                }
            }

            // Deduct balance from affiliate (floor at 0)
            Database::query(
                "UPDATE `affiliates` SET `balance` = GREATEST(0, `balance` - ?) WHERE `id` = ?",
                [$total, $affiliateId]
            );

            // Commit DB transaction before generating PDF & sending email
            $pdo->commit();

        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[AutoInvoiceEngine] Transaction failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }

        // Generate PDF
        $pdfPath = '';
        try {
            $entityName    = trim($aff['first_name'] . ' ' . $aff['last_name']) ?: 'Affiliate #' . $affId;
            $entityEmail   = $aff['email'];
            $paymentMethod = $rule['payment_method'] ?: $aff['payment_method'] ?: 'Standard Payout';
            $paymentDetails= $aff['payment_details'] ?? '';

            $invoiceRow = Database::fetchOne("SELECT * FROM `invoices` WHERE `id` = ?", [$invoiceId]);
            $pdfPath = InvoicePDF::generate(
                $invoiceRow,
                $items,
                $entityName,
                $entityEmail,
                $paymentMethod,
                $paymentDetails
            );

            $relativePdfPath = '/uploads/invoices/' . basename($pdfPath);
            Database::query("UPDATE `invoices` SET `pdf_path` = ? WHERE `id` = ?", [$relativePdfPath, $invoiceId]);
        } catch (\Throwable $e) {
            error_log('[AutoInvoiceEngine] PDF generation error: ' . $e->getMessage());
        }

        // Send Email if configured
        $emailSent = false;
        if (!empty($global['auto_email']) && !empty($aff['email'])) {
            try {
                $emailSent = self::sendInvoiceEmail($invoiceId);
            } catch (\Throwable $e) {
                error_log('[AutoInvoiceEngine] Email dispatch error: ' . $e->getMessage());
            }
        }

        // Record Log
        self::logAction(
            $invoiceId,
            $affiliateId,
            $isAuto ? 'auto_generated' : 'manual_generated',
            count($items),
            $total,
            $isAuto ? 1 : 0,
            $adminId,
            "Generated invoice {$invNum} for Affiliate #{$affiliateId} ({$periodStart} - {$periodEnd}) Amount: \${$total}"
        );

        return [
            'success'        => true,
            'invoice_id'     => $invoiceId,
            'invoice_number' => $invNum,
            'affiliate_id'   => $affiliateId,
            'subtotal'       => $subtotal,
            'total'          => $total,
            'period_start'   => $periodStart,
            'period_end'     => $periodEnd,
            'pdf_path'       => $pdfPath,
            'email_sent'     => $emailSent,
        ];
    }

    /**
     * Generate an incremental & unique invoice number using prefix.
     */
    private static function generateUniqueInvoiceNumber(string $prefix = 'AFFSCASH-INV-'): string
    {
        $yearMonth = date('Ym');
        $prefix = rtrim($prefix, '-') . '-' . $yearMonth . '-';

        // Check highest existing invoice number matching prefix
        try {
            $lastRow = Database::fetchOne(
                "SELECT invoice_number FROM `invoices` WHERE `invoice_number` LIKE ? ORDER BY `id` DESC LIMIT 1",
                [$prefix . '%']
            );
            if ($lastRow && preg_match('/-(\d+)$/', $lastRow['invoice_number'], $matches)) {
                $nextSeq = (int)$matches[1] + 1;
            } else {
                $global = self::getGlobalSchedule();
                $nextSeq = max(100001, (int)($global['starting_number'] ?? 100001));
            }
        } catch (\Throwable $e) {
            $nextSeq = 100001;
        }

        $invNum = $prefix . str_pad((string)$nextSeq, 6, '0', STR_PAD_LEFT);

        // Fallback check
        $exists = Database::fetchOne("SELECT id FROM `invoices` WHERE `invoice_number` = ?", [$invNum]);
        if ($exists) {
            $invNum = $prefix . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        }

        return $invNum;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 7. BATCH AUTOMATIC GENERATION (CRON SCHEDULER)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Executes the automatic invoice generation pipeline.
     * Evaluates active affiliates, applies priority rules, prevents duplicates, generates invoices.
     */
    public static function runAutoGeneration(string $triggeredBy = 'cron', ?int $adminId = null): array
    {
        self::ensureSchema();
        $global = self::getGlobalSchedule();

        $force = ($triggeredBy === 'admin_manual' || !empty($_GET['force']));
        if (empty($global['enabled']) && !$force) {
            return [
                'status'    => 'disabled',
                'message'   => 'Global auto invoice scheduler is currently disabled. (Turn ON in Admin > Auto Invoices > 1. Invoice Scheduler or pass &force=1 to test)',
                'generated' => 0,
                'skipped'   => 0,
                'total_amt' => 0.0,
                'logs'      => ['Scheduler is turned OFF in Admin Settings. Please enable "Enable Automated Invoice Generation" in tab 1.'],
            ];
        }

        // Fetch candidate active affiliates who have uninvoiced conversions or positive balance
        $candidates = Database::fetchAll("
            SELECT af.id, af.balance, af.payment_threshold, af.payment_method, af.payment_terms,
                   u.first_name, u.last_name, u.email, u.status as user_status
            FROM `affiliates` af
            JOIN `users` u ON u.id = af.user_id
            WHERE u.status = 'active'
            ORDER BY af.id ASC
        ");

        $generated = 0;
        $skipped   = 0;
        $totalAmt  = 0.0;
        $jobLogs   = [];

        foreach ($candidates as $cand) {
            $affId = (int)$cand['id'];

            // Priority rule resolution
            $rule = self::resolveEffectiveRule($affId);

            // Compute billing period
            [$pStart, $pEnd, $dueDate] = self::computePeriod(
                $rule['frequency'],
                $rule['monthly_day'] ?? 1,
                $rule['interval_days'] ?? 15,
                $rule['payment_terms'] ?? 'net15',
                null,
                $rule['period_type'] ?? 'prev_month',
                $rule['custom_start_day'] ?? 1,
                $rule['custom_end_day'] ?? 31,
                $rule['custom_period_start'] ?? null,
                $rule['custom_period_end'] ?? null
            );

            // Duplicate check
            if (self::isDuplicate($affId, $pStart, $pEnd)) {
                $skipped++;
                $jobLogs[] = "Affiliate #{$affId}: Skipped (Invoice already generated for period {$pStart} - {$pEnd})";
                continue;
            }

            // Create invoice
            $res = self::createInvoice([
                'affiliate_id' => $affId,
                'period_start' => $pStart,
                'period_end'   => $pEnd,
                'due_date'     => $dueDate,
            ], $adminId, true);

            if (!empty($res['success'])) {
                $generated++;
                $totalAmt += (float)$res['total'];
                $jobLogs[] = "Affiliate #{$affId}: Generated {$res['invoice_number']} for \${$res['total']}";
            } else {
                $skipped++;
                $jobLogs[] = "Affiliate #{$affId}: Skipped ({$res['error']})";
            }
        }

        // Update last run & compute next run timestamp
        $nowUtc = date('Y-m-d H:i:s');
        $nextRun = self::computeNextRunTimestamp(
            $global['frequency'],
            (int)$global['monthly_day'],
            (int)$global['interval_days'],
            $global['invoice_time'] ?? '00:00:00',
            $global['timezone'] ?? 'UTC'
        );

        Database::query(
            "UPDATE `invoice_schedules` SET `last_run_at` = ?, `next_run_at` = ? WHERE `id` = ?",
            [$nowUtc, $nextRun, $global['id']]
        );

        self::logAction(
            null,
            null,
            'cron_run',
            $generated,
            $totalAmt,
            1,
            $adminId,
            "Auto invoice cron executed ({$triggeredBy}): {$generated} generated, {$skipped} skipped, Total: \${$totalAmt}"
        );

        return [
            'status'    => 'completed',
            'generated' => $generated,
            'skipped'   => $skipped,
            'total_amt' => round($totalAmt, 2),
            'next_run'  => $nextRun,
            'logs'      => $jobLogs,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 8. INVOICE EMAIL DELIVERY
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Send professional email notification with invoice details & attachment to affiliate.
     */
    public static function sendInvoiceEmail(int $invoiceId): bool
    {
        self::ensureSchema();
        $inv = Database::fetchOne("
            SELECT i.*, af.id as affiliate_id, u.first_name, u.last_name, u.email
            FROM `invoices` i
            JOIN `affiliates` af ON af.id = i.affiliate_id
            JOIN `users` u ON u.id = af.user_id
            WHERE i.id = ?
        ", [$invoiceId]);

        if (!$inv || empty($inv['email'])) {
            return false;
        }

        $affName = trim($inv['first_name'] . ' ' . $inv['last_name']) ?: 'Valued Affiliate';
        $siteName = Config::get('config', 'app.name') ?: 'Affscash.net';
        $appUrl   = rtrim(Config::get('config', 'app.url') ?: 'https://affscash.net', '/');
        $invNum   = $inv['invoice_number'];
        $amount   = '$' . number_format((float)$inv['total'], 2);
        $period   = date('d M Y', strtotime($inv['period_start'])) . ' – ' . date('d M Y', strtotime($inv['period_end']));
        $dueDate  = !empty($inv['due_date']) ? date('d M Y', strtotime($inv['due_date'])) : 'Net Terms';
        $viewUrl  = $appUrl . '/affiliate/billing-history/' . $inv['id'];

        $subject = "Affscash Invoice #{$invNum} [{$amount}]";
        $body = "
            <div style=\"font-family: Arial, sans-serif; color: #1e293b; max-width: 600px; margin: 0 auto; line-height: 1.6;\">
                <div style=\"background: #4f46e5; padding: 24px; text-align: center; border-radius: 8px 8px 0 0;\">
                    <h1 style=\"color: #ffffff; margin: 0; font-size: 24px; font-weight: 700;\">{$siteName}</h1>
                    <p style=\"color: #e0e7ff; margin: 4px 0 0 0; font-size: 14px;\">Affiliate Payout Invoice</p>
                </div>
                <div style=\"padding: 30px; background: #ffffff; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px;\">
                    <p style=\"font-size: 16px;\">Hello <strong>" . htmlspecialchars($affName) . "</strong>,</p>
                    <p>Your affiliate commission invoice has been automatically generated for the billing period below.</p>
                    
                    <div style=\"background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 18px; margin: 20px 0;\">
                        <table style=\"width: 100%; font-size: 14px; border-collapse: collapse;\">
                            <tr>
                                <td style=\"color: #64748b; padding: 6px 0;\">Invoice Number:</td>
                                <td style=\"font-weight: 700; text-align: right; color: #4f46e5;\">" . htmlspecialchars($invNum) . "</td>
                            </tr>
                            <tr>
                                <td style=\"color: #64748b; padding: 6px 0;\">Billing Period:</td>
                                <td style=\"font-weight: 600; text-align: right;\">{$period}</td>
                            </tr>
                            <tr>
                                <td style=\"color: #64748b; padding: 6px 0;\">Total Payable:</td>
                                <td style=\"font-weight: 800; font-size: 18px; text-align: right; color: #10b981;\">{$amount}</td>
                            </tr>
                            <tr>
                                <td style=\"color: #64748b; padding: 6px 0;\">Estimated Due Date:</td>
                                <td style=\"font-weight: 600; text-align: right;\">{$dueDate}</td>
                            </tr>
                            <tr>
                                <td style=\"color: #64748b; padding: 6px 0;\">Status:</td>
                                <td style=\"font-weight: 600; text-align: right; color: #3b82f6;\">" . ucfirst($inv['status']) . "</td>
                            </tr>
                        </table>
                    </div>

                    <div style=\"text-align: center; margin: 30px 0;\">
                        <a href=\"{$viewUrl}\" style=\"background: #4f46e5; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: 700; font-size: 14px; display: inline-block;\">View Invoice Online &rarr;</a>
                    </div>

                    <p style=\"font-size: 13px; color: #64748b; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 15px;\">
                        If you have any questions regarding your billing or payment information, please contact your affiliate manager or reply directly to this email.
                    </p>
                    <p style=\"font-size: 13px; color: #94a3b8; margin: 0;\">
                        Thank you,<br><strong>{$siteName} Finance Team</strong>
                    </p>
                </div>
            </div>
        ";

        // Dispatch via Mailer
        try {
            $sent = Mailer::send($inv['email'], $affName, $subject, $body);
            if ($sent) {
                Database::query("UPDATE `invoices` SET `email_sent_at` = ? WHERE `id` = ?", [date('Y-m-d H:i:s'), $invoiceId]);
                self::logAction($invoiceId, $inv['affiliate_id'], 'email_sent', 1, (float)$inv['total'], 0, null, "Sent invoice email notification to {$inv['email']}");
            }
            return (bool)$sent;
        } catch (\Throwable $e) {
            error_log('[AutoInvoiceEngine] Mailer error: ' . $e->getMessage());
            return false;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 9. STATUS WORKFLOW & INVOICE OPERATIONS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Update invoice status (draft, sent, viewed, paid, void, cancelled).
     */
    public static function updateInvoiceStatus(int $invoiceId, string $newStatus, ?int $adminId = null): bool
    {
        self::ensureSchema();
        $allowed = ['draft', 'sent', 'viewed', 'paid', 'void', 'cancelled'];
        if (!in_array($newStatus, $allowed)) {
            return false;
        }

        $inv = Database::fetchOne("SELECT * FROM `invoices` WHERE `id` = ?", [$invoiceId]);
        if (!$inv) return false;

        $update = ['status' => $newStatus];
        if ($newStatus === 'paid' && empty($inv['paid_at'])) {
            $update['paid_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'viewed' && empty($inv['viewed_at'])) {
            $update['viewed_at'] = date('Y-m-d H:i:s');
        }

        Database::update('invoices', $update, 'id = ?', [$invoiceId]);

        self::logAction(
            $invoiceId,
            $inv['affiliate_id'],
            'status_change',
            1,
            (float)$inv['total'],
            0,
            $adminId,
            "Changed invoice status from '{$inv['status']}' to '{$newStatus}'"
        );

        return true;
    }

    /**
     * Cancel/Void an invoice: unlinks conversions, refunds balance if deducted.
     */
    public static function cancelInvoice(int $invoiceId, ?int $adminId = null, string $reason = ''): bool
    {
        self::ensureSchema();
        $inv = Database::fetchOne("SELECT * FROM `invoices` WHERE `id` = ?", [$invoiceId]);
        if (!$inv || in_array($inv['status'], ['void', 'cancelled'])) {
            return false;
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            // 1. Unlink conversions
            Database::query("UPDATE `conversions` SET `invoice_id` = NULL WHERE `invoice_id` = ?", [$invoiceId]);

            // 2. Refund balance if was deducted
            if (!empty($inv['balance_deducted']) && !empty($inv['affiliate_id'])) {
                Database::query(
                    "UPDATE `affiliates` SET `balance` = `balance` + ? WHERE `id` = ?",
                    [(float)$inv['total'], (int)$inv['affiliate_id']]
                );
            }

            // 3. Mark invoice as cancelled
            Database::update('invoices', [
                'status'           => 'cancelled',
                'balance_deducted' => 0,
                'notes'            => trim(($inv['notes'] ? $inv['notes'] . "\n" : '') . 'Cancelled on ' . date('Y-m-d H:i') . ($reason ? ": {$reason}" : '')),
            ], 'id = ?', [$invoiceId]);

            $pdo->commit();

            self::logAction(
                $invoiceId,
                $inv['affiliate_id'],
                'invoice_cancelled',
                0,
                (float)$inv['total'],
                0,
                $adminId,
                "Cancelled invoice {$inv['invoice_number']}. Conversions unlinked and balance refunded." . ($reason ? " Reason: {$reason}" : '')
            );

            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[AutoInvoiceEngine] Cancel error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an invoice permanently, unlink conversions, and sync balance.
     */
    public static function deleteInvoice(int $invoiceId, ?int $adminId = null): bool
    {
        self::ensureSchema();
        $inv = Database::fetchOne("SELECT * FROM `invoices` WHERE `id` = ?", [$invoiceId]);
        if (!$inv) return false;

        $pdo = Database::getInstance();
        $pdo->beginTransaction();
        try {
            // 1. Unlink conversions
            Database::query("UPDATE `conversions` SET `invoice_id` = NULL WHERE `invoice_id` = ?", [$invoiceId]);

            // 2. Delete invoice items
            Database::query("DELETE FROM `invoice_items` WHERE `invoice_id` = ?", [$invoiceId]);

            // 3. Delete invoice file if exists
            if (!empty($inv['pdf_path'])) {
                $abs = BASE_PATH . '/' . ltrim($inv['pdf_path'], '/');
                if (file_exists($abs)) @unlink($abs);
            }

            // 4. Delete invoice record
            Database::query("DELETE FROM `invoices` WHERE `id` = ?", [$invoiceId]);

            $pdo->commit();

            // 5. Safely recalculate and restore affiliate balance
            if (!empty($inv['affiliate_id'])) {
                self::recalculateAffiliateBalance((int)$inv['affiliate_id']);
            }

            self::logAction(
                $invoiceId,
                $inv['affiliate_id'],
                'invoice_deleted',
                0,
                (float)$inv['total'],
                0,
                $adminId,
                "Deleted invoice {$inv['invoice_number']} and safely reconciled affiliate balance."
            );

            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[AutoInvoiceEngine] deleteInvoice error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Recalculate & restore exact affiliate balance based strictly on
     * (Total Approved Conversions - Total Paid Invoices).
     * Prevents any balance discrepancies, double-deductions, or artificial inflation.
     */
    public static function recalculateAffiliateBalance(?int $affiliateId = null): array
    {
        self::ensureSchema();
        $sql = "SELECT id FROM `affiliates`";
        $params = [];
        if ($affiliateId) {
            $sql .= " WHERE `id` = ?";
            $params[] = $affiliateId;
        }
        $affs = Database::fetchAll($sql, $params);
        $updated = 0;
        $results = [];

        foreach ($affs as $af) {
            $aId = (int)$af['id'];
            
            // 1. Total Approved Conversions (Excluding Hidden, Fraud, Rejected, Declined, Blocked)
            $convRow = Database::fetchOne(
                "SELECT COALESCE(SUM(payout), 0) AS total_earned
                 FROM `conversions`
                 WHERE `affiliate_id` = ? 
                   AND `status` = 'approved' 
                   AND COALESCE(`is_hidden`, 0) = 0
                   AND COALESCE(`is_fraud`, 0) = 0
                   AND (`fraud_score` IS NULL OR `fraud_score` < 80)",
                [$aId]
            );
            $totalEarned = (float)($convRow['total_earned'] ?? 0);

            // 2. Total Paid Invoices
            $paidRow = Database::fetchOne(
                "SELECT COALESCE(SUM(total), 0) AS total_paid
                 FROM `invoices`
                 WHERE `affiliate_id` = ? AND `status` = 'paid'",
                [$aId]
            );
            $totalPaid = (float)($paidRow['total_paid'] ?? 0);

            // 3. Exact Current Balance
            $exactBalance = max(0.00, round($totalEarned - $totalPaid, 4));

            Database::query("UPDATE `affiliates` SET `balance` = ? WHERE `id` = ?", [$exactBalance, $aId]);
            $updated++;
            $results[$aId] = [
                'affiliate_id' => $aId,
                'total_earned' => $totalEarned,
                'total_paid'   => $totalPaid,
                'new_balance'  => $exactBalance,
            ];
        }

        return ['updated_count' => $updated, 'results' => $results];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 10. AUDIT LOGGING
    // ──────────────────────────────────────────────────────────────────────────

    public static function logAction(
        ?int $invoiceId,
        ?int $affiliateId,
        string $action,
        int $offerCount = 0,
        float $amount = 0.0,
        int $isAuto = 0,
        ?int $createdBy = null,
        ?string $details = null
    ): int {
        self::ensureSchema();
        try {
            return Database::insert('invoice_logs', [
                'invoice_id'   => $invoiceId,
                'affiliate_id' => $affiliateId,
                'action'       => $action,
                'offer_count'  => $offerCount,
                'amount'       => $amount,
                'is_auto'      => $isAuto,
                'created_by'   => $createdBy,
                'details'      => $details,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('[AutoInvoiceEngine] Log error: ' . $e->getMessage());
            return 0;
        }
    }

    public static function getLogs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        self::ensureSchema();
        $sql = "
            SELECT l.*, i.invoice_number, u.first_name, u.last_name, u.email as affiliate_email,
                   adm.first_name as admin_first, adm.last_name as admin_last
            FROM `invoice_logs` l
            LEFT JOIN `invoices` i ON i.id = l.invoice_id
            LEFT JOIN `affiliates` af ON af.id = l.affiliate_id
            LEFT JOIN `users` u ON u.id = af.user_id
            LEFT JOIN `users` adm ON adm.id = l.created_by
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['action'])) {
            $sql .= " AND l.action = ?";
            $params[] = $filters['action'];
        }
        if (!empty($filters['affiliate_id'])) {
            $sql .= " AND l.affiliate_id = ?";
            $params[] = (int)$filters['affiliate_id'];
        }
        if (!empty($filters['invoice_id'])) {
            $sql .= " AND l.invoice_id = ?";
            $params[] = (int)$filters['invoice_id'];
        }
        if (!empty($filters['is_auto']) && $filters['is_auto'] !== '') {
            $sql .= " AND l.is_auto = ?";
            $params[] = (int)$filters['is_auto'];
        }

        $sql .= " ORDER BY l.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return Database::fetchAll($sql, $params);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 11. DASHBOARD KPIS & STATS
    // ──────────────────────────────────────────────────────────────────────────

    public static function getDashboardStats(): array
    {
        self::ensureSchema();
        $global = self::getGlobalSchedule();

        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd   = date('Y-m-t 23:59:59');

        $invoicesThisMonth = 0;
        $invoicedAmountMonth = 0.0;
        try {
            $row = Database::fetchOne("
                SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as tot
                FROM `invoices`
                WHERE `created_at` BETWEEN ? AND ? AND `status` NOT IN ('void','cancelled')
            ", [$monthStart, $monthEnd]);
            $invoicesThisMonth   = (int)($row['cnt'] ?? 0);
            $invoicedAmountMonth = (float)($row['tot'] ?? 0.0);
        } catch (\Throwable $_e) {}

        $pendingAmount = 0.0;
        $pendingCount  = 0;
        try {
            $row = Database::fetchOne("
                SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as tot
                FROM `invoices`
                WHERE `status` IN ('sent', 'draft')
            ");
            $pendingCount  = (int)($row['cnt'] ?? 0);
            $pendingAmount = (float)($row['tot'] ?? 0.0);
        } catch (\Throwable $_e) {}

        $paidAmount = 0.0;
        $paidCount  = 0;
        try {
            $row = Database::fetchOne("
                SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as tot
                FROM `invoices`
                WHERE `status` = 'paid'
            ");
            $paidCount  = (int)($row['cnt'] ?? 0);
            $paidAmount = (float)($row['tot'] ?? 0.0);
        } catch (\Throwable $_e) {}

        $affRulesCount = 0;
        try {
            $affRulesCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM `affiliate_invoice_rules` WHERE `enabled` = 1")['c'] ?? 0);
        } catch (\Throwable $_e) {}

        $offerRulesCount = 0;
        try {
            $offerRulesCount = (int)(Database::fetchOne("SELECT COUNT(*) as c FROM `offer_invoice_rules` WHERE `enabled` = 1")['c'] ?? 0);
        } catch (\Throwable $_e) {}

        return [
            'auto_enabled'          => !empty($global['enabled']),
            'frequency'             => $global['frequency'],
            'next_run_at'           => $global['next_run_at'],
            'last_run_at'           => $global['last_run_at'],
            'invoices_this_month'   => $invoicesThisMonth,
            'invoiced_amount_month' => $invoicedAmountMonth,
            'pending_count'         => $pendingCount,
            'pending_amount'        => $pendingAmount,
            'paid_count'            => $paidCount,
            'paid_amount'           => $paidAmount,
            'affiliate_rules_count' => $affRulesCount,
            'offer_rules_count'     => $offerRulesCount,
        ];
    }
}
