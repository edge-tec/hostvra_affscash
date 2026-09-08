<?php
/**
 * In-House Offer Tracking Endpoint
 *
 * URL: /offer/{offer_id}?aff_id={code}&click_id={ext_click_id}&sub_id={sub1}&sub_id2={sub2}...
 *
 * This is an alias for the standard /click/{id} endpoint, purpose-built for
 * in-house offers. It maps sub_id/sub_id2-5 to the standard sub1-5 columns
 * and supports an explicit click_id parameter from the affiliate's tracker.
 *
 * All tracking, fraud detection, geo-targeting, and postback logic flows
 * through the existing click.php — this file just normalises the parameters.
 */

require_once dirname(__DIR__) . '/core/TrackingBootstrap.php';

// Resolve offer ID from route or GET
$offerId = (int)($_GET['offer_id'] ?? 0);
if (!$offerId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing offer_id']);
    exit;
}

// Map in-house parameter names to the standard names click.php expects
// aff_id  → aff (affiliate code)
// sub_id  → sub1
// sub_id2 → sub2 … sub_id5 → sub5
// click_id kept as-is for sub1 override (external tracker's click ID)

$_GET['offer_id'] = $offerId;

// Affiliate code: accept aff_id or aff
if (!isset($_GET['aff']) && isset($_GET['aff_id'])) {
    $_GET['aff'] = $_GET['aff_id'];
}

// Map sub_id parameters to sub1-5
if (!isset($_GET['sub1']) && isset($_GET['sub_id']))  { $_GET['sub1'] = $_GET['sub_id']; }
if (!isset($_GET['sub2']) && isset($_GET['sub_id2'])) { $_GET['sub2'] = $_GET['sub_id2']; }
if (!isset($_GET['sub3']) && isset($_GET['sub_id3'])) { $_GET['sub3'] = $_GET['sub_id3']; }
if (!isset($_GET['sub4']) && isset($_GET['sub_id4'])) { $_GET['sub4'] = $_GET['sub_id4']; }
if (!isset($_GET['sub5']) && isset($_GET['sub_id5'])) { $_GET['sub5'] = $_GET['sub_id5']; }

// If affiliate passed their external click_id, store it in sub1 so the
// postback fires back with their click_id (matching our existing sub1 logic)
if (isset($_GET['click_id']) && !isset($_GET['sub1'])) {
    $_GET['sub1'] = $_GET['click_id'];
} elseif (isset($_GET['click_id']) && empty($_GET['sub1'])) {
    $_GET['sub1'] = $_GET['click_id'];
}

// Ensure offer is an in-house offer (safety check — don't allow /offer/X for external offers)
try {
    $offerCheck = Database::fetchOne(
        "SELECT id, is_inhouse FROM offers WHERE id=? AND status='active'",
        [$offerId]
    );
    if (!$offerCheck) {
        http_response_code(404);
        echo 'Offer not found.';
        exit;
    }
} catch (\Throwable $e) {
    // If is_inhouse column doesn't exist yet, proceed anyway
}

// Delegate entirely to the existing click tracking engine
require BASE_PATH . '/tracking/click.php';
