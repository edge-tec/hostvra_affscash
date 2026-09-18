<?php
/**
 * AdvBudget — single source of truth for the Advertiser Offer Budget system.
 *
 * Handles:
 *   - Idempotent schema migrations (called once per request from any code path
 *     that touches budgets, offers, conversions, or payment requests).
 *   - Helpers for deducting a conversion's payout from an offer's remaining
 *     budget, auto-pausing the offer when it hits zero, decrementing the
 *     advertiser's USD balance, and firing low-balance notifications/emails.
 *   - Helpers for the manual payment top-up flow (Bank / Crypto / Capitalist).
 *
 * All public methods are safe to call multiple times.
 */
final class AdvBudget
{
    /** Threshold (USD) below which we fire the low-balance warning. */
    public const LOW_BALANCE_THRESHOLD = 25.00;

    /** Static schema-ensure guard */
    private static bool $schemaReady = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }
        try {
            Database::query("ALTER TABLE `advertisers` ADD COLUMN `budget_exempt` TINYINT(1) NOT NULL DEFAULT 0");
        } catch (\Throwable $_e) {}
        self::$schemaReady = true;
    }

    /** Admin toggle — when true, budget field becomes mandatory on offer create. */
    public static function isBudgetRequired(): bool
    {
        return (string)(Config::get('config','app.budget_required') ?? '0') === '1';
    }

    /**
     * Returns true when the given advertiser has been individually exempted by
     * the admin from the global budget requirement. Exempt advertisers can
     * create offers without adding any balance, regardless of the global toggle.
     */
    public static function isBudgetExempt(int $advertiserId): bool
    {
        if ($advertiserId <= 0) return false;
        try {
            $row = Database::fetchOne(
                "SELECT budget_exempt FROM advertisers WHERE id = ?",
                [$advertiserId]
            );
            return $row && (int)$row['budget_exempt'] === 1;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Toggle the budget_exempt flag for an advertiser. Returns the new value.
     */
    public static function setBudgetExempt(int $advertiserId, bool $exempt): void
    {
        Database::update('advertisers', ['budget_exempt' => $exempt ? 1 : 0], 'id=?', [$advertiserId]);
    }

    /** Wallet-address / instructions map for the top-up methods. */
    public static function paymentMethods(): array
    {
        $cfg = Config::get('config','app.payment_methods');
        if (!is_array($cfg)) $cfg = [];

        // Per-coin crypto wallets — stored as a sub-array.
        // Backward-compat: if only the legacy 'crypto' string key exists, start with empty wallets.
        $wallets = $cfg['crypto_wallets'] ?? [];
        if (!is_array($wallets)) $wallets = [];

        // Derive "is crypto available" from whether any coin wallet is configured.
        $cryptoEnabled = array_filter($wallets, fn($w) => trim((string)$w) !== '');

        // Always expose the three required methods; admin fills in addresses.
        return [
            'bank'          => $cfg['bank']       ?? '',
            'crypto'        => !empty($cryptoEnabled) ? 'enabled' : '',
            'crypto_wallets'=> $wallets,
            'capitalist'    => $cfg['capitalist'] ?? '',
            'stripe'        => (Config::get('config', 'app.stripe_enabled') === '1') ? 'enabled' : '',
        ];
    }

    /** Convenience: remaining USD on an offer (NULL when no budget set). */
    public static function remainingFor(array $offer): ?float
    {
        if ($offer['budget_total'] === null || $offer['budget_total'] === '') return null;
        return max(0, (float)$offer['budget_total'] - (float)($offer['budget_spent'] ?? 0));
    }

    /**
     * Called from postback.php right after an approved (non-hidden, non-pending)
     * conversion is recorded. Self-sufficient — reads its own offer row.
     *
     * Deducts the conversion REVENUE (advertiser cost / "Revenue Today") from:
     *   - offers.budget_spent  (only when offer has budget_total set)
     *   - advertisers.balance  (always, when the offer has an advertiser)
     * Auto-pauses the offer if budget drained. Fires low-balance notification
     * + email when advertiser balance dips below the threshold.
     *
     * @param int   $offerId  The offer that generated the conversion.
     * @param float $revenue  The revenue amount for this conversion (advertiser cost).
     */
    public static function applyConversionDeduction(int $offerId, float $revenue): void
    {
        self::ensureSchema();
        if ($revenue <= 0 || $offerId <= 0) return;

        // Read offer fresh (we don't trust the click row to have budget columns).
        try {
            $offer = Database::fetchOne(
                "SELECT id, advertiser_id, budget_total, budget_spent, status FROM offers WHERE id = ?",
                [$offerId]
            );
        } catch (\Throwable $e) { return; }
        if (!$offer) return;

        $advertiserId = (int)($offer['advertiser_id'] ?? 0);

        // ── Decrement offer.budget_spent, then auto-pause if drained ─────
        if ($offer['budget_total'] !== null && (float)$offer['budget_total'] > 0) {
            try {
                Database::query("UPDATE offers SET budget_spent = budget_spent + ? WHERE id = ?", [$revenue, $offerId]);
                // Re-read to decide whether we just crossed zero.
                $fresh = Database::fetchOne("SELECT budget_total, budget_spent, status FROM offers WHERE id = ?", [$offerId]);
                if ($fresh && (float)$fresh['budget_spent'] >= (float)$fresh['budget_total']) {
                    if ($fresh['status'] === 'active') {
                        Database::query("UPDATE offers SET status = 'paused', budget_paused = 1 WHERE id = ? AND status = 'active'", [$offerId]);
                        if (class_exists('Cache')) {
                            Cache::invalidateOffer($offerId);
                        }
                        self::notifyOfferAutoPaused($offerId, $advertiserId);
                    }
                }
            } catch (\Throwable $e) {}
        }

        // ── Decrement advertisers.balance & Auto-Pause on Zero Balance ───
        if ($advertiserId > 0) {
            try {
                Database::query("UPDATE advertisers SET balance = balance - ? WHERE id = ?", [$revenue, $advertiserId]);
                $advRow = Database::fetchOne("SELECT a.id, a.balance, a.credit_limit, a.budget_exempt, u.id AS user_id, u.email, u.first_name, u.last_name
                                              FROM advertisers a JOIN users u ON u.id = a.user_id WHERE a.id = ?", [$advertiserId]);
                if ($advRow) {
                    $bal = (float)$advRow['balance'];
                    $creditLimit = (float)($advRow['credit_limit'] ?? 0);
                    $isExempt = (int)($advRow['budget_exempt'] ?? 0) === 1;

                    // Enterprise Wallet Protection: Auto-pause ALL active offers if funds depleted
                    if (($bal + $creditLimit) <= 0 && !$isExempt) {
                        $activeOffers = Database::fetchAll("SELECT id FROM offers WHERE advertiser_id = ? AND status = 'active'", [$advertiserId]);
                        if (!empty($activeOffers)) {
                            Database::query("UPDATE offers SET status = 'paused', budget_paused = 1 WHERE advertiser_id = ? AND status = 'active'", [$advertiserId]);
                            foreach ($activeOffers as $actOff) {
                                if (class_exists('Cache')) {
                                    Cache::invalidateOffer((int)$actOff['id']);
                                }
                            }
                        }
                        self::notifyLowBalance($advRow, 'empty');
                    } elseif ($bal < self::LOW_BALANCE_THRESHOLD) {
                        self::notifyLowBalance($advRow, 'low');
                    }
                }
            } catch (\Throwable $e) {}
        }
    }

    /** In-app + email notification to the offer's advertiser when an offer auto-pauses. */
    private static function notifyOfferAutoPaused(int $offerId, int $advertiserId): void
    {
        if ($advertiserId <= 0) return;
        try {
            $adv = Database::fetchOne(
                "SELECT u.id AS user_id, u.email, u.first_name, u.last_name, o.name AS offer_name
                 FROM advertisers a
                 JOIN users u ON u.id = a.user_id
                 JOIN offers o ON o.id = ?
                 WHERE a.id = ?",
                [$offerId, $advertiserId]
            );
            if (!$adv) return;
            Database::insert('notifications', [
                'user_id'     => (int)$adv['user_id'],
                'target_role' => null,
                'type'        => 'warning',
                'title'       => 'Offer Auto-Paused',
                'message'     => 'Offer "' . ($adv['offer_name'] ?? '') . '" reached its budget limit and was paused automatically. Top up your balance and reactivate it to keep running.',
                'link'        => '/advertiser/offers',
                'is_read'     => 0,
            ]);
        } catch (\Throwable $e) {}
    }

    /**
     * Throttled low-balance alert.
     * Sends ONE in-app + email per 6 hours per advertiser so a long burst of
     * conversions doesn't spam the same warning. The notification's `link`
     * deep-links to the top-up page.
     */
    private static function notifyLowBalance(array $advRow, string $level): void
    {
        $userId = (int)$advRow['user_id'];
        if ($userId <= 0) return;
        // Throttle: skip if a low-balance alert was emitted within the last 6h.
        try {
            $recent = Database::fetchOne(
                "SELECT id FROM notifications
                 WHERE user_id = ? AND title = 'Low Balance' AND created_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)
                 LIMIT 1",
                [$userId]
            );
            if ($recent) return;
        } catch (\Throwable $e) {}

        $msg = $level === 'empty'
            ? 'Your balance is finished. Please reload your account to continue running offers without interruption.'
            : 'Your balance is low. Please reload your account to continue running offers without interruption.';

        try {
            Database::insert('notifications', [
                'user_id'     => $userId,
                'target_role' => null,
                'type'        => 'danger',
                'title'       => 'Low Balance',
                'message'     => $msg,
                'link'        => '/advertiser/billing/top-up',
                'is_read'     => 0,
            ]);
        } catch (\Throwable $e) {}

        // Email — non-fatal if the mailer is misconfigured.
        try {
            $siteName = Config::get('config','app.name') ?? 'AffsCash';
            $appUrl   = rtrim((string)(Config::get('config','app.url') ?: ''), '/');
            $name     = trim(($advRow['first_name'] ?? '') . ' ' . ($advRow['last_name'] ?? '')) ?: 'Advertiser';
            $topUpUrl = $appUrl . '/advertiser/billing/top-up';
            $subject  = $level === 'empty'
                ? '⚠️ Your ' . $siteName . ' balance is empty'
                : '⚠️ Your ' . $siteName . ' balance is running low';
            $body = '<div style="font-family:sans-serif;max-width:560px;margin:0 auto;padding:24px;background:#fff;border:1px solid #E2E8F0;border-radius:12px">
                <div style="background:linear-gradient(135deg,#DC2626,#7F1D1D);color:#fff;padding:20px 24px;border-radius:10px;margin-bottom:18px">
                    <div style="font-size:11px;letter-spacing:.14em;text-transform:uppercase;opacity:.85">Action Required</div>
                    <h1 style="margin:6px 0 0;font-size:20px;line-height:1.3">Balance ' . ($level === 'empty' ? 'finished' : 'running low') . '</h1>
                </div>
                <p>Hi ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
                <p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>
                <p style="margin:22px 0">
                    <a href="' . htmlspecialchars($topUpUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#4F46E5;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block">Top Up Balance →</a>
                </p>
                <p style="font-size:12px;color:#94A3B8">If you have any questions, reply to this email.</p>
            </div>';
            Mailer::sendRaw((string)$advRow['email'], $name, $subject, $body, 'low_balance');
        } catch (\Throwable $e) {}
    }
}
