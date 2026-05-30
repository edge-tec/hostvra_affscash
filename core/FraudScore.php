<?php
/**
 * FraudScore — read-only helper that derives a 0–100 "Fraud Conversion
 * Score" from existing tracking data. Used by manager-facing reports to
 * surface a single risk number per affiliate / offer / conversion.
 *
 * This class NEVER writes to the database and NEVER changes tracking
 * decisions. It only reads from clicks + conversions tables that already
 * exist, blending signals into a normalised 0–100 number with a colour
 * band:
 *      0–39    low      green
 *     40–69    medium   amber
 *     70–100   high     red
 *
 * Formula (weighted blend, all inputs already present in the schema):
 *   fraud_clicks_ratio × 45 + blocked_clicks_ratio × 25
 *     + fraud_conversions_ratio × 20 + rejected_conversions_ratio × 10
 *
 * Each ratio is a value in [0,1]. Result is clamped to [0,100].
 *
 * Per-request cache stops repeat queries when the same affiliate/offer
 * appears multiple times in one page render.
 */
class FraudScore {
    public const WINDOW_DAYS = 30;

    private static array $affCache   = [];
    private static array $offerCache = [];

    /** Returns 0–100 risk score for one affiliate over WINDOW_DAYS. */
    public static function forAffiliate(int $affId, int $windowDays = self::WINDOW_DAYS): int {
        if ($affId <= 0) return 0;
        $key = $affId . ':' . $windowDays;
        if (isset(self::$affCache[$key])) return self::$affCache[$key];

        $since = date('Y-m-d 00:00:00', strtotime('-' . max(1, $windowDays) . ' days'));
        try {
            $c = Database::fetchOne(
                "SELECT COUNT(*) AS total,
                        SUM(is_fraud)                                     AS fraud_clicks,
                        SUM(CASE WHEN status='blocked' THEN 1 ELSE 0 END) AS blocked_clicks
                   FROM clicks
                  WHERE affiliate_id = ? AND clicked_at >= ?",
                [$affId, $since]
            ) ?: [];
            $v = Database::fetchOne(
                "SELECT COUNT(*) AS total,
                        SUM(is_fraud)                                       AS fraud_conv,
                        SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END)  AS rejected_conv
                   FROM conversions
                  WHERE affiliate_id = ? AND converted_at >= ?",
                [$affId, $since]
            ) ?: [];
        } catch (\Throwable $_) {
            return self::$affCache[$key] = 0;
        }

        return self::$affCache[$key] = self::blend($c, $v);
    }

    /** Returns 0–100 risk score for one offer over WINDOW_DAYS. */
    public static function forOffer(int $offerId, int $windowDays = self::WINDOW_DAYS): int {
        if ($offerId <= 0) return 0;
        $key = $offerId . ':' . $windowDays;
        if (isset(self::$offerCache[$key])) return self::$offerCache[$key];

        $since = date('Y-m-d 00:00:00', strtotime('-' . max(1, $windowDays) . ' days'));
        try {
            $c = Database::fetchOne(
                "SELECT COUNT(*) AS total,
                        SUM(is_fraud)                                     AS fraud_clicks,
                        SUM(CASE WHEN status='blocked' THEN 1 ELSE 0 END) AS blocked_clicks
                   FROM clicks
                  WHERE offer_id = ? AND clicked_at >= ?",
                [$offerId, $since]
            ) ?: [];
            $v = Database::fetchOne(
                "SELECT COUNT(*) AS total,
                        SUM(is_fraud)                                       AS fraud_conv,
                        SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END)  AS rejected_conv
                   FROM conversions
                  WHERE offer_id = ? AND converted_at >= ?",
                [$offerId, $since]
            ) ?: [];
        } catch (\Throwable $_) {
            return self::$offerCache[$key] = 0;
        }

        return self::$offerCache[$key] = self::blend($c, $v);
    }

    /**
     * Returns 0–100 risk score for a single conversion. Mirrors the
     * click-time fraud_score that already exists in the clicks table —
     * we normalise its 0–255 TINYINT range to 0–100 for display.
     */
    public static function forConversion(array $click): int {
        if (empty($click)) return 0;
        $raw = (int)($click['fraud_score'] ?? 0);
        if ($raw <= 0 && !empty($click['is_fraud'])) $raw = 80;   // fallback when score is missing
        $pct = (int)round(min(255, max(0, $raw)) / 255 * 100);
        // status=blocked / fraud bumps to at least High band
        if (($click['status'] ?? '') === 'blocked') $pct = max($pct, 90);
        elseif (!empty($click['is_fraud']))         $pct = max($pct, 70);
        return min(100, max(0, $pct));
    }

    /** Returns 'low' | 'medium' | 'high'. */
    public static function level(int $score): string {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }

    /**
     * HTML badge — used inside tables / cards. Caller is responsible
     * for placement; this method returns an inline <span>.
     */
    public static function badge(int $score, string $label = ''): string {
        $score = max(0, min(100, $score));
        $level = self::level($score);
        $palette = [
            'low'    => ['bg' => 'rgba(16,185,129,.18)', 'fg' => '#047857', 'text' => 'Low'],
            'medium' => ['bg' => 'rgba(245,158,11,.18)', 'fg' => '#92400E', 'text' => 'Medium'],
            'high'   => ['bg' => 'rgba(239,68,68,.18)',  'fg' => '#991B1B', 'text' => 'High'],
        ];
        $p = $palette[$level];
        $shown = $label !== '' ? $label : ($score . ' · ' . $p['text']);
        return sprintf(
            '<span class="fraud-score-badge fraud-score-%s" '
            . 'style="display:inline-flex;align-items:center;gap:6px;padding:2px 8px;'
            . 'border-radius:20px;background:%s;color:%s;font-size:11px;font-weight:700;'
            . 'white-space:nowrap" title="Fraud Conversion Score · last %d days">'
            . '<span style="width:6px;height:6px;border-radius:50%%;background:%s"></span>'
            . '%s</span>',
            htmlspecialchars($level, ENT_QUOTES),
            $p['bg'], $p['fg'],
            self::WINDOW_DAYS,
            $p['fg'],
            htmlspecialchars($shown, ENT_QUOTES)
        );
    }

    /**
     * Internal: blend click + conversion signal counts into a 0–100 score.
     * Each input is one of the row arrays returned by the SELECTs above.
     */
    private static function blend(array $c, array $v): int {
        $cTot  = (int)($c['total']          ?? 0);
        $cFr   = (int)($c['fraud_clicks']   ?? 0);
        $cBl   = (int)($c['blocked_clicks'] ?? 0);
        $vTot  = (int)($v['total']          ?? 0);
        $vFr   = (int)($v['fraud_conv']     ?? 0);
        $vRej  = (int)($v['rejected_conv']  ?? 0);

        // Treat zero-traffic affiliates/offers as "no signal" → low risk (0).
        if ($cTot === 0 && $vTot === 0) return 0;

        $rFraudClicks   = $cTot > 0 ? $cFr  / $cTot : 0;
        $rBlockedClicks = $cTot > 0 ? $cBl  / $cTot : 0;
        $rFraudConv     = $vTot > 0 ? $vFr  / $vTot : 0;
        $rRejectedConv  = $vTot > 0 ? $vRej / $vTot : 0;

        $score = ($rFraudClicks   * 45)
               + ($rBlockedClicks * 25)
               + ($rFraudConv     * 20)
               + ($rRejectedConv  * 10);

        return (int)round(max(0, min(100, $score)));
    }
}
