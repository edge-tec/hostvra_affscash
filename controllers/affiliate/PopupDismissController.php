<?php
/**
 * Affiliate → POST /affiliate/popup/dismiss
 * Marks the current popup version as dismissed for this affiliate.
 */
Auth::check('affiliate');
header('Content-Type: application/json');

if (!Helpers::isPost() || !Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    http_response_code(400); echo json_encode(['ok' => false]); exit;
}
$version = (int)Helpers::postRaw('version');
$affId   = (int)Auth::affiliateId();
PopupService::dismiss($affId, $version);
echo json_encode(['ok' => true]);
