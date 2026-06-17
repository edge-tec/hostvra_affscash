<?php
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/PrivateOffer.php';

Auth::check('admin');
PrivateOffer::ensureTables();

$offerId = (int)Helpers::get('id');

if ($offerId <= 0) {
    Helpers::json(['status' => 'error', 'message' => 'Invalid offer ID'], 400);
}

$offer = Database::fetchOne(
    "SELECT id, name, payout_amount as payout, payout_type, status, visibility FROM offers WHERE id=? LIMIT 1",
    [$offerId]
);

if (!$offer) {
    Helpers::json(['status' => 'error', 'message' => 'Offer not found'], 404);
}

$grants = PrivateOffer::listAccess($offerId);

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

Helpers::json([
    'status' => 'success',
    'data' => [
        'offer' => $offer,
        'grants' => $grants,
        'log' => $log
    ]
]);
