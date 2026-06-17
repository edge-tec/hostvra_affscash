<?php
/**
 * Admin App API — Invoices
 */
Auth::check('admin');

try {
    // Read JSON input if any
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? ($input['action'] ?? 'list');

    if ($action === 'list') {
        $invoices = Database::fetchAll(
            "SELECT inv.id, inv.invoice_number, inv.type, inv.subtotal, inv.tax_rate, inv.tax_amount, inv.total, inv.status, inv.due_date, inv.created_at, inv.period_start, inv.period_end, inv.paid_at,
                    CASE
                        WHEN inv.type='affiliate_payout'  THEN CONCAT(ua.first_name,' ',ua.last_name)
                        WHEN inv.type='manager_fee'        THEN CONCAT(um.first_name,' ',um.last_name)
                        ELSE CONCAT(ub.first_name,' ',ub.last_name)
                    END as recipient_name,
                    CASE
                        WHEN inv.type='affiliate_payout'  THEN ua.email
                        WHEN inv.type='manager_fee'        THEN um.email
                        ELSE ub.email
                    END as recipient_email
             FROM invoices inv
             LEFT JOIN affiliates af  ON af.id=inv.affiliate_id  LEFT JOIN users ua ON ua.id=af.user_id
             LEFT JOIN advertisers adv ON adv.id=inv.advertiser_id LEFT JOIN users ub ON ub.id=adv.user_id
             LEFT JOIN affiliate_managers mgr ON mgr.id=inv.manager_id LEFT JOIN users um ON um.id=mgr.user_id
             ORDER BY inv.created_at DESC"
        );

        echo json_encode(['status' => 'success', 'data' => $invoices]);
        exit;
    }

    if ($action === 'view') {
        $id = (int)($_GET['id'] ?? 0);
        $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$id]);
        if (!$invoice) {
            echo json_encode(['status' => 'error', 'message' => 'Invoice not found']);
            exit;
        }

        // Load entity info
        $entityName  = '';
        $entityEmail = '';
        if ($invoice['affiliate_id']) {
            $e = Database::fetchOne(
                "SELECT u.first_name, u.last_name, u.email FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
                [$invoice['affiliate_id']]
            );
            $entityName  = $e ? trim($e['first_name'] . ' ' . $e['last_name']) : '';
            $entityEmail = $e['email'] ?? '';
        } elseif ($invoice['advertiser_id']) {
            $e = Database::fetchOne(
                "SELECT u.first_name, u.last_name, u.email, u.company FROM advertisers adv JOIN users u ON u.id=adv.user_id WHERE adv.id=?",
                [$invoice['advertiser_id']]
            );
            $entityName  = $e ? trim($e['first_name'] . ' ' . $e['last_name']) . ($e['company'] ? ' ('.$e['company'].')' : '') : '';
            $entityEmail = $e['email'] ?? '';
        } elseif (!empty($invoice['manager_id'])) {
            $e = Database::fetchOne(
                "SELECT u.first_name, u.last_name, u.email FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.id=?",
                [$invoice['manager_id']]
            );
            $entityName  = $e ? trim($e['first_name'] . ' ' . $e['last_name']) : '';
            $entityEmail = $e['email'] ?? '';
        }
        $invoice['recipient_name'] = $entityName;
        $invoice['recipient_email'] = $entityEmail;
        $invoice['items'] = json_decode($invoice['items'] ?? '[]', true) ?: [];

        echo json_encode(['status' => 'success', 'data' => $invoice]);
        exit;
    }

    if ($action === 'update_status') {
        $id = (int)($input['invoice_id'] ?? 0);
        $newStatus = $input['status'] ?? '';
        $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$id]);
        
        if (!$invoice) {
            echo json_encode(['status' => 'error', 'message' => 'Invoice not found']);
            exit;
        }

        if (in_array($newStatus, ['draft','sent','paid','void'])) {
            $upd = ['status' => $newStatus];
            if ($newStatus === 'paid') $upd['paid_at'] = date('Y-m-d H:i:s');
            Database::update('invoices', $upd, 'id=?', [$id]);

            if ($newStatus === 'paid' && $invoice['affiliate_id']) {
                $affUser = Database::fetchOne(
                    "SELECT u.email, u.first_name, u.last_name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
                    [$invoice['affiliate_id']]
                );
                if ($affUser) {
                    Mailer::sendEvent($affUser['email'], trim($affUser['first_name'].' '.$affUser['last_name']), 'invoice_paid', [
                        'name'           => trim($affUser['first_name'].' '.$affUser['last_name']),
                        'email'          => $affUser['email'],
                        'site_name'      => Config::get('config','app.name') ?? 'AffiliateTracker',
                        'app_url'        => rtrim(Config::get('config','app.url') ?? '', '/'),
                        'invoice_number' => $invoice['invoice_number'],
                        'total'          => '$' . number_format($invoice['total'], 2),
                    ]);
                }
            }
            echo json_encode(['status' => 'success', 'message' => 'Invoice status updated']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int)($input['invoice_id'] ?? 0);
        $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$id]);
        if (!$invoice) {
            echo json_encode(['status' => 'error', 'message' => 'Invoice not found']);
            exit;
        }

        // Refund affiliate balance if it was deducted and invoice is not paid
        if ($invoice['affiliate_id'] && $invoice['balance_deducted'] && $invoice['status'] !== 'paid') {
            try {
                Database::query(
                    "UPDATE affiliates SET balance = balance + ? WHERE id = ?",
                    [$invoice['total'], $invoice['affiliate_id']]
                );
            } catch (\Throwable $e) {}
        }
        // Refund manager balance if it was deducted and invoice is not paid
        if ($invoice['manager_id'] && $invoice['balance_deducted'] && $invoice['status'] !== 'paid') {
            try {
                Database::query(
                    "UPDATE affiliate_managers SET balance = balance + ? WHERE id = ?",
                    [(float)$invoice['total'], (int)$invoice['manager_id']]
                );
            } catch (\Throwable $e) {}
        }
        // Delete PDF file if exists
        $pdfFile = BASE_PATH . '/uploads/invoices/' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $invoice['invoice_number']) . '.pdf';
        if (file_exists($pdfFile)) { @unlink($pdfFile); }
        // Delete invoice
        Database::query("DELETE FROM invoices WHERE id=?", [$id]);

        echo json_encode(['status' => 'success', 'message' => 'Invoice deleted']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} catch (\Throwable $e) {
    error_log("Admin Invoice API Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
