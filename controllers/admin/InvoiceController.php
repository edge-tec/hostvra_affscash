<?php
Auth::check('admin');
$pageTitle = 'Invoices';

// Schema migrations
try { Database::query("ALTER TABLE invoices ADD COLUMN manager_id INT DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE invoices MODIFY COLUMN `type` ENUM('affiliate_payout','advertiser_billing','manager_fee') NOT NULL DEFAULT 'affiliate_payout'"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE invoices ADD COLUMN entity_name VARCHAR(255) DEFAULT ''"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE invoices ADD COLUMN entity_email VARCHAR(255) DEFAULT ''"); } catch(\Throwable $_e) {}
// Duplicate-prevention hash: unique per affiliate+period+type combination
try { Database::query("ALTER TABLE invoices ADD COLUMN period_hash VARCHAR(64) DEFAULT NULL"); } catch(\Throwable $_e) {}
try { Database::query("ALTER TABLE invoices ADD COLUMN balance_deducted TINYINT(1) DEFAULT 0"); } catch(\Throwable $_e) {}
// Manager invoice balance column
try { Database::query("ALTER TABLE affiliate_managers ADD COLUMN IF NOT EXISTS balance DECIMAL(10,4) NOT NULL DEFAULT 0.0000"); } catch(\Throwable $_e) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'view' : 'index');

// ── AJAX: load per-affiliate conversion summary for a manager ─────────────────
if ($action === 'load_manager_offers') {
    header('Content-Type: application/json');
    $mgrId = (int)Helpers::get('manager_id');
    $from  = Helpers::get('from');
    $to    = Helpers::get('to');
    if (!$mgrId || !$from || !$to) { echo json_encode(['error' => 'Missing parameters']); exit; }

    $mgr = Database::fetchOne("SELECT id FROM affiliate_managers WHERE id=?", [$mgrId]);
    if (!$mgr) { echo json_encode(['error' => 'Manager not found']); exit; }

    $rows = Database::fetchAll(
        "SELECT af.id AS offer_id,
                CONCAT(u.first_name,' ',u.last_name,' (',af.affiliate_code,')') AS offer_name,
                COUNT(c.id) AS conversions,
                SUM(c.payout) AS total_payout,
                ROUND(SUM(c.payout)/COUNT(c.id),4) AS avg_rate
         FROM affiliates af
         JOIN users u ON u.id=af.user_id
         JOIN conversions c ON c.affiliate_id=af.id
         WHERE af.manager_id=? AND c.status='approved'
           AND COALESCE(c.is_hidden, 0) = 0
           AND DATE(c.converted_at) BETWEEN ? AND ?
         GROUP BY af.id ORDER BY total_payout DESC",
        [$mgrId, $from, $to]
    );
    echo json_encode(['offers' => $rows]);
    exit;
}

// ── AJAX: edit invoice total amount (admin only) ────────────────────────────
if ($action === 'edit_total') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }
    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) { echo json_encode(['error'=>'Invalid CSRF']); exit; }
    $invId    = (int)($_POST['invoice_id'] ?? 0);
    $newTotal = (float)($_POST['new_total'] ?? 0);
    if ($invId <= 0 || $newTotal < 0) { echo json_encode(['error'=>'Invalid input']); exit; }
    $inv = Database::fetchOne("SELECT id, subtotal FROM invoices WHERE id=?", [$invId]);
    if (!$inv) { echo json_encode(['error'=>'Invoice not found']); exit; }
    Database::update('invoices', ['total' => $newTotal], 'id=?', [$invId]);
    echo json_encode(['success'=>true, 'new_total'=>$newTotal]);
    exit;
}

// ── AJAX: edit invoice payment details (admin only) ──────────────────────────
if ($action === 'edit_payment_details') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }
    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) { echo json_encode(['error'=>'Invalid CSRF']); exit; }
    $invId   = (int)($_POST['invoice_id'] ?? 0);
    $details = trim($_POST['payment_details'] ?? '');
    if ($invId <= 0) { echo json_encode(['error'=>'Invalid input']); exit; }
    // Store as updated notes or in items JSON — we add a special column if needed
    try { Database::query("ALTER TABLE invoices ADD COLUMN payment_details_override TEXT NULL"); } catch(\Throwable $e) {}
    Database::update('invoices', ['payment_details_override' => $details], 'id=?', [$invId]);
    echo json_encode(['success'=>true]);
    exit;
}

// ── AJAX: get affiliate balance/threshold/payment info ───────────────────────
if ($action === 'get_affiliate_info') {
    header('Content-Type: application/json');
    $affId = (int)Helpers::get('affiliate_id');
    if (!$affId) { echo json_encode(['error' => 'Missing affiliate_id']); exit; }
    $row = Database::fetchOne(
        "SELECT af.balance, af.payment_threshold, af.payment_method, af.payment_details
         FROM affiliates af WHERE af.id=?",
        [$affId]
    );
    if (!$row) { echo json_encode(['error' => 'Not found']); exit; }
    echo json_encode([
        'balance'         => (float)$row['balance'],
        'threshold'       => (float)($row['payment_threshold'] ?? 0),
        'payment_method'  => $row['payment_method']  ?? '',
        'payment_details' => $row['payment_details'] ?? '',
    ]);
    exit;
}

// ── AJAX: get manager balance info ───────────────────────────────────────────
if ($action === 'get_manager_info') {
    header('Content-Type: application/json');
    $mgrId = (int)Helpers::get('manager_id');
    if (!$mgrId) { echo json_encode(['error' => 'Missing manager_id']); exit; }
    $row = Database::fetchOne(
        "SELECT am.balance, am.commission_rate, u.first_name, u.last_name, u.email
         FROM affiliate_managers am JOIN users u ON u.id = am.user_id WHERE am.id = ?",
        [$mgrId]
    );
    if (!$row) { echo json_encode(['error' => 'Manager not found']); exit; }
    echo json_encode([
        'balance' => (float)$row['balance'],
        'name'    => trim($row['first_name'] . ' ' . $row['last_name']),
        'email'   => $row['email'] ?? '',
    ]);
    exit;
}

// ── AJAX: load offers with conversions for affiliate + period ─────────────────
if ($action === 'load_offers') {
    header('Content-Type: application/json');
    $affId = (int)Helpers::get('affiliate_id');
    $from  = Helpers::get('from');
    $to    = Helpers::get('to');

    if (!$affId || !$from || !$to) {
        echo json_encode(['error' => 'Missing parameters']);
        exit;
    }

    $rows = Database::fetchAll(
        "SELECT o.id AS offer_id,
                o.name AS offer_name,
                COUNT(c.id) AS conversions,
                SUM(c.payout) AS total_payout,
                ROUND(SUM(c.payout) / COUNT(c.id), 4) AS avg_rate
         FROM conversions c
         JOIN offers o ON o.id = c.offer_id
         WHERE c.affiliate_id = ?
           AND c.status = 'approved'
           AND COALESCE(c.is_hidden, 0) = 0
           AND DATE(c.converted_at) BETWEEN ? AND ?
         GROUP BY o.id, o.name
         ORDER BY total_payout DESC",
        [$affId, $from, $to]
    );

    echo json_encode(['offers' => $rows]);
    exit;
}

// ── Download PDF ──────────────────────────────────────────────────────────────
if ($action === 'download_pdf') {
    $id      = (int)Helpers::get('id');
    $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$id]);
    if (!$invoice) { http_response_code(404); exit('Invoice not found'); }

    $items = json_decode($invoice['items'] ?? '[]', true) ?: [];

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
            "SELECT u.first_name, u.last_name, u.email FROM advertisers adv JOIN users u ON u.id=adv.user_id WHERE adv.id=?",
            [$invoice['advertiser_id']]
        );
        $entityName  = $e ? trim($e['first_name'] . ' ' . $e['last_name']) : '';
        $entityEmail = $e['email'] ?? '';
    } elseif (!empty($invoice['manager_id'])) {
        $e = Database::fetchOne(
            "SELECT u.first_name, u.last_name, u.email FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.id=?",
            [$invoice['manager_id']]
        );
        $entityName  = $e ? trim($e['first_name'] . ' ' . $e['last_name']) : '';
        $entityEmail = $e['email'] ?? '';
    }

    $pdfFile = BASE_PATH . '/uploads/invoices/' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $invoice['invoice_number']) . '.pdf';

    // Regenerate if missing
    if (!file_exists($pdfFile)) {
        // Load affiliate payment details for PDF
        $dlPayMethod  = '';
        $dlPayDetails = '';
        if ($invoice['affiliate_id']) {
            $dlAff = Database::fetchOne("SELECT payment_method, payment_details FROM affiliates WHERE id=?", [$invoice['affiliate_id']]);
            $dlPayMethod  = $dlAff['payment_method']  ?? '';
            $dlPayDetails = $dlAff['payment_details'] ?? '';
        }
        $pdfFile = InvoicePDF::generate($invoice, $items, $entityName, $entityEmail, $dlPayMethod, $dlPayDetails);
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $invoice['invoice_number'] . '.pdf"');
    header('Content-Length: ' . filesize($pdfFile));
    header('Cache-Control: private, max-age=0');
    readfile($pdfFile);
    exit;
}

// ── Index ─────────────────────────────────────────────────────────────────────
if ($action === 'index') {
    $invoices = Database::fetchAll(
        "SELECT inv.*,
                CASE
                    WHEN inv.type='affiliate_payout'  THEN CONCAT(ua.first_name,' ',ua.last_name)
                    WHEN inv.type='manager_fee'        THEN CONCAT(um.first_name,' ',um.last_name)
                    ELSE CONCAT(ub.first_name,' ',ub.last_name)
                END as entity_name,
                CASE
                    WHEN inv.type='affiliate_payout'  THEN ua.email
                    WHEN inv.type='manager_fee'        THEN um.email
                    ELSE ub.email
                END as entity_email
         FROM invoices inv
         LEFT JOIN affiliates af  ON af.id=inv.affiliate_id  LEFT JOIN users ua ON ua.id=af.user_id
         LEFT JOIN advertisers adv ON adv.id=inv.advertiser_id LEFT JOIN users ub ON ub.id=adv.user_id
         LEFT JOIN affiliate_managers mgr ON mgr.id=inv.manager_id LEFT JOIN users um ON um.id=mgr.user_id
         ORDER BY inv.created_at DESC"
    );

    if (Helpers::get('export') === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="invoices_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['Invoice #','Type','Recipient','Email','Subtotal','Tax %','Tax Amt','Total','Status','Period Start','Period End','Due Date','Paid At','Created']);
        foreach ($invoices as $inv) {
            fputcsv($f, [$inv['invoice_number'],$inv['type'],$inv['entity_name'],$inv['entity_email'],$inv['subtotal'],$inv['tax_rate'],$inv['tax_amount'],$inv['total'],$inv['status'],$inv['period_start'],$inv['period_end'],$inv['due_date'],$inv['paid_at'],$inv['created_at']]);
        }
        fclose($f);
        exit;
    }

    require BASE_PATH . '/views/admin/invoices/index.php';
}

// ── Create ────────────────────────────────────────────────────────────────────
elseif ($action === 'create') {
    $errors     = [];
    $affiliates = Database::fetchAll(
        "SELECT af.id, CONCAT(u.first_name,' ',u.last_name,' — ',af.affiliate_code) as label
         FROM affiliates af JOIN users u ON u.id=af.user_id
         WHERE u.status='active' ORDER BY u.first_name"
    );
    $advertisers = Database::fetchAll(
        "SELECT adv.id, CONCAT(u.first_name,' ',u.last_name,' (',u.company,')') as label
         FROM advertisers adv JOIN users u ON u.id=adv.user_id
         WHERE u.status='active' ORDER BY u.first_name"
    );
    $affiliateManagers = Database::fetchAll(
        "SELECT am.id, CONCAT(u.first_name,' ',u.last_name,' — ',u.email) as label
         FROM affiliate_managers am JOIN users u ON u.id=am.user_id
         WHERE u.status='active' ORDER BY u.first_name"
    );

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $type = in_array(Helpers::post('type'), ['affiliate_payout','advertiser_billing','manager_fee'])
            ? Helpers::post('type') : 'affiliate_payout';
        $entityId = (int)Helpers::post('entity_id');
        $from    = Helpers::post('period_start');
        $to      = Helpers::post('period_end');
        $dueDate = Helpers::post('due_date') ?: null;
        $notes   = Helpers::post('notes');
        $taxRate = (float)Helpers::post('tax_rate');
        // Admin total override (empty string = use calculated total)
        $totalOverrideRaw      = trim(Helpers::postRaw('total_override') ?? '');
        $totalOverride         = ($totalOverrideRaw !== '' && is_numeric($totalOverrideRaw)) ? (float)$totalOverrideRaw : null;
        // Payment details override (editable on form before submission)
        $paymentDetailsOverride = trim(Helpers::postRaw('payment_details_override') ?? '');

        // Build line items from posted arrays
        $descs  = $_POST['item_desc']   ?? [];
        $qtys   = $_POST['item_qty']    ?? [];
        $rates  = $_POST['item_rate']   ?? [];
        $items  = [];
        $subtotal = 0;
        foreach ($descs as $i => $desc) {
            if (!trim($desc)) continue;
            $qty    = (float)($qtys[$i] ?? 1);
            $rate   = (float)($rates[$i] ?? 0);
            $amount = round($qty * $rate, 4);
            $items[] = [
                'description' => trim($desc),
                'qty'         => $qty,
                'rate'        => $rate,
                'amount'      => $amount,
            ];
            $subtotal += $amount;
        }

        if (!$entityId)    $errors[] = 'Please select an affiliate, advertiser, or manager.';
        if (empty($items)) $errors[] = 'Add at least one line item.';

        // ── Affiliate-specific validations ────────────────────────────────
        $affRow = null;
        if (!$errors && $type === 'affiliate_payout') {
            $affRow = Database::fetchOne(
                "SELECT af.id, af.balance, af.payment_threshold, af.payment_method, af.payment_details,
                         af.affiliate_code, u.email, u.first_name, u.last_name
                  FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
                [$entityId]
            );
            if (!$affRow) {
                $errors[] = 'Affiliate not found.';
            } else {
                $balance   = (float)$affRow['balance'];
                $threshold = (float)($affRow['payment_threshold'] ?? 0);
                $taxAmount = round($subtotal * $taxRate / 100, 4);
                $total     = $subtotal + $taxAmount;

                // Threshold check
                if ($threshold > 0 && $balance < $threshold) {
                    $errors[] = sprintf(
                        'Insufficient balance: affiliate has $%s but minimum payout threshold is $%s.',
                        number_format($balance, 2),
                        number_format($threshold, 2)
                    );
                }
                // Balance check
                if (!$errors && $balance < $total) {
                    $errors[] = sprintf(
                        'Insufficient balance: affiliate has $%s but invoice total is $%s.',
                        number_format($balance, 2),
                        number_format($total, 2)
                    );
                }
                // Duplicate invoice check — same affiliate + same period + same type
                if (!$errors && $from && $to) {
                    $periodHash = md5($type . '|' . $entityId . '|' . $from . '|' . $to);
                    $dupCheck = Database::fetchOne(
                        "SELECT id, invoice_number FROM invoices WHERE period_hash=? LIMIT 1",
                        [$periodHash]
                    );
                    if ($dupCheck) {
                        $errors[] = 'A duplicate invoice already exists for this affiliate and period: '
                                  . $dupCheck['invoice_number'] . '. Each period can only be invoiced once.';
                    }
                }
            }
        }

        // ── Manager-specific validations ──────────────────────────────────
        $mgrRow = null;
        if (!$errors && $type === 'manager_fee') {
            $mgrRow = Database::fetchOne(
                "SELECT am.id, am.balance, u.email, u.first_name, u.last_name
                 FROM affiliate_managers am JOIN users u ON u.id = am.user_id WHERE am.id = ?",
                [$entityId]
            );
            if (!$mgrRow) {
                $errors[] = 'Manager not found.';
            } else {
                $mgrBalance = (float)$mgrRow['balance'];
                $chkTax     = round($subtotal * $taxRate / 100, 4);
                $chkTotal   = ($totalOverride !== null && $totalOverride >= 0)
                              ? $totalOverride
                              : ($subtotal + $chkTax);

                // ── Insufficient balance check ─────────────────────────
                if ($mgrBalance < $chkTotal) {
                    $errors[] = sprintf(
                        'Insufficient balance: manager has $%s in earned commissions but invoice total is $%s. '
                        . 'Wait for more conversions to be approved or reduce the invoice amount.',
                        number_format($mgrBalance, 2),
                        number_format($chkTotal, 2)
                    );
                }

                // ── Duplicate invoice check ────────────────────────────
                if (!$errors && $from && $to) {
                    $mgrPeriodHash = md5($type . '|' . $entityId . '|' . $from . '|' . $to);
                    $dupCheck = Database::fetchOne(
                        "SELECT id, invoice_number FROM invoices WHERE period_hash = ? LIMIT 1",
                        [$mgrPeriodHash]
                    );
                    if ($dupCheck) {
                        $errors[] = 'Duplicate invoice: ' . $dupCheck['invoice_number']
                                  . ' already exists for this manager and period. '
                                  . 'Each period can only be invoiced once per manager.';
                    }
                }
            }
        }

        if (!$errors) {
            $taxAmount = round($subtotal * $taxRate / 100, 4);
            $total     = $subtotal + $taxAmount;
            // Admin override: replace calculated total if provided
            if ($totalOverride !== null && $totalOverride >= 0) {
                $total = $totalOverride;
            }
            $invNum    = 'INV-' . date('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $periodHash = ($from && $to) ? md5($type . '|' . $entityId . '|' . $from . '|' . $to) : null;

            // Resolve entity name/email BEFORE insert so we can store them
            $entityName  = '';
            $entityEmail = '';
            if ($type === 'affiliate_payout' && $affRow) {
                $entityName  = trim($affRow['first_name'] . ' ' . $affRow['last_name']);
                $entityEmail = $affRow['email'] ?? '';
            } elseif ($type === 'affiliate_payout') {
                $eu = Database::fetchOne(
                    "SELECT u.email, u.first_name, u.last_name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
                    [$entityId]
                );
                $entityName  = $eu ? trim($eu['first_name'] . ' ' . $eu['last_name']) : '';
                $entityEmail = $eu['email'] ?? '';
            } elseif ($type === 'advertiser_billing') {
                $eu = Database::fetchOne(
                    "SELECT u.email, u.first_name, u.last_name FROM advertisers adv JOIN users u ON u.id=adv.user_id WHERE adv.id=?",
                    [$entityId]
                );
                $entityName  = $eu ? trim($eu['first_name'] . ' ' . $eu['last_name']) : '';
                $entityEmail = $eu['email'] ?? '';
            } elseif ($type === 'manager_fee') {
                $eu = Database::fetchOne(
                    "SELECT u.email, u.first_name, u.last_name FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.id=?",
                    [$entityId]
                );
                $entityName  = $eu ? trim($eu['first_name'] . ' ' . $eu['last_name']) : '';
                $entityEmail = $eu['email'] ?? '';
            }

            // ── Deduct balance immediately on invoice creation ────────────
            $balanceDeducted = 0;
            $canCreate       = true;

            if ($type === 'affiliate_payout') {
                try {
                    Database::query(
                        "UPDATE affiliates SET balance = balance - ? WHERE id = ? AND balance >= ?",
                        [$total, $entityId, $total]
                    );
                    $balanceDeducted = 1;
                } catch (\Throwable $e) {}

            } elseif ($type === 'manager_fee') {
                // Atomic deduction inside a transaction so a concurrent request
                // cannot create a second invoice against the same balance.
                Database::begin();
                try {
                    Database::query(
                        "UPDATE affiliate_managers SET balance = balance - ? WHERE id = ? AND balance >= ?",
                        [$total, $entityId, $total]
                    );
                    $rows = (int)(Database::fetchOne("SELECT ROW_COUNT() as n")['n'] ?? 0);
                    if ($rows === 0) {
                        // Balance changed between validation and deduction (race condition).
                        Database::rollback();
                        $errors[]  = 'Insufficient manager balance (balance changed during submission). '
                                   . 'Please reload the page and try again.';
                        $canCreate = false;
                    } else {
                        $balanceDeducted = 1;
                        // Transaction stays open; committed after the invoice row is inserted.
                    }
                } catch (\Throwable $e) {
                    Database::rollback();
                    $errors[]  = 'Balance deduction failed. Please try again.';
                    $canCreate = false;
                }
            }

            if (!$canCreate) {
                // Re-render form with error — do NOT fall through to insert.
                require BASE_PATH . '/views/admin/invoices/create.php';
                exit;
            }

            $newId = Database::insert('invoices', [
                'invoice_number'  => $invNum,
                'type'            => $type,
                'affiliate_id'    => $type === 'affiliate_payout'  ? $entityId : null,
                'advertiser_id'   => $type === 'advertiser_billing' ? $entityId : null,
                'manager_id'      => $type === 'manager_fee'        ? $entityId : null,
                'entity_name'     => $entityName,
                'entity_email'    => $entityEmail,
                'period_start'    => $from ?: null,
                'period_end'      => $to   ?: null,
                'items'           => json_encode($items),
                'subtotal'        => $subtotal,
                'tax_rate'        => $taxRate,
                'tax_amount'      => $taxAmount,
                'total'           => $total,
                'status'          => 'sent',
                'notes'           => $notes,
                'due_date'        => $dueDate,
                'created_by'      => Auth::id(),
                'period_hash'     => $periodHash,
                'balance_deducted'=> $balanceDeducted,
            ]);
            $invRow = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$newId]);

            // Commit the open transaction for manager fee invoices.
            if ($type === 'manager_fee' && $balanceDeducted) {
                try { Database::commit(); } catch (\Throwable $e) {}
            }

            // ── Generate PDF & email ───────────────────────────────────────
            // Fetch affiliate payment details for PDF
            $pdfPaymentMethod  = '';
            $pdfPaymentDetails = '';
            if ($type === 'affiliate_payout' && $affRow) {
                $pdfPaymentMethod  = $affRow['payment_method']  ?? '';
                $pdfPaymentDetails = $affRow['payment_details'] ?? '';
            } elseif ($type === 'affiliate_payout') {
                $affPay = Database::fetchOne(
                    "SELECT payment_method, payment_details FROM affiliates WHERE id=?",
                    [$entityId]
                );
                $pdfPaymentMethod  = $affPay['payment_method']  ?? '';
                $pdfPaymentDetails = $affPay['payment_details'] ?? '';
            }
            // Admin may have edited payment details on the form — use that if provided
            if ($paymentDetailsOverride !== '') {
                $pdfPaymentDetails = $paymentDetailsOverride;
            }

            try {
                $pdfPath = InvoicePDF::generate($invRow, $items, $entityName, $entityEmail, $pdfPaymentMethod, $pdfPaymentDetails);

                if ($entityEmail) {
                    $appName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
                    $appUrl  = rtrim(Config::get('config', 'app.url') ?? '', '/');
                    Mailer::sendEventWithPdf(
                        $entityEmail,
                        $entityName,
                        'invoice_created',
                        [
                            'name'           => $entityName,
                            'email'          => $entityEmail,
                            'site_name'      => $appName,
                            'app_url'        => $appUrl,
                            'invoice_number' => $invNum,
                            'total'          => '$' . number_format($total, 2),
                            'due_date'       => $dueDate ?: 'N/A',
                            'period_start'   => $from ? date('M j, Y', strtotime($from)) : '',
                            'period_end'     => $to   ? date('M j, Y', strtotime($to))   : '',
                        ],
                        $pdfPath,
                        $invNum . '.pdf'
                    );
                }
            } catch (\Throwable $e) {
                // PDF/email failure is non-fatal; invoice is still created
            }

            Helpers::flash('success', "Invoice {$invNum} created and sent to {$entityEmail}.");
            Helpers::redirect('/admin/invoices/' . $newId);
        }
    }

    require BASE_PATH . '/views/admin/invoices/create.php';
}

// ── Delete ────────────────────────────────────────────────────────────────────
elseif ($action === 'delete') {
    if (!Helpers::isPost() || !Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        Helpers::flash('error', 'Invalid request.');
        Helpers::redirect('/admin/invoices');
    }
    $id      = (int)Helpers::postRaw('invoice_id');
    $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$id]);
    if (!$invoice) {
        Helpers::flash('error', 'Invoice not found.');
        Helpers::redirect('/admin/invoices');
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
    Helpers::flash('success', 'Invoice ' . $invoice['invoice_number'] . ' deleted.');
    Helpers::redirect('/admin/invoices');
}

// ── Edit ──────────────────────────────────────────────────────────────────────
elseif ($action === 'edit') {
    $id      = (int)(Helpers::get('id') ?: Helpers::postRaw('invoice_id'));
    $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$id]);
    if (!$invoice) Helpers::redirect('/admin/invoices');

    $items = json_decode($invoice['items'] ?? '[]', true) ?: [];

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $newStatus  = Helpers::post('status');
        $newDue     = Helpers::postRaw('due_date') ?: null;
        $newNotes   = Helpers::postRaw('notes');
        $newTaxRate = (float)Helpers::postRaw('tax_rate');

        // Rebuild line items
        $descs    = $_POST['item_desc']  ?? [];
        $qtys     = $_POST['item_qty']   ?? [];
        $rates    = $_POST['item_rate']  ?? [];
        $newItems = [];
        $subtotal = 0;
        foreach ($descs as $i => $desc) {
            if (!trim($desc)) continue;
            $qty    = (float)($qtys[$i] ?? 1);
            $rate   = (float)($rates[$i] ?? 0);
            $amount = round($qty * $rate, 4);
            $newItems[] = ['description' => trim($desc), 'qty' => $qty, 'rate' => $rate, 'amount' => $amount];
            $subtotal += $amount;
        }
        $taxAmount    = round($subtotal * $newTaxRate / 100, 4);
        $newTotal     = $subtotal + $taxAmount;
        $totalOverride = trim(Helpers::postRaw('total_override'));
        if ($totalOverride !== '' && is_numeric($totalOverride)) {
            $newTotal = (float)$totalOverride;
        }

        // Adjust affiliate balance if total changed and balance was deducted
        if ($invoice['affiliate_id'] && $invoice['balance_deducted'] && $invoice['status'] !== 'paid') {
            $diff = $newTotal - (float)$invoice['total'];
            if ($diff != 0) {
                try {
                    Database::query(
                        "UPDATE affiliates SET balance = balance - ? WHERE id = ?",
                        [$diff, $invoice['affiliate_id']]
                    );
                } catch (\Throwable $e) {}
            }
        }
        // Adjust manager balance if total changed and balance was deducted
        if ($invoice['manager_id'] && $invoice['balance_deducted'] && $invoice['status'] !== 'paid') {
            $diff = $newTotal - (float)$invoice['total'];
            if ($diff != 0) {
                try {
                    // Positive diff means invoice got larger → deduct more from balance
                    // Negative diff means invoice got smaller → refund the difference
                    Database::query(
                        "UPDATE affiliate_managers SET balance = GREATEST(0, balance - ?) WHERE id = ?",
                        [$diff, (int)$invoice['manager_id']]
                    );
                } catch (\Throwable $e) {}
            }
        }

        // Handle status change to paid
        $upd = [
            'status'     => in_array($newStatus, ['draft','sent','paid','void']) ? $newStatus : $invoice['status'],
            'due_date'   => $newDue,
            'notes'      => $newNotes,
            'tax_rate'   => $newTaxRate,
            'tax_amount' => $taxAmount,
            'subtotal'   => $subtotal,
            'total'      => $newTotal,
            'items'      => json_encode($newItems),
        ];
        if ($newStatus === 'paid' && $invoice['status'] !== 'paid') {
            $upd['paid_at'] = date('Y-m-d H:i:s');
        }
        Database::update('invoices', $upd, 'id=?', [$id]);

        // Regenerate PDF
        try {
            $invRow = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$id]);
            $entityName  = $invoice['entity_name']  ?? '';
            $entityEmail = $invoice['entity_email'] ?? '';
            $payMethod   = '';
            $payDetails  = $invoice['payment_details_override'] ?? '';
            if ($invoice['affiliate_id']) {
                $affPay = Database::fetchOne("SELECT payment_method, payment_details FROM affiliates WHERE id=?", [$invoice['affiliate_id']]);
                $payMethod  = $affPay['payment_method']  ?? '';
                if (!$payDetails) $payDetails = $affPay['payment_details'] ?? '';
            }
            // Delete old PDF so it gets regenerated on next download
            $pdfFile = BASE_PATH . '/uploads/invoices/' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $invoice['invoice_number']) . '.pdf';
            if (file_exists($pdfFile)) { @unlink($pdfFile); }
            InvoicePDF::generate($invRow, $newItems, $entityName, $entityEmail, $payMethod, $payDetails);
        } catch (\Throwable $e) {}

        Helpers::flash('success', 'Invoice updated successfully.');
        Helpers::redirect('/admin/invoices/' . $id);
    }

    require BASE_PATH . '/views/admin/invoices/edit.php';
}

// ── View ──────────────────────────────────────────────────────────────────────
elseif ($action === 'view') {
    $id = (int)$_GET['id'];
    $invoice = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$id]);
    if (!$invoice) Helpers::redirect('/admin/invoices');

    // Handle status change
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $newStatus = Helpers::post('status');
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

            Helpers::flash('success', 'Invoice status updated.');
            Helpers::redirect('/admin/invoices/' . $id);
        }
    }

    // Load entity info
    $entityName  = '';
    $entityEmail = '';
    $entityExtra = [];
    if ($invoice['affiliate_id']) {
        $e = Database::fetchOne(
            "SELECT u.first_name, u.last_name, u.email, u.company, u.phone,
                    af.affiliate_code, af.payment_method, af.payment_details
             FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
            [$invoice['affiliate_id']]
        );
        $entityName  = $e ? trim($e['first_name'] . ' ' . $e['last_name']) : '';
        $entityEmail = $e['email'] ?? '';
        $entityExtra = $e ?? [];
    } elseif ($invoice['advertiser_id']) {
        $e = Database::fetchOne(
            "SELECT u.first_name, u.last_name, u.email, u.company
             FROM advertisers adv JOIN users u ON u.id=adv.user_id WHERE adv.id=?",
            [$invoice['advertiser_id']]
        );
        $entityName  = $e ? trim($e['first_name'] . ' ' . $e['last_name']) . ($e['company'] ? ' ('.$e['company'].')' : '') : '';
        $entityEmail = $e['email'] ?? '';
        $entityExtra = $e ?? [];
    } elseif (!empty($invoice['manager_id'])) {
        $e = Database::fetchOne(
            "SELECT u.first_name, u.last_name, u.email
             FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.id=?",
            [$invoice['manager_id']]
        );
        $entityName  = $e ? trim($e['first_name'] . ' ' . $e['last_name']) : '';
        $entityEmail = $e['email'] ?? '';
        $entityExtra = $e ?? [];
    }
    $items = json_decode($invoice['items'] ?? '[]', true) ?: [];

    require BASE_PATH . '/views/admin/invoices/view.php';
}
