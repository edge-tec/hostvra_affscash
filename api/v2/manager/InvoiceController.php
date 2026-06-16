<?php
header('Content-Type: application/json');

Auth::check('affiliate_manager');

try {
    $affIds = Auth::managerAffiliateIds();
    $_mgrRow = Database::fetchOne("SELECT id FROM affiliate_managers WHERE user_id=?", [Auth::id()]);
    $mgrId = (int)($_mgrRow['id'] ?? 0);

    $affiliateInvoices = [];
    if (!empty($affIds)) {
        $inPlaceholders = implode(',', array_fill(0, count($affIds), '?'));
        $affiliateInvoices = Database::fetchAll(
            "SELECT i.id as invoice_id, i.invoice_number, i.type, i.total, i.status, i.due_date, i.created_at,
                    CONCAT(u.first_name,' ',u.last_name) as entity_name
             FROM invoices i
             JOIN affiliates af ON af.id=i.affiliate_id
             JOIN users u ON u.id=af.user_id
             WHERE i.affiliate_id IN ($inPlaceholders)
             ORDER BY i.created_at DESC",
            $affIds
        );
    }

    $myInvoices = [];
    if ($mgrId) {
        $myInvoices = Database::fetchAll(
            "SELECT i.id as invoice_id, i.invoice_number, i.type, i.total, i.status, i.due_date, i.created_at,
                    'My Invoice' as entity_name
             FROM invoices i
             WHERE i.manager_id=? AND i.type='manager_fee'
             ORDER BY i.created_at DESC",
            [$mgrId]
        );
    }

    $invoices = array_merge($affiliateInvoices, $myInvoices);
    usort($invoices, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    foreach ($invoices as &$inv) {
        $inv['total'] = (float)$inv['total'];
        $inv['invoice_id'] = (int)$inv['invoice_id'];
    }

    echo json_encode([
        'success' => true,
        'data' => $invoices
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
