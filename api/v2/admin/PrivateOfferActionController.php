<?php
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/PrivateOffer.php';

Auth::requireRole('admin');
PrivateOffer::ensureTables();

$data = Helpers::getJsonPayload();
$action = $data['action'] ?? '';
$offerId = (int)($data['offer_id'] ?? 0);
$adminId = Auth::id();

if (!$action || $offerId <= 0) {
    Helpers::jsonResponse(['status' => 'error', 'message' => 'Action and offer_id are required'], 400);
}

if ($action === 'set_private') {
    $makePrivate = !empty($data['private']) && $data['private'] == 1;
    PrivateOffer::setPrivate($offerId, $makePrivate, $adminId);
    $msg = $makePrivate 
        ? 'Offer is now Private. Only granted affiliates can see and promote it.' 
        : 'Offer is now Public again — all eligible affiliates can see it.';
    Helpers::jsonResponse(['status' => 'success', 'message' => $msg]);
}

if ($action === 'grant_access') {
    $identifier = trim($data['affiliate_identifier'] ?? '');
    $notes = trim($data['notes'] ?? '');
    
    if ($identifier === '') {
        Helpers::jsonResponse(['status' => 'error', 'message' => 'Please enter an affiliate ID, affiliate code, or email.'], 400);
    }
    
    $aff = PrivateOffer::resolveAffiliate($identifier);
    if (!$aff) {
        PrivateOffer::log($offerId, null, 'deny', $adminId, 'admin', 'grant attempt with unknown identifier: ' . $identifier);
        Helpers::jsonResponse(['status' => 'error', 'message' => 'No affiliate matched "' . $identifier . '".'], 404);
    }
    
    $ok = PrivateOffer::grant($offerId, (int)$aff['id'], $adminId, $notes ?: null);
    if ($ok) {
        Helpers::jsonResponse(['status' => 'success', 'message' => 'Granted access successfully']);
    } else {
        Helpers::jsonResponse(['status' => 'error', 'message' => 'Could not save the grant. Please try again.'], 500);
    }
}

if ($action === 'revoke_access') {
    $affId = (int)($data['affiliate_id'] ?? 0);
    if ($affId > 0) {
        $ok = PrivateOffer::revoke($offerId, $affId, $adminId);
        if ($ok) {
            Helpers::jsonResponse(['status' => 'success', 'message' => 'Access revoked']);
        } else {
            Helpers::jsonResponse(['status' => 'error', 'message' => 'Could not revoke access'], 500);
        }
    } else {
        Helpers::jsonResponse(['status' => 'error', 'message' => 'Invalid affiliate ID'], 400);
    }
}

Helpers::jsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
