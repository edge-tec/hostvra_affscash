<?php
header('Content-Type: application/json');

if (!Auth::check('admin', false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $invoices = Database::fetchAll(
        "SELECT inv.id as invoice_id, inv.invoice_number, inv.type, inv.total, inv.status, inv.due_date, inv.created_at,
                CASE
                    WHEN inv.type='affiliate_payout'  THEN CONCAT(ua.first_name,' ',ua.last_name)
                    WHEN inv.type='manager_fee'        THEN CONCAT(um.first_name,' ',um.last_name)
                    ELSE CONCAT(ub.first_name,' ',ub.last_name)
                END as entity_name
         FROM invoices inv
         LEFT JOIN affiliates af  ON af.id=inv.affiliate_id  LEFT JOIN users ua ON ua.id=af.user_id
         LEFT JOIN advertisers adv ON adv.id=inv.advertiser_id LEFT JOIN users ub ON ub.id=adv.user_id
         LEFT JOIN affiliate_managers mgr ON mgr.id=inv.manager_id LEFT JOIN users um ON um.id=mgr.user_id
         ORDER BY inv.created_at DESC"
    );

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
