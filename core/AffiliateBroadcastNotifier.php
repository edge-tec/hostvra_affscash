<?php
/**
 * AffiliateBroadcastNotifier
 *
 * Sends an email blast to every active affiliate when a new product is added
 * to the Affiliate Shop, or when a new reward is added to the Rewards module.
 *
 * Failures are swallowed per-affiliate so one bad recipient never blocks the
 * rest of the batch (or the admin's form submission).
 */
final class AffiliateBroadcastNotifier
{
    public static function notifyShopProductAdded(array $product): int
    {
        $siteName = (string)(Config::get('config', 'app.name') ?? 'AffiliateTracker');
        $appUrl   = rtrim((string)(Config::get('config', 'app.url')  ?? ''), '/');
        $productUrl = $appUrl . '/affiliate/shop?action=product&product_id=' . (int)$product['id'];

        $subject = "[$siteName] New product available in the Affiliate Shop";

        $descPlain = trim(strip_tags((string)($product['description'] ?? '')));
        if (function_exists('mb_strimwidth')) {
            $descPlain = mb_strimwidth($descPlain, 0, 300, '…');
        } elseif (strlen($descPlain) > 300) {
            $descPlain = substr($descPlain, 0, 300) . '…';
        }

        $priceLabel = number_format((int)($product['price_points'] ?? 0)) . ' points';

        return self::blast('shop_product_added', $subject, function (array $aff) use ($product, $siteName, $appUrl, $productUrl, $descPlain, $priceLabel) {
            $name = trim($aff['first_name'] . ' ' . $aff['last_name']);
            return self::renderTemplate([
                'title'    => 'New product in the Shop',
                'greeting' => 'Hi ' . htmlspecialchars($name !== '' ? $name : 'there', ENT_QUOTES, 'UTF-8') . ',',
                'lede'     => 'A new product has just been added to the Affiliate Shop. Redeem it with your earned points.',
                'main'     => '<h2 style="margin:0 0 8px;font-size:20px;color:#111827">' . htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8') . '</h2>'
                            . ($descPlain !== '' ? '<p style="margin:0 0 12px;color:#475569;line-height:1.55">' . htmlspecialchars($descPlain, ENT_QUOTES, 'UTF-8') . '</p>' : '')
                            . '<p style="margin:0;font-size:16px;font-weight:700;color:#4F46E5">' . $priceLabel . '</p>',
                'cta_url'  => $productUrl,
                'cta_text' => 'View product',
                'site'     => $siteName,
                'app_url'  => $appUrl,
            ]);
        });
    }

    public static function notifyRewardAdded(array $rule): int
    {
        $siteName = (string)(Config::get('config', 'app.name') ?? 'AffiliateTracker');
        $appUrl   = rtrim((string)(Config::get('config', 'app.url')  ?? ''), '/');
        $rewardUrl = $appUrl . '/affiliate/rewards?action=detail&reward_id=' . (int)$rule['id'];

        $subject = "[$siteName] New reward unlocked in the Rewards module";

        $descPlain = trim(strip_tags((string)($rule['description'] ?? '')));
        if (function_exists('mb_strimwidth')) {
            $descPlain = mb_strimwidth($descPlain, 0, 300, '…');
        } elseif (strlen($descPlain) > 300) {
            $descPlain = substr($descPlain, 0, 300) . '…';
        }

        $thresholdLabel = '$' . number_format((float)($rule['threshold_usd'] ?? 0), 2) . ' lifetime earnings';

        return self::blast('reward_added', $subject, function (array $aff) use ($rule, $siteName, $appUrl, $rewardUrl, $descPlain, $thresholdLabel) {
            $name = trim($aff['first_name'] . ' ' . $aff['last_name']);
            return self::renderTemplate([
                'title'    => 'New reward available',
                'greeting' => 'Hi ' . htmlspecialchars($name !== '' ? $name : 'there', ENT_QUOTES, 'UTF-8') . ',',
                'lede'     => 'A new milestone reward has just been added. Keep converting to unlock it.',
                'main'     => '<h2 style="margin:0 0 8px;font-size:20px;color:#111827">' . htmlspecialchars((string)($rule['title'] ?? 'Reward'), ENT_QUOTES, 'UTF-8') . '</h2>'
                            . ($descPlain !== '' ? '<p style="margin:0 0 12px;color:#475569;line-height:1.55">' . htmlspecialchars($descPlain, ENT_QUOTES, 'UTF-8') . '</p>' : '')
                            . '<p style="margin:0;font-size:14px;color:#64748B">Threshold: <strong style="color:#0F172A">' . $thresholdLabel . '</strong></p>',
                'cta_url'  => $rewardUrl,
                'cta_text' => 'View reward',
                'site'     => $siteName,
                'app_url'  => $appUrl,
            ]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal: load active affiliates, fan-out emails, swallow per-row errors.
    // Returns the number of successful sends.
    // ─────────────────────────────────────────────────────────────────────────
    private static function blast(string $eventType, string $subject, callable $bodyFor): int
    {
        $rows = [];
        try {
            $rows = Database::fetchAll(
                "SELECT u.email, u.first_name, u.last_name
                 FROM affiliates af
                 JOIN users u ON u.id = af.user_id
                 WHERE u.status = 'active' AND u.email <> ''"
            ) ?: [];
        } catch (\Throwable $e) {
            error_log('[AffiliateBroadcastNotifier] load affiliates: ' . $e->getMessage());
            return 0;
        }

        $sent = 0;
        foreach ($rows as $aff) {
            $email = trim((string)($aff['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            $name = trim($aff['first_name'] . ' ' . $aff['last_name']);
            try {
                $html = $bodyFor($aff);
                if (Mailer::sendRaw($email, $name, $subject, $html, $eventType)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                error_log("[AffiliateBroadcastNotifier:$eventType] $email: " . $e->getMessage());
            }
        }
        return $sent;
    }

    private static function renderTemplate(array $v): string
    {
        $title    = $v['title']    ?? '';
        $greeting = $v['greeting'] ?? '';
        $lede     = $v['lede']     ?? '';
        $main     = $v['main']     ?? '';
        $ctaUrl   = $v['cta_url']  ?? '';
        $ctaText  = $v['cta_text'] ?? 'Open';
        $site     = $v['site']     ?? '';
        $appUrl   = $v['app_url']  ?? '';

        return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#F8FAFC;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;color:#0F172A">'
             . '<table cellpadding="0" cellspacing="0" width="100%" style="background:#F8FAFC;padding:32px 16px"><tr><td align="center">'
             . '<table cellpadding="0" cellspacing="0" width="560" style="max-width:560px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #E2E8F0">'
             . '<tr><td style="background:#4F46E5;color:#fff;padding:18px 24px;font-size:14px;font-weight:700">' . htmlspecialchars($site, ENT_QUOTES, 'UTF-8') . '</td></tr>'
             . '<tr><td style="padding:28px 28px 8px;font-size:13px;color:#64748B;text-transform:uppercase;letter-spacing:.06em;font-weight:700">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</td></tr>'
             . '<tr><td style="padding:0 28px 12px;font-size:14px;color:#0F172A">' . $greeting . '</td></tr>'
             . '<tr><td style="padding:0 28px 18px;font-size:14px;color:#475569;line-height:1.6">' . htmlspecialchars($lede, ENT_QUOTES, 'UTF-8') . '</td></tr>'
             . '<tr><td style="padding:0 28px 20px">'
             . '<div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:18px">' . $main . '</div>'
             . '</td></tr>'
             . ($ctaUrl !== '' ? '<tr><td style="padding:0 28px 28px"><a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#4F46E5;color:#fff;text-decoration:none;padding:11px 22px;border-radius:8px;font-weight:700;font-size:14px">' . htmlspecialchars($ctaText, ENT_QUOTES, 'UTF-8') . '</a></td></tr>' : '')
             . '<tr><td style="padding:18px 28px 24px;border-top:1px solid #F1F5F9;font-size:12px;color:#94A3B8">'
             . 'You received this email because you have an active affiliate account at ' . htmlspecialchars($site, ENT_QUOTES, 'UTF-8') . '.'
             . ($appUrl !== '' ? ' <a href="' . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . '" style="color:#64748B">' . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . '</a>' : '')
             . '</td></tr>'
             . '</table></td></tr></table></body></html>';
    }
}
