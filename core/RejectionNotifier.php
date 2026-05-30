<?php
/**
 * RejectionNotifier — instant in-app notification (and optional email) when an
 * admin rejects a conversion.
 *
 * Call RejectionNotifier::afterReject($conversionId) right after a conversion's
 * status is flipped to 'rejected' (or 'chargebacked'). The helper:
 *
 *   1. Re-reads the row to pick up the canonical rejection_reason / rejected_at
 *      that RejectionHelper::buildUpdatePayload() just persisted.
 *   2. Inserts ONE notification per rejection event (de-duped by exact link).
 *   3. Optionally fires an email if config[notifications.email_on_reject] = '1'.
 *
 * No throws — every external dependency (DB, Mailer) is wrapped so a failure
 * here can never block the admin's reject action.
 */
final class RejectionNotifier
{
    /** Centralised affiliate-facing message text. Edit here, propagates everywhere. */
    public static function buildMessage(array $conv): string
    {
        $shortId = substr((string)($conv['conversion_id'] ?? ''), 0, 8);
        $offer   = trim((string)($conv['offer_name'] ?? ''));
        $reason  = trim((string)($conv['rejection_reason'] ?? '')) ?: 'No reason provided.';

        $bits = [];
        $bits[] = 'Your conversion ' . $shortId . '… was rejected.';
        if ($offer !== '') $bits[] = 'Offer: ' . $offer . '.';
        $bits[] = 'Reason: ' . $reason;
        return implode(' ', $bits);
    }

    /** Fire the in-app notification (and optional email). Idempotent. */
    public static function afterReject(string $conversionId): void
    {
        if ($conversionId === '') return;

        $row = null;
        try {
            $row = Database::fetchOne(
                "SELECT cv.conversion_id, cv.affiliate_id, cv.offer_id,
                        cv.payout, cv.status, cv.rejection_reason, cv.rejected_at,
                        af.user_id,
                        u.email AS affiliate_email,
                        CONCAT(u.first_name,' ',u.last_name) AS affiliate_name,
                        o.name AS offer_name
                 FROM conversions cv
                 LEFT JOIN affiliates af ON af.id = cv.affiliate_id
                 LEFT JOIN users      u  ON u.id  = af.user_id
                 LEFT JOIN offers     o  ON o.id  = cv.offer_id
                 WHERE cv.conversion_id = ? LIMIT 1",
                [$conversionId]
            );
        } catch (\Throwable $e) { return; }

        if (!$row || !$row['user_id']) return;
        // Only fire when the row is actually in a rejected-style state — guards
        // against the helper being called before the UPDATE actually committed.
        if (!in_array($row['status'], ['rejected', 'chargebacked'], true)) return;

        $deepLink = '/affiliate/reports?tab=conversion&cid=' . urlencode($conversionId);

        // De-dupe: a single rejection event yields exactly one notification.
        try {
            $existing = Database::fetchOne(
                "SELECT id FROM notifications
                 WHERE user_id = ?
                   AND title   = 'Conversion Rejected'
                   AND link   LIKE ? LIMIT 1",
                [(int)$row['user_id'], '%cid=' . $conversionId]
            );
            if ($existing) return;
        } catch (\Throwable $e) {}

        $msg = self::buildMessage($row);

        try {
            Database::insert('notifications', [
                'user_id'     => (int)$row['user_id'],
                'target_role' => null,
                'type'        => 'warning',
                'title'       => 'Conversion Rejected',
                'message'     => $msg,
                'link'        => $deepLink,
                'is_read'     => 0,
            ]);
        } catch (\Throwable $e) {}

        // ── Optional email — controlled by an admin setting ────────────────
        // Default OFF so existing installs don't suddenly start emailing.
        $emailOn = (string)(Config::get('config', 'notifications.email_on_reject') ?? '0') === '1';
        if ($emailOn && !empty($row['affiliate_email']) && class_exists('Mailer')) {
            $payout  = number_format((float)($row['payout'] ?? 0), 2);
            $reason  = (string)($row['rejection_reason'] ?? '') ?: 'No reason provided.';
            $offer   = (string)($row['offer_name'] ?? '');
            $shortId = substr((string)$conversionId, 0, 8);
            $when    = (string)($row['rejected_at'] ?? date('Y-m-d H:i:s'));
            $name    = trim((string)($row['affiliate_name'] ?? '')) ?: 'there';
            $site    = (string)(Config::get('config', 'app.name') ?? 'AffiliateTracker');
            $appUrl  = rtrim((string)(Config::get('config', 'app.url') ?? ''), '/');

            $body = '<div style="font-family:sans-serif;max-width:520px;margin:0 auto">'
                  . '<h2 style="color:#DC2626">Conversion Rejected</h2>'
                  . '<p>Hi ' . htmlspecialchars($name) . ',</p>'
                  . '<p>One of your conversions on <strong>' . htmlspecialchars($site) . '</strong> has been rejected.</p>'
                  . '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:14px;margin:14px 0">'
                  .   '<tr><td style="color:#64748B">Conversion ID:</td><td><code>' . htmlspecialchars($shortId) . '…</code></td></tr>'
                  .   ($offer  !== '' ? '<tr><td style="color:#64748B">Offer:</td><td>'  . htmlspecialchars($offer)  . '</td></tr>' : '')
                  .   '<tr><td style="color:#64748B">Payout:</td><td>$' . htmlspecialchars($payout) . '</td></tr>'
                  .   '<tr><td style="color:#64748B">Rejected at:</td><td>' . htmlspecialchars($when) . '</td></tr>'
                  .   '<tr><td style="color:#64748B;vertical-align:top">Reason:</td><td><strong style="color:#991B1B">' . htmlspecialchars($reason) . '</strong></td></tr>'
                  . '</table>'
                  . ($appUrl !== '' ? '<p><a href="' . htmlspecialchars($appUrl . $deepLink) . '" style="display:inline-block;background:#4F46E5;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;font-weight:600">Open in your account</a></p>' : '')
                  . '<p style="color:#64748B;font-size:12px;margin-top:24px">If you believe this was rejected in error, please contact support.</p>'
                  . '</div>';

            try {
                Mailer::sendRaw(
                    (string)$row['affiliate_email'],
                    $name,
                    '[' . $site . '] Conversion ' . $shortId . '… was rejected',
                    $body,
                    'conversion_rejected'
                );
            } catch (\Throwable $e) {}
        }
    }
}
