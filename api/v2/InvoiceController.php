<?php
header('Content-Type: application/json');

Auth::check('affiliate');

$affId = Auth::affiliateId();
$action = $_GET['action'] ?? 'list';
$appUrl = Config::get('config', 'app.url') ?? '';
$appUrl = rtrim($appUrl, '/');

if ($action === 'list') {
    try {
        $invoices = Database::fetchAll(
            "SELECT id as invoice_id, invoice_number, type, total, status, due_date, created_at, period_start, period_end
             FROM invoices
             WHERE affiliate_id=?
             ORDER BY created_at DESC",
            [$affId]
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
    exit;
}

if ($action === 'download_pdf') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Invoice ID required']);
        exit;
    }

    try {
        $invoice = Database::fetchOne(
            "SELECT * FROM invoices WHERE id=? AND affiliate_id=?",
            [$id, $affId]
        );
        
        if (!$invoice) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Invoice not found']);
            exit;
        }

        $items = json_decode($invoice['items'] ?? '[]', true) ?: [];
        $aff   = Database::fetchOne(
            "SELECT u.first_name, u.last_name, u.email FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
            [$affId]
        );
        
        $entityName  = $aff ? trim($aff['first_name'] . ' ' . $aff['last_name']) : '';
        $entityEmail = $aff['email'] ?? '';

        $fileName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $invoice['invoice_number']) . '.pdf';
        $pdfFile = BASE_PATH . '/uploads/invoices/' . $fileName;

        if (!file_exists($pdfFile)) {
            $pdfFile = InvoicePDF::generate($invoice, $items, $entityName, $entityEmail);
        }

        $pdfUrl = $appUrl . '/uploads/invoices/' . $fileName;

        echo json_encode([
            'success' => true,
            'url' => $pdfUrl
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
