<?php
header('Content-Type: application/json');

Auth::check('affiliate_manager');

try {
    $affIds = Auth::managerAffiliateIds();
    $_mgrRow = Database::fetchOne("SELECT id FROM affiliate_managers WHERE user_id=?", [Auth::id()]);
    $mgrId = (int)($_mgrRow['id'] ?? 0);

    $affiliateInvoices = [];
    $totals = [
        'affiliate' => ['total_invoices' => 0, 'pending' => 0.0, 'paid' => 0.0],
        'my' => ['total_invoices' => 0, 'pending' => 0.0, 'paid' => 0.0],
        'my_balance' => 0.0
    ];

    if (!empty($affIds)) {
        $inPlaceholders = implode(',', array_fill(0, count($affIds), '?'));
        $affiliateInvoices = Database::fetchAll(
            "SELECT i.id as invoice_id, i.invoice_number, i.type, i.total, i.status, i.due_date, i.created_at, i.paid_at,
                    i.period_start, i.period_end,
                    CONCAT(u.first_name,' ',u.last_name) as entity_name,
                    af.affiliate_code as affiliate_code
             FROM invoices i
             JOIN affiliates af ON af.id=i.affiliate_id
             JOIN users u ON u.id=af.user_id
             WHERE i.affiliate_id IN ($inPlaceholders)
             ORDER BY i.created_at DESC",
            $affIds
        );
        
        foreach ($affiliateInvoices as &$inv) {
            $inv['total'] = (float)$inv['total'];
            $inv['invoice_id'] = (int)$inv['invoice_id'];
            $totals['affiliate']['total_invoices']++;
            if (in_array($inv['status'], ['sent', 'draft', 'pending'])) {
                $totals['affiliate']['pending'] += $inv['total'];
            }
            if ($inv['status'] === 'paid') {
                $totals['affiliate']['paid'] += $inv['total'];
            }
        }
    }

    $myInvoices = [];
    if ($mgrId) {
        $myInvoices = Database::fetchAll(
            "SELECT i.id as invoice_id, i.invoice_number, i.type, i.total, i.status, i.due_date, i.created_at, i.paid_at,
                    i.period_start, i.period_end,
                    'My Invoice' as entity_name
             FROM invoices i
             WHERE i.manager_id=? AND i.type='manager_fee'
             ORDER BY i.created_at DESC",
            [$mgrId]
        );

        foreach ($myInvoices as &$inv) {
            $inv['total'] = (float)$inv['total'];
            $inv['invoice_id'] = (int)$inv['invoice_id'];
            $totals['my']['total_invoices']++;
            if (in_array($inv['status'], ['sent', 'draft', 'pending'])) {
                $totals['my']['pending'] += $inv['total'];
            }
            if ($inv['status'] === 'paid') {
                $totals['my']['paid'] += $inv['total'];
            }
        }

        // Get manager balance
        require_once BASE_PATH . '/core/ManagerCommissionService.php';
        $balRow = ManagerCommissionService::getManagerBalance($mgrId);
        $totals['my_balance'] = (float)($balRow['balance'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'affiliate_invoices' => $affiliateInvoices,
        'my_invoices' => $myInvoices,
        'totals' => $totals
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
