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

        foreach ($invoices as &$inv) {
            $inv['id'] = (int)$inv['id'];
            $inv['total'] = (string)$inv['total'];
        }

        Helpers::json(['status' => 'success', 'data' => $invoices]);
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
        $invoice['id'] = (int)$invoice['id'];
        $invoice['recipient_name'] = $entityName;
        $invoice['recipient_email'] = $entityEmail;
        $invoice['total'] = (string)$invoice['total'];
        $invoice['items'] = json_decode($invoice['items'] ?? '[]', true) ?: [];

        Helpers::json(['status' => 'success', 'data' => $invoice]);
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
            Helpers::json(['status' => 'success', 'message' => "Invoice marked as $newStatus"]);
            exit;
        }

        Helpers::json(['status' => 'error', 'message' => 'Invalid status'], 400);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($input['invoice_id'] ?? 0);
        $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$id]);
        
        if (!$invoice) {
            Helpers::json(['status' => 'error', 'message' => 'Invoice not found']);
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

        Helpers::json(['status' => 'success', 'message' => 'Invoice deleted successfully']);
        exit;
    }

    if ($action === 'get_create_form_data') {
        $affiliates = Database::fetchAll("SELECT af.id, CONCAT(u.first_name,' ',u.last_name,' — ',af.affiliate_code) as label FROM affiliates af JOIN users u ON u.id=af.user_id WHERE u.status='active' ORDER BY u.first_name");
        $advertisers = Database::fetchAll("SELECT adv.id, CONCAT(u.first_name,' ',u.last_name,' (',u.company,')') as label FROM advertisers adv JOIN users u ON u.id=adv.user_id WHERE u.status='active' ORDER BY u.first_name");
        $managers = Database::fetchAll("SELECT am.id, CONCAT(u.first_name,' ',u.last_name,' — ',u.email) as label FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE u.status='active' ORDER BY u.first_name");
        Helpers::json(['status' => 'success', 'data' => ['affiliates' => $affiliates, 'advertisers' => $advertisers, 'managers' => $managers]]);
        exit;
    }

    if ($action === 'get_affiliate_info') {
        $affId = (int)($_GET['affiliate_id'] ?? 0);
        $row = Database::fetchOne("SELECT balance, payment_threshold, payment_method, payment_details FROM affiliates WHERE id=?", [$affId]);
        if ($row) {
            Helpers::json(['status' => 'success', 'data' => ['balance' => (float)$row['balance'], 'threshold' => (float)($row['payment_threshold'] ?? 0), 'payment_method' => $row['payment_method'] ?? '', 'payment_details' => $row['payment_details'] ?? '']]);
        } else {
            Helpers::json(['status' => 'error', 'message' => 'Not found']);
        }
        exit;
    }

    if ($action === 'get_manager_info') {
        $mgrId = (int)($_GET['manager_id'] ?? 0);
        $row = Database::fetchOne("SELECT am.balance, am.commission_rate, u.first_name, u.last_name, u.email FROM affiliate_managers am JOIN users u ON u.id = am.user_id WHERE am.id = ?", [$mgrId]);
        if ($row) {
            Helpers::json(['status' => 'success', 'data' => ['balance' => (float)$row['balance'], 'name' => trim($row['first_name'] . ' ' . $row['last_name']), 'email' => $row['email'] ?? '']]);
        } else {
            Helpers::json(['status' => 'error', 'message' => 'Manager not found']);
        }
        exit;
    }

    if ($action === 'load_offers') {
        $affId = (int)($_GET['affiliate_id'] ?? 0);
        $from  = $_GET['from'] ?? '';
        $to    = $_GET['to'] ?? '';
        if (!$affId || !$from || !$to) {
            Helpers::json(['status' => 'error', 'message' => 'Missing parameters']);
            exit;
        }
        $rows = Database::fetchAll("SELECT o.id AS offer_id, o.name AS offer_name, COUNT(c.id) AS conversions, SUM(c.payout) AS total_payout, ROUND(SUM(c.payout) / COUNT(c.id), 4) AS avg_rate FROM conversions c JOIN offers o ON o.id = c.offer_id WHERE c.affiliate_id = ? AND c.status = 'approved' AND COALESCE(c.is_hidden, 0) = 0 AND DATE(c.converted_at) BETWEEN ? AND ? GROUP BY o.id, o.name ORDER BY total_payout DESC", [$affId, $from, $to]);
        Helpers::json(['status' => 'success', 'data' => ['offers' => $rows]]);
        exit;
    }

    if ($action === 'create') {
        $type = in_array($input['type'] ?? '', ['affiliate_payout','advertiser_billing','manager_fee']) ? $input['type'] : 'affiliate_payout';
        $entityId = (int)($input['entity_id'] ?? 0);
        $from    = $input['period_start'] ?? null;
        $to      = $input['period_end'] ?? null;
        $dueDate = !empty($input['due_date']) ? $input['due_date'] : null;
        $notes   = $input['notes'] ?? '';
        $taxRate = (float)($input['tax_rate'] ?? 0);
        $totalOverrideRaw = $input['total_override'] ?? '';
        $totalOverride = ($totalOverrideRaw !== '' && is_numeric($totalOverrideRaw)) ? (float)$totalOverrideRaw : null;
        $paymentDetailsOverride = trim($input['payment_details_override'] ?? '');

        $items = $input['items'] ?? [];
        $subtotal = 0;
        $formattedItems = [];
        foreach ($items as $item) {
            $desc = trim($item['description'] ?? '');
            if (!$desc) continue;
            $qty = (float)($item['qty'] ?? 1);
            $rate = (float)($item['rate'] ?? 0);
            $amount = round($qty * $rate, 4);
            $formattedItems[] = ['description' => $desc, 'qty' => $qty, 'rate' => $rate, 'amount' => $amount];
            $subtotal += $amount;
        }

        if (!$entityId) { Helpers::json(['status' => 'error', 'message' => 'Please select an affiliate, advertiser, or manager.']); exit; }
        if (empty($formattedItems)) { Helpers::json(['status' => 'error', 'message' => 'Add at least one line item.']); exit; }

        $taxAmount = round($subtotal * $taxRate / 100, 4);
        $total = $subtotal + $taxAmount;
        if ($totalOverride !== null && $totalOverride >= 0) { $total = $totalOverride; }

        $errors = [];
        $affRow = null;
        if ($type === 'affiliate_payout') {
            $affRow = Database::fetchOne("SELECT af.id, af.balance, af.payment_threshold, af.payment_method, af.payment_details, af.affiliate_code, u.email, u.first_name, u.last_name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?", [$entityId]);
            if (!$affRow) { $errors[] = 'Affiliate not found.'; } else {
                $balance = (float)$affRow['balance'];
                $threshold = (float)($affRow['payment_threshold'] ?? 0);
                if ($threshold > 0 && $balance < $threshold) { $errors[] = "Insufficient balance: affiliate has $" . number_format($balance, 2) . " but minimum payout threshold is $" . number_format($threshold, 2); }
                if (!$errors && $balance < $total) { $errors[] = "Insufficient balance: affiliate has $" . number_format($balance, 2) . " but invoice total is $" . number_format($total, 2); }
                if (!$errors && $from && $to) {
                    $periodHash = md5($type . '|' . $entityId . '|' . $from . '|' . $to);
                    $dupCheck = Database::fetchOne("SELECT id, invoice_number FROM invoices WHERE period_hash=? LIMIT 1", [$periodHash]);
                    if ($dupCheck) { $errors[] = 'A duplicate invoice already exists for this affiliate and period.'; }
                }
            }
        }

        $mgrRow = null;
        if ($type === 'manager_fee') {
            $mgrRow = Database::fetchOne("SELECT am.id, am.balance, u.email, u.first_name, u.last_name FROM affiliate_managers am JOIN users u ON u.id = am.user_id WHERE am.id = ?", [$entityId]);
            if (!$mgrRow) { $errors[] = 'Manager not found.'; } else {
                $mgrBalance = (float)$mgrRow['balance'];
                if ($mgrBalance < $total) { $errors[] = "Insufficient balance: manager has $" . number_format($mgrBalance, 2) . " in earned commissions but invoice total is $" . number_format($total, 2); }
                if (!$errors && $from && $to) {
                    $mgrPeriodHash = md5($type . '|' . $entityId . '|' . $from . '|' . $to);
                    $dupCheck = Database::fetchOne("SELECT id, invoice_number FROM invoices WHERE period_hash = ? LIMIT 1", [$mgrPeriodHash]);
                    if ($dupCheck) { $errors[] = 'Duplicate invoice already exists for this manager and period.'; }
                }
            }
        }

        if (!empty($errors)) { Helpers::json(['status' => 'error', 'message' => implode(' ', $errors)]); exit; }

        $invNum = 'INV-' . date('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $periodHash = ($from && $to) ? md5($type . '|' . $entityId . '|' . $from . '|' . $to) : null;

        $entityName = ''; $entityEmail = '';
        if ($type === 'affiliate_payout' && $affRow) { $entityName = trim($affRow['first_name'] . ' ' . $affRow['last_name']); $entityEmail = $affRow['email'] ?? ''; }
        elseif ($type === 'affiliate_payout') { $eu = Database::fetchOne("SELECT u.email, u.first_name, u.last_name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?", [$entityId]); $entityName = $eu ? trim($eu['first_name'] . ' ' . $eu['last_name']) : ''; $entityEmail = $eu['email'] ?? ''; }
        elseif ($type === 'advertiser_billing') { $eu = Database::fetchOne("SELECT u.email, u.first_name, u.last_name FROM advertisers adv JOIN users u ON u.id=adv.user_id WHERE adv.id=?", [$entityId]); $entityName = $eu ? trim($eu['first_name'] . ' ' . $eu['last_name']) : ''; $entityEmail = $eu['email'] ?? ''; }
        elseif ($type === 'manager_fee') { $eu = Database::fetchOne("SELECT u.email, u.first_name, u.last_name FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.id=?", [$entityId]); $entityName = $eu ? trim($eu['first_name'] . ' ' . $eu['last_name']) : ''; $entityEmail = $eu['email'] ?? ''; }

        $balanceDeducted = 0; $canCreate = true;
        if ($type === 'affiliate_payout') {
            try { Database::query("UPDATE affiliates SET balance = balance - ? WHERE id = ? AND balance >= ?", [$total, $entityId, $total]); $balanceDeducted = 1; } catch (\Throwable $e) {}
        } elseif ($type === 'manager_fee') {
            Database::begin();
            try {
                Database::query("UPDATE affiliate_managers SET balance = balance - ? WHERE id = ? AND balance >= ?", [$total, $entityId, $total]);
                $rows = (int)(Database::fetchOne("SELECT ROW_COUNT() as n")['n'] ?? 0);
                if ($rows === 0) { Database::rollback(); Helpers::json(['status' => 'error', 'message' => 'Insufficient manager balance']); exit; }
                else { $balanceDeducted = 1; }
            } catch (\Throwable $e) { Database::rollback(); Helpers::json(['status' => 'error', 'message' => 'Balance deduction failed']); exit; }
        }

        $newId = Database::insert('invoices', [
            'invoice_number' => $invNum, 'type' => $type, 'affiliate_id' => $type === 'affiliate_payout' ? $entityId : null,
            'advertiser_id' => $type === 'advertiser_billing' ? $entityId : null, 'manager_id' => $type === 'manager_fee' ? $entityId : null,
            'entity_name' => $entityName, 'entity_email' => $entityEmail, 'period_start' => $from ?: null, 'period_end' => $to ?: null,
            'items' => json_encode($formattedItems), 'subtotal' => $subtotal, 'tax_rate' => $taxRate, 'tax_amount' => $taxAmount,
            'total' => $total, 'status' => 'sent', 'notes' => $notes, 'due_date' => $dueDate, 'created_by' => Auth::id(),
            'period_hash' => $periodHash, 'balance_deducted' => $balanceDeducted
        ]);

        if ($type === 'manager_fee' && $balanceDeducted) { try { Database::commit(); } catch (\Throwable $e) {} }

        $invRow = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$newId]);
        $pdfPaymentMethod = ''; $pdfPaymentDetails = '';
        if ($type === 'affiliate_payout' && $affRow) { $pdfPaymentMethod = $affRow['payment_method'] ?? ''; $pdfPaymentDetails = $affRow['payment_details'] ?? ''; }
        if ($paymentDetailsOverride !== '') { $pdfPaymentDetails = $paymentDetailsOverride; }

        try {
            $pdfPath = InvoicePDF::generate($invRow, $formattedItems, $entityName, $entityEmail, $pdfPaymentMethod, $pdfPaymentDetails);
            if ($entityEmail) {
                $appName = Config::get('config', 'app.name') ?? 'AffiliateTracker'; $appUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');
                Mailer::sendEventWithPdf($entityEmail, $entityName, 'invoice_created', ['name' => $entityName, 'email' => $entityEmail, 'site_name' => $appName, 'app_url' => $appUrl, 'invoice_number' => $invNum, 'total' => '$' . number_format($total, 2), 'due_date' => $dueDate ?: 'N/A', 'period_start' => $from ? date('M j, Y', strtotime($from)) : '', 'period_end' => $to ? date('M j, Y', strtotime($to)) : ''], $pdfPath, $invNum . '.pdf');
            }
        } catch (\Throwable $e) {}

        Helpers::json(['status' => 'success', 'message' => "Invoice {$invNum} created successfully.", 'data' => ['invoice_id' => $newId]]);
        exit;
    }
    
    Helpers::json(['status' => 'error', 'message' => 'Invalid action'], 400);

} catch (\Throwable $e) {
    error_log("Admin Invoice API Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
