<?php
/**
 * Admin — Private Offers
 *
 * Two views:
 *   /admin/private-offers              → index (list of private offers + a
 *                                          picker to convert a public offer)
 *   /admin/private-offers?id={offerId} → manage one offer's access list
 *
 * All POST actions are CSRF-protected and require admin role.
 */
Auth::check('admin');
PrivateOffer::ensureTables();

$pageTitle = 'Private Offers';
$action    = Helpers::get('action');
$offerId   = (int)Helpers::get('id');
$adminId   = Auth::id();

// ── POST handlers ───────────────────────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $postAction = Helpers::postRaw('action');
    $postOffer  = (int)Helpers::postRaw('offer_id');

    if ($postAction === 'set_private') {
        // Convert a public offer to private (or revert).
        $makePrivate = (int)Helpers::postRaw('private') === 1;
        if ($postOffer > 0) {
            PrivateOffer::setPrivate($postOffer, $makePrivate, $adminId);
            Helpers::flash('success', $makePrivate
                ? 'Offer is now Private. Only granted affiliates can see and promote it.'
                : 'Offer is now Public again — all eligible affiliates can see it.');
        }
        Helpers::redirect($makePrivate ? '/admin/private-offers?id=' . $postOffer : '/admin/private-offers');
    }

    if ($postAction === 'grant_access' && $postOffer > 0) {
        $identifier = trim(Helpers::postRaw('affiliate_identifier'));
        $notes      = trim(Helpers::postRaw('notes'));
        if ($identifier === '') {
            Helpers::flash('error', 'Please enter an affiliate ID, affiliate code, or email.');
        } else {
            $aff = PrivateOffer::resolveAffiliate($identifier);
            if (!$aff) {
                Helpers::flash('error', 'No affiliate matched "' . Helpers::e($identifier) . '".');
                PrivateOffer::log($postOffer, null, 'deny', $adminId, 'admin', 'grant attempt with unknown identifier: ' . $identifier);
            } else {
                $ok = PrivateOffer::grant($postOffer, (int)$aff['id'], $adminId, $notes ?: null);
                if ($ok) {
                    Helpers::flash('success', 'Granted "' . Helpers::e($aff['first_name'] . ' ' . $aff['last_name']) . '" (' . Helpers::e($aff['affiliate_code']) . ') access to this offer.');

                    // Push Notification
                    try {
                        $uInfo = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$aff['id']]);
                        if ($uInfo && $uInfo['user_id']) {
                            Database::insert('notifications', [
                                'user_id' => (int)$uInfo['user_id'],
                                'target_role' => 'affiliate',
                                'title' => "Private Offer Access Granted",
                                'message' => "You have been granted access to offer #{$postOffer}.",
                                'link' => '/affiliate/offers'
                            ]);
                            require_once BASE_PATH . '/core/FirebaseMessaging.php';
                            FirebaseMessaging::sendToUser((int)$uInfo['user_id'], "Private Offer Access Granted", "You have been granted access to offer #{$postOffer}.", ['type' => 'offer', 'offer_id' => (string)$postOffer]);
                        }
                    } catch (\Throwable $e) {}

                } else {
                    Helpers::flash('error', 'Could not save the grant. Please try again.');
                }
            }
        }
        Helpers::redirect('/admin/private-offers?id=' . $postOffer);
    }

    if ($postAction === 'revoke_access' && $postOffer > 0) {
        $affId = (int)Helpers::postRaw('affiliate_id');
        if ($affId > 0) {
            $ok = PrivateOffer::revoke($postOffer, $affId, $adminId);
            Helpers::flash($ok ? 'success' : 'error',
                $ok ? 'Access revoked. The affiliate can no longer see or promote this offer.' : 'Could not revoke access.');
        }
        Helpers::redirect('/admin/private-offers?id=' . $postOffer);
    }
}

// ── Detail view: one offer's access list ────────────────────────────────────
if ($offerId > 0) {
    $offer = Database::fetchOne(
        "SELECT * FROM offers WHERE id=? AND (is_inhouse IS NULL OR is_inhouse = 0 OR 1=1) LIMIT 1",
        [$offerId]
    );
    if (!$offer) {
        Helpers::flash('error', 'Offer not found.');
        Helpers::redirect('/admin/private-offers');
    }
    $grants = PrivateOffer::listAccess($offerId);

    // Recent log entries for this offer (last 50)
    $log = [];
    try {
        $log = Database::fetchAll(
            "SELECT pol.*, CONCAT(u.first_name,' ',u.last_name) AS actor_name,
                    af.affiliate_code, CONCAT(au.first_name,' ',au.last_name) AS aff_name
             FROM private_offer_access_log pol
             LEFT JOIN users      u  ON u.id = pol.actor_id
             LEFT JOIN affiliates af ON af.id = pol.affiliate_id
             LEFT JOIN users      au ON au.id = af.user_id
             WHERE pol.offer_id = ?
             ORDER BY pol.created_at DESC
             LIMIT 50",
            [$offerId]
        ) ?: [];
    } catch (\Throwable $_) {}

    require BASE_PATH . '/views/admin/private_offers/manage.php';
    return;
}

// ── Index view: all private offers + convert-to-private picker ──────────────
$privateOffers = PrivateOffer::listPrivateOffers();

// Public offers the admin could mark as private (exclude in-house, exclude already-private)
$convertable = [];
try {
    $convertable = Database::fetchAll(
        "SELECT id, name, payout_amount, payout_type, status
         FROM offers
         WHERE COALESCE(visibility,'public') != 'private'
           AND (is_inhouse IS NULL OR is_inhouse = 0)
         ORDER BY name LIMIT 200"
    ) ?: [];
} catch (\Throwable $_) {}

// Activity log summary (last 50 entries across all offers)
$recentLog = [];
try {
    $recentLog = Database::fetchAll(
        "SELECT pol.*, o.name AS offer_name,
                CONCAT(u.first_name,' ',u.last_name) AS actor_name,
                af.affiliate_code, CONCAT(au.first_name,' ',au.last_name) AS aff_name
         FROM private_offer_access_log pol
         LEFT JOIN offers     o  ON o.id  = pol.offer_id
         LEFT JOIN users      u  ON u.id  = pol.actor_id
         LEFT JOIN affiliates af ON af.id = pol.affiliate_id
         LEFT JOIN users      au ON au.id = af.user_id
         ORDER BY pol.created_at DESC
         LIMIT 50"
    ) ?: [];
} catch (\Throwable $_) {}

require BASE_PATH . '/views/admin/private_offers/index.php';
