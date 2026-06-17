<?php
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/PrivateOffer.php';

Auth::check('admin');
PrivateOffer::ensureTables();

$privateOffers = PrivateOffer::listPrivateOffers();

$convertable = [];
try {
    $convertable = Database::fetchAll(
        "SELECT id, name, payout_amount as payout, payout_type, status
         FROM offers
         WHERE COALESCE(visibility,'public') != 'private'
           AND (is_inhouse IS NULL OR is_inhouse = 0)
         ORDER BY name LIMIT 200"
    ) ?: [];
} catch (\Throwable $_) {}

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

Helpers::json([
    'status' => 'success',
    'data' => [
        'privateOffers' => $privateOffers,
        'convertableOffers' => $convertable,
        'recentLog' => $recentLog
    ]
]);
