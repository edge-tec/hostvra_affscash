<?php
/**
 * Referral System Helper
 * Handles referral code generation, registration hooking, and commission calculation.
 */
class Referral
{
    // ── Auto-create tables if missing ─────────────────────────────────────
    public static function migrate(): void
    {
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `referral_codes` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id`    INT UNSIGNED NOT NULL,
                `role`       ENUM('affiliate','affiliate_manager') NOT NULL,
                `code`       VARCHAR(32) NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_user` (`user_id`),
                UNIQUE KEY `uq_code` (`code`),
                INDEX `idx_role` (`role`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            Database::query("CREATE TABLE IF NOT EXISTS `referral_signups` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `referrer_user_id` INT UNSIGNED NOT NULL,
                `referrer_role`    ENUM('affiliate','affiliate_manager') NOT NULL,
                `referred_aff_id`  INT UNSIGNED NOT NULL,
                `referred_user_id` INT UNSIGNED NOT NULL,
                `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_referred` (`referred_aff_id`),
                INDEX `idx_referrer` (`referrer_user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            Database::query("CREATE TABLE IF NOT EXISTS `referral_commissions` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `referrer_aff_id`   INT UNSIGNED NOT NULL,
                `referred_aff_id`   INT UNSIGNED NOT NULL,
                `conversion_id`     CHAR(36) NOT NULL,
                `base_payout`       DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                `commission_rate`   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `commission_type`   ENUM('percent','fixed') DEFAULT 'percent',
                `commission_amount` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                `status`            ENUM('pending','approved','rejected') DEFAULT 'pending',
                `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_referrer` (`referrer_aff_id`),
                INDEX `idx_referred` (`referred_aff_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {}
    }

    // ── Get or create referral code for a user ─────────────────────────────
    public static function getOrCreateCode(int $userId, string $role): string
    {
        self::migrate();
        $row = Database::fetchOne("SELECT code FROM referral_codes WHERE user_id=?", [$userId]);
        if ($row) return $row['code'];

        // Generate unique code: ROLE prefix + userId + random
        $prefix = $role === 'affiliate_manager' ? 'MGR' : 'AFF';
        $code   = $prefix . strtoupper(base_convert($userId, 10, 36)) . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

        // Ensure unique
        while (Database::fetchOne("SELECT id FROM referral_codes WHERE code=?", [$code])) {
            $code = $prefix . strtoupper(base_convert($userId, 10, 36)) . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        }

        Database::insert('referral_codes', [
            'user_id' => $userId,
            'role'    => $role,
            'code'    => $code,
        ]);

        return $code;
    }

    // ── Resolve a ref code to user_id + role ──────────────────────────────
    public static function resolveCode(string $code): ?array
    {
        self::migrate();
        if (!$code) return null;
        return Database::fetchOne(
            "SELECT rc.user_id, rc.role, u.first_name, u.last_name, u.status
             FROM referral_codes rc
             JOIN users u ON u.id=rc.user_id
             WHERE rc.code=?",
            [strtoupper(trim($code))]
        ) ?: null;
    }

    // ── Store referral on sign-up ─────────────────────────────────────────
    // Called immediately after the new affiliate record is created.
    // $referrerUserId — user.id of whoever shared the link
    // $referrerRole   — 'affiliate' or 'affiliate_manager'
    // $newAffId       — affiliates.id of the new affiliate
    // $newUserId      — users.id of the new affiliate
    public static function recordSignup(
        int $referrerUserId,
        string $referrerRole,
        int $newAffId,
        int $newUserId
    ): void {
        // Note: migrate() must be called BEFORE any active transaction.
        // The caller (RegisterAffiliateController) ensures this.
        try {
            Database::insert('referral_signups', [
                'referrer_user_id' => $referrerUserId,
                'referrer_role'    => $referrerRole,
                'referred_aff_id'  => $newAffId,
                'referred_user_id' => $newUserId,
            ]);
        } catch (\Throwable $e) {}

        // If referrer is an affiliate_manager → assign manager_id on the affiliate
        if ($referrerRole === 'affiliate_manager') {
            $mgr = Database::fetchOne(
                "SELECT id FROM affiliate_managers WHERE user_id=?",
                [$referrerUserId]
            );
            if ($mgr) {
                Database::update('affiliates', ['manager_id' => $mgr['id']], 'id=?', [$newAffId]);
            }
        }

        // If referrer is an affiliate → store referred_by (affiliate.id)
        if ($referrerRole === 'affiliate') {
            $refAff = Database::fetchOne(
                "SELECT id FROM affiliates WHERE user_id=?",
                [$referrerUserId]
            );
            if ($refAff) {
                Database::update('affiliates', ['referred_by' => $refAff['id']], 'id=?', [$newAffId]);
            }
        }
    }

    // ── Calculate and credit referral commission ──────────────────────────
    // Called from postback.php after a conversion is approved.
    // $convId         — conversions.conversion_id
    // $affId          — affiliates.id of the affiliate who just got the conversion
    // $payout         — the approved payout amount
    public static function creditCommission(string $convId, int $affId, float $payout): void
    {
        self::migrate();

        // Find the referrer (must be an affiliate who referred this affiliate)
        $aff = Database::fetchOne(
            "SELECT referred_by FROM affiliates WHERE id=?", [$affId]
        );
        if (!$aff || !$aff['referred_by']) return;

        $referrerAffId = (int)$aff['referred_by'];

        // Make sure referrer exists and is active
        $referrer = Database::fetchOne(
            "SELECT af.id FROM affiliates af JOIN users u ON u.id=af.user_id
             WHERE af.id=? AND u.status='active'",
            [$referrerAffId]
        );
        if (!$referrer) return;

        // Avoid double-crediting for same conversion
        $exists = Database::fetchOne(
            "SELECT id FROM referral_commissions WHERE conversion_id=? AND referrer_aff_id=?",
            [$convId, $referrerAffId]
        );
        if ($exists) return;

        // Get commission rate from settings
        $rate = (float)(Config::get('config', 'app.refer_commission_rate') ?? 5);
        $type = Config::get('config', 'app.refer_commission_type') ?? 'percent';

        if ($rate <= 0) return;

        $commissionAmount = $type === 'fixed'
            ? $rate
            : round(($payout * $rate) / 100, 4);

        if ($commissionAmount <= 0) return;

        // Record the commission
        Database::insert('referral_commissions', [
            'referrer_aff_id'   => $referrerAffId,
            'referred_aff_id'   => $affId,
            'conversion_id'     => $convId,
            'base_payout'       => $payout,
            'commission_rate'   => $rate,
            'commission_type'   => $type,
            'commission_amount' => $commissionAmount,
            'status'            => 'approved',
        ]);

        // Credit referrer's balance
        Database::query(
            "UPDATE affiliates SET balance = balance + ? WHERE id = ?",
            [$commissionAmount, $referrerAffId]
        );
    }

    // ── Get referral link URL ─────────────────────────────────────────────
    public static function getLink(int $userId, string $role): string
    {
        $code   = self::getOrCreateCode($userId, $role);
        $appUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');
        // Route is /register/affiliate — not /register (which has no handler)
        return $appUrl . '/register/affiliate?ref=' . $code;
    }

    // ── Get stats for a referrer ──────────────────────────────────────────
    public static function getStats(int $referrerUserId): array
    {
        self::migrate();
        $totalSignups = Database::fetchOne(
            "SELECT COUNT(*) as c FROM referral_signups WHERE referrer_user_id=?",
            [$referrerUserId]
        )['c'] ?? 0;

        $commStats = Database::fetchOne(
            "SELECT COUNT(*) as total, COALESCE(SUM(commission_amount),0) as earned
             FROM referral_commissions rc
             JOIN affiliates af ON af.id=rc.referrer_aff_id
             JOIN users u ON u.id=af.user_id
             WHERE u.id=? AND rc.status='approved'",
            [$referrerUserId]
        );

        return [
            'signups' => (int)$totalSignups,
            'earned'  => (float)($commStats['earned'] ?? 0),
            'total_commissions' => (int)($commStats['total'] ?? 0),
        ];
    }
}
