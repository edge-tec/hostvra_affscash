<?php
Auth::check('affiliate');
$pageTitle = 'My Invoices';

$affId = Auth::affiliateId();
if (!$affId) Helpers::redirect('/affiliate/dashboard');

$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action    = Helpers::get('action') ?? '';

// ── Download PDF ──────────────────────────────────────────────────────────────
if ($action === 'download_pdf') {
    $id      = (int)Helpers::get('id');
    $invoice = Database::fetchOne(
        "SELECT * FROM invoices WHERE id=? AND affiliate_id=?",
        [$id, $affId]
    );
    if (!$invoice) { http_response_code(404); exit('Invoice not found'); }

    $items = json_decode($invoice['items'] ?? '[]', true) ?: [];
    $aff   = Database::fetchOne(
        "SELECT u.first_name, u.last_name, u.email FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
        [$affId]
    );
    $entityName  = $aff ? trim($aff['first_name'] . ' ' . $aff['last_name']) : '';
    $entityEmail = $aff['email'] ?? '';

    $pdfFile = BASE_PATH . '/uploads/invoices/' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $invoice['invoice_number']) . '.pdf';

    if (!file_exists($pdfFile)) {
        $pdfFile = InvoicePDF::generate($invoice, $items, $entityName, $entityEmail);
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $invoice['invoice_number'] . '.pdf"');
    header('Content-Length: ' . filesize($pdfFile));
    header('Cache-Control: private, max-age=0');
    readfile($pdfFile);
    exit;
}

// ── Single invoice view ───────────────────────────────────────────────────────
if ($invoiceId) {
    $invoice = Database::fetchOne(
        "SELECT * FROM invoices WHERE id=? AND affiliate_id=?",
        [$invoiceId, $affId]
    );
    if (!$invoice) Helpers::redirect('/affiliate/invoices');
    $items    = json_decode($invoice['items'] ?? '[]', true) ?: [];
    $pageTitle = 'Invoice ' . $invoice['invoice_number'];
    $aff      = Database::fetchOne(
        "SELECT u.first_name, u.last_name, u.email, u.company FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
        [$affId]
    );
    $appName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
    require BASE_PATH . '/views/affiliate/invoice_view.php';
} else {
    // Invoice list
    $invoices = Database::fetchAll(
        "SELECT * FROM invoices WHERE affiliate_id=? ORDER BY created_at DESC",
        [$affId]
    );
    require BASE_PATH . '/views/affiliate/invoices.php';
}
