<?php
/**
 * Impression Pixel + Conversion Pixel
 * Impression: /pixel?type=imp&offer_id=X&aff=AFFCODE
 * Conversion: /pixel?click_id=X&payout=Y   (legacy - delegates to postback)
 */

// Output 1x1 GIF immediately (non-blocking)
if (!headers_sent()) {
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

// Handle impression tracking
if (($_GET['type'] ?? '') === 'imp') {
    $offerId = (int)($_GET['offer_id'] ?? 0);
    $affCode = $_GET['aff_id'] ?? ($_GET['aff'] ?? '');

    if ($offerId && $affCode) {
        $affiliate = Database::fetchOne(
            "SELECT af.id FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.affiliate_code=? AND u.status='active'",
            [$affCode]
        );
        if ($affiliate) {
            Database::upsertStats(date('Y-m-d'), $affiliate['id'], $offerId, ['impressions' => 1]);
        }
    }
    exit;
}

// Legacy conversion pixel — delegate to postback handler
require __DIR__ . '/postback.php';
