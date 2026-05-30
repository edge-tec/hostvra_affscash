<?php
Auth::check('affiliate');

if (($_GET['action'] ?? '') === 'get_balance') {
    header('Content-Type: application/json');
    $affId = Auth::affiliateId();
    if (!$affId) {
        echo json_encode(['balance' => 0]);
        exit;
    }
    $row = Database::fetchOne("SELECT balance FROM affiliates WHERE id=?", [$affId]);
    echo json_encode(['balance' => round((float)($row['balance'] ?? 0), 2)]);
    exit;
}

// Fallback — redirect to invoices
Helpers::redirect('/affiliate/invoices');
