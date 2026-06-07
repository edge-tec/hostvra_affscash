<?php
Auth::check('affiliate_manager');
// Base page permission: managers can view invoices + submit requests by default.
// The elevated actions (generate, mark paid) are gated further below where they fire.
ManagerPermissions::requirePermission('create_invoice_request');
$pageTitle = 'Invoices';

// Ensure manager_invoice_requests table exists (self-healing).
try {
    Database::query("CREATE TABLE IF NOT EXISTS `manager_invoice_requests` (
        `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `manager_id`   INT UNSIGNED NOT NULL,
        `affiliate_id` INT UNSIGNED NULL DEFAULT NULL,
        `amount`       DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
        `period_start` DATE NOT NULL,
        `period_end`   DATE NOT NULL,
        `notes`        TEXT NULL,
        `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `admin_note`   TEXT NULL,
        `reviewed_by`  INT UNSIGNED NULL,
        `reviewed_at`  DATETIME NULL,
        `invoice_id`   INT UNSIGNED NULL,
        `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_manager_id` (`manager_id`),
        INDEX `idx_status`     (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\Throwable $_e) {}
// Self-healing: add affiliate_id column if missing (from migration 0015).
try { Database::query("ALTER TABLE `manager_invoice_requests` ADD COLUMN `affiliate_id` INT UNSIGNED NULL DEFAULT NULL AFTER `manager_id`"); } catch (\Throwable $_e) {}

$affIds = Auth::managerAffiliateIds();
$inSql  = empty($affIds) ? '0' : implode(',', array_fill(0, count($affIds), '?'));
$action = Helpers::get('action') ?: 'index';
$tab    = Helpers::get('tab') ?: 'affiliates';

// Resolve this manager's record ID
$_mgrRow      = Database::fetchOne("SELECT id, COALESCE(hide_earnings,0) as hide_earnings FROM affiliate_managers WHERE user_id=?", [Auth::id()]);
$mgrId        = (int)($_mgrRow['id'] ?? 0);
$mgrHideEarnings = (int)($_mgrRow['hide_earnings'] ?? 0);

require_once BASE_PATH . '/core/ManagerCommissionService.php';

// ─── List invoices (tabbed: affiliate invoices + my own invoices) ──────────────
if ($action === 'index') {
    // Tab: invoices this manager created for affiliates
    $invoices = empty($affIds) ? [] : Database::fetchAll(
        "SELECT i.*, CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code
         FROM invoices i
         JOIN affiliates af ON af.id=i.affiliate_id
         JOIN users u ON u.id=af.user_id
         WHERE i.affiliate_id IN ($inSql)
         ORDER BY i.created_at DESC",
        $affIds
    );
    $totalPending = array_sum(array_map(fn($i) => $i['status'] === 'sent'  ? (float)($i['total'] ?? 0) : 0, $invoices));
    $totalPaid    = array_sum(array_map(fn($i) => $i['status'] === 'paid'  ? (float)($i['total'] ?? 0) : 0, $invoices));

    // Tab: invoices the admin generated for this manager
    $myInvoices = [];
    $myPending  = 0.0;
    $myPaid     = 0.0;
    if ($mgrId) {
        try {
            $myInvoices = Database::fetchAll(
                "SELECT i.*, CONCAT(u.first_name,' ',u.last_name) as created_by_name
                 FROM invoices i
                 LEFT JOIN users u ON u.id=i.created_by
                 WHERE i.manager_id=? AND i.type='manager_fee'
                 ORDER BY i.created_at DESC",
                [$mgrId]
            );
            $myPending = array_sum(array_map(fn($i) => in_array($i['status'], ['sent','draft']) ? (float)($i['total'] ?? 0) : 0, $myInvoices));
            $myPaid    = array_sum(array_map(fn($i) => $i['status'] === 'paid' ? (float)($i['total'] ?? 0) : 0, $myInvoices));
        } catch (\Throwable $e) {}
    }

    require BASE_PATH . '/views/affiliate_manager/invoices.php';
}

// ─── Load offers with conversions for affiliate + period (AJAX) ───────────────
elseif ($action === 'load_offers') {
    header('Content-Type: application/json');
    $affId = (int)Helpers::get('affiliate_id');
    $from  = Helpers::get('from');
    $to    = Helpers::get('to');

    if (!$affId || !$from || !$to || (!empty($affIds) && !in_array($affId, $affIds))) {
        echo json_encode(['error' => 'Invalid parameters']); exit;
    }

    $rows = Database::fetchAll(
        "SELECT o.id AS offer_id, o.name AS offer_name,
                COUNT(c.id) AS conversions,
                SUM(c.payout) AS total_payout,
                ROUND(SUM(c.payout)/COUNT(c.id), 4) AS avg_rate
         FROM conversions c JOIN offers o ON o.id=c.offer_id
         WHERE c.affiliate_id=? AND c.status='approved'
           AND COALESCE(c.is_hidden, 0) = 0
           AND DATE(c.created_at) BETWEEN ? AND ?
         GROUP BY o.id, o.name ORDER BY total_payout DESC",
        [$affId, $from, $to]
    );
    echo json_encode(['offers' => $rows]); exit;
}

// ─── Create invoice ───────────────────────────────────────────────────────────
elseif ($action === 'create') {
    if (!Auth::hasPermission('create_invoices')) {
        Helpers::flash('error', 'You do not have permission to generate invoices. Please contact an administrator.');
        Helpers::redirect('/affiliate_manager/invoices');
    }

    if (empty($affIds)) {
        Helpers::flash('error', 'You have no managed affiliates.');
        Helpers::redirect('/affiliate_manager/invoices');
    }

    $managedAffiliates = Database::fetchAll(
        "SELECT af.id, CONCAT(u.first_name,' ',u.last_name,' — ',af.affiliate_code) as label
         FROM affiliates af JOIN users u ON u.id=af.user_id
         WHERE af.id IN ($inSql) AND u.status='active' ORDER BY u.first_name",
        $affIds
    );

    $errors = [];

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $entityId = (int)Helpers::post('entity_id');
        $from     = Helpers::post('period_start');
        $to       = Helpers::post('period_end');
        $dueDate  = Helpers::post('due_date') ?: null;
        $notes    = Helpers::post('notes');
        $taxRate  = (float)Helpers::postRaw('tax_rate');

        // Verify the selected affiliate is managed by this manager
        if (!in_array($entityId, $affIds)) {
            $errors[] = 'You can only create invoices for your managed affiliates.';
        }

        // Build line items
        $descs    = $_POST['item_desc']  ?? [];
        $qtys     = $_POST['item_qty']   ?? [];
        $rates    = $_POST['item_rate']  ?? [];
        $items    = [];
        $subtotal = 0;
        foreach ($descs as $i => $desc) {
            if (!trim($desc)) continue;
            $qty    = (float)($qtys[$i] ?? 1);
            $rate   = (float)($rates[$i] ?? 0);
            $amount = round($qty * $rate, 4);
            $items[] = ['description' => trim($desc), 'qty' => $qty, 'rate' => $rate, 'amount' => $amount];
            $subtotal += $amount;
        }

        if (!$entityId)    $errors[] = 'Please select an affiliate.';
        if (empty($items)) $errors[] = 'Add at least one line item.';

        if (!$errors) {
            $taxAmount = round($subtotal * $taxRate / 100, 4);
            $total     = $subtotal + $taxAmount;
            $invNum    = 'INV-' . date('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $newId = Database::insert('invoices', [
                'invoice_number' => $invNum,
                'type'           => 'affiliate_payout',
                'affiliate_id'   => $entityId,
                'advertiser_id'  => null,
                'period_start'   => $from ?: null,
                'period_end'     => $to   ?: null,
                'items'          => json_encode($items),
                'subtotal'       => $subtotal,
                'tax_rate'       => $taxRate,
                'tax_amount'     => $taxAmount,
                'total'          => $total,
                'status'         => 'sent',
                'notes'          => $notes,
                'due_date'       => $dueDate,
                'created_by'     => Auth::id(),
            ]);

            $invRow = Database::fetchOne("SELECT * FROM invoices WHERE id=?", [$newId]);

            // Generate PDF & email
            $affUser = Database::fetchOne(
                "SELECT u.email, u.first_name, u.last_name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
                [$entityId]
            );
            $entityName  = $affUser ? trim($affUser['first_name'] . ' ' . $affUser['last_name']) : '';
            $entityEmail = $affUser['email'] ?? '';

            try {
                $pdfPath = InvoicePDF::generate($invRow, $items, $entityName, $entityEmail);
                if ($entityEmail) {
                    $appName = Config::get('config', 'app.name') ?? 'AffiliateTracker';
                    $appUrl  = rtrim(Config::get('config', 'app.url') ?? '', '/');
                    Mailer::sendEventWithPdf(
                        $entityEmail, $entityName, 'invoice_created',
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
                        $pdfPath, $invNum . '.pdf'
                    );
                }
            } catch (\Throwable $e) {}

            Helpers::flash('success', "Invoice {$invNum} created and sent to {$entityEmail}.");
            Helpers::redirect('/affiliate_manager/invoices');
        }
    }

    require BASE_PATH . '/views/affiliate_manager/invoice_create.php';
}

// ─── View single invoice ──────────────────────────────────────────────────────
elseif ($action === 'view') {
    $id = (int)Helpers::get('id');
    $invoice = $id ? Database::fetchOne(
        "SELECT i.*, CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code, u.email as aff_email
         FROM invoices i
         JOIN affiliates af ON af.id=i.affiliate_id
         JOIN users u ON u.id=af.user_id
         WHERE i.id=? AND i.affiliate_id IN ($inSql)",
        array_merge([$id], $affIds)
    ) : null;

    if (!$invoice) {
        Helpers::flash('error', 'Invoice not found.');
        Helpers::redirect('/affiliate_manager/invoices');
    }

    require BASE_PATH . '/views/affiliate_manager/invoice_view.php';
}

// ─── Download PDF ──────────────────────────────────────────────────────────────
elseif ($action === 'download_pdf') {
    $id = (int)Helpers::get('id');
    $invoice = $id ? Database::fetchOne(
        "SELECT i.* FROM invoices i WHERE i.id=? AND i.affiliate_id IN ($inSql)",
        array_merge([$id], $affIds)
    ) : null;
    if (!$invoice) { http_response_code(404); exit('Invoice not found'); }

    $items   = json_decode($invoice['items'] ?? '[]', true) ?: [];
    $affUser = Database::fetchOne(
        "SELECT u.first_name, u.last_name, u.email FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
        [$invoice['affiliate_id']]
    );
    $entityName  = $affUser ? trim($affUser['first_name'].' '.$affUser['last_name']) : '';
    $entityEmail = $affUser['email'] ?? '';

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

// ─── Mark as paid ─────────────────────────────────────────────────────────────
elseif ($action === 'mark_paid' && Helpers::isPost()) {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        Helpers::flash('error', 'Invalid CSRF token.');
        Helpers::redirect('/affiliate_manager/invoices');
    }
    $id = (int)Helpers::postRaw('id');
    $invoice = $id ? Database::fetchOne("SELECT * FROM invoices WHERE id=? AND affiliate_id IN ($inSql)", array_merge([$id], $affIds)) : null;
    if ($invoice && in_array($invoice['status'], ['sent','draft'])) {
        Database::update('invoices', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], 'id=?', [$id]);
        Helpers::flash('success', 'Invoice marked as paid.');
    }
    Helpers::redirect('/affiliate_manager/invoices');
}

// ─── View my own invoice (admin-generated for this manager) ───────────────────
elseif ($action === 'my_invoice_view') {
    $id = (int)Helpers::get('id');
    $invoice = ($id && $mgrId) ? Database::fetchOne(
        "SELECT i.*, CONCAT(u.first_name,' ',u.last_name) as created_by_name
         FROM invoices i LEFT JOIN users u ON u.id=i.created_by
         WHERE i.id=? AND i.manager_id=? AND i.type='manager_fee'",
        [$id, $mgrId]
    ) : null;
    if (!$invoice) {
        Helpers::flash('error', 'Invoice not found.');
        Helpers::redirect('/affiliate_manager/invoices?tab=my_invoices');
    }
    $items        = json_decode($invoice['items'] ?? '[]', true) ?: [];
    $mgrUser      = Database::fetchOne("SELECT u.first_name, u.last_name, u.email, u.company FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.id=?", [$mgrId]);
    $entityName   = $mgrUser ? trim($mgrUser['first_name'].' '.$mgrUser['last_name']) : '';
    $entityEmail  = $mgrUser['email'] ?? '';
    $entityCompany= $mgrUser['company'] ?? '';
    // Reuse the invoice_view layout (it's generic enough)
    require BASE_PATH . '/views/affiliate_manager/my_invoice_view.php';
}

// ─── Download PDF of my own invoice ──────────────────────────────────────────
elseif ($action === 'my_invoice_pdf') {
    $id = (int)Helpers::get('id');
    $invoice = ($id && $mgrId) ? Database::fetchOne(
        "SELECT * FROM invoices WHERE id=? AND manager_id=? AND type='manager_fee'",
        [$id, $mgrId]
    ) : null;
    if (!$invoice) { http_response_code(404); exit('Invoice not found'); }

    $items   = json_decode($invoice['items'] ?? '[]', true) ?: [];
    $mgrUser = Database::fetchOne("SELECT u.first_name, u.last_name, u.email FROM affiliate_managers am JOIN users u ON u.id=am.user_id WHERE am.id=?", [$mgrId]);
    $entityName  = $mgrUser ? trim($mgrUser['first_name'].' '.$mgrUser['last_name']) : '';
    $entityEmail = $mgrUser['email'] ?? '';

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

// ─── Request payout invoice (sent to admin for approval) ─────────────────────
elseif ($action === 'request_invoice') {
    if (!$mgrId) {
        Helpers::flash('error', 'Manager record not found.');
        Helpers::redirect('/affiliate_manager/invoices');
    }

    require_once BASE_PATH . '/core/ManagerCommissionService.php';
    $mgrBalance = ManagerCommissionService::getManagerBalance($mgrId);

    // Load managed affiliates for the account selector
    $managedAffiliatesForRequest = empty($affIds) ? [] : Database::fetchAll(
        "SELECT af.id, af.affiliate_code,
                CONCAT(u.first_name,' ',u.last_name) as full_name,
                u.email
         FROM affiliates af
         JOIN users u ON u.id=af.user_id
         WHERE af.id IN ($inSql) AND u.status='active'
         ORDER BY u.first_name, u.last_name",
        $affIds
    );

    // Fetch this manager's existing requests (join affiliate info)
    $myRequests = Database::fetchAll(
        "SELECT mir.*,
                af.affiliate_code,
                CONCAT(u.first_name,' ',u.last_name) as aff_name
         FROM manager_invoice_requests mir
         LEFT JOIN affiliates af ON af.id = mir.affiliate_id
         LEFT JOIN users u ON u.id = af.user_id
         WHERE mir.manager_id=?
         ORDER BY mir.created_at DESC LIMIT 50",
        [$mgrId]
    );

    $errors = [];

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $amount      = round((float)Helpers::postRaw('amount'), 4);
        $periodStart = Helpers::post('period_start') ?: date('Y-m-01');
        $periodEnd   = Helpers::post('period_end')   ?: date('Y-m-d');
        $notes       = trim(Helpers::post('notes') ?? '');
        $affiliateId = (int)Helpers::postRaw('affiliate_id') ?: null;

        // Validate affiliate belongs to this manager (if provided)
        if ($affiliateId && !in_array($affiliateId, $affIds)) {
            $affiliateId = null;
            $errors[] = 'Invalid affiliate account selected.';
        }

        // Block if a pending request already exists
        $existing = Database::fetchOne(
            "SELECT id FROM manager_invoice_requests WHERE manager_id=? AND status='pending' LIMIT 1",
            [$mgrId]
        );

        if ($existing) {
            $errors[] = 'You already have a pending invoice request. Please wait for the admin to review it before submitting another.';
        }
        if ($amount <= 0) $errors[] = 'Amount must be greater than zero.';
        if (!$periodStart || !$periodEnd) $errors[] = 'Period start and end dates are required.';
        if ($periodEnd < $periodStart)    $errors[] = 'Period end must be on or after period start.';

        // When an affiliate is selected, validate against the affiliate's balance.
        // When no affiliate, validate against the manager's own commission balance.
        if (!$errors && $affiliateId) {
            $affBalRow = Database::fetchOne(
                "SELECT af.balance, af.affiliate_code FROM affiliates af WHERE af.id = ?",
                [$affiliateId]
            );
            $affAvail = (float)($affBalRow['balance'] ?? 0);
            if ($amount > $affAvail + 0.0001) {
                $errors[] = 'Amount ($' . number_format($amount, 2) . ') exceeds the affiliate\'s available balance ($' . number_format($affAvail, 2) . ').';
            }
        } elseif (!$errors) {
            if ($amount > $mgrBalance['balance'] + 0.0001) {
                $errors[] = 'Amount ($' . number_format($amount, 2) . ') exceeds your available balance ($' . number_format($mgrBalance['balance'], 2) . ').';
            }
        }
        if (!$periodStart || !$periodEnd)                    $errors[] = 'Period start and end dates are required.';
        if ($periodEnd < $periodStart)                       $errors[] = 'Period end must be on or after period start.';

        if (!$errors) {
            Database::insert('manager_invoice_requests', [
                'manager_id'   => $mgrId,
                'affiliate_id' => $affiliateId,
                'amount'       => $amount,
                'period_start' => $periodStart,
                'period_end'   => $periodEnd,
                'notes'        => $notes ?: null,
                'status'       => 'pending',
            ]);

            // Notify admin via the notifications table
            try {
                $mgrName = trim(Auth::currentUser()['first_name'] . ' ' . Auth::currentUser()['last_name']);
                Database::insert('notifications', [
                    'user_id'     => null,
                    'target_role' => 'admin',
                    'type'        => 'info',
                    'title'       => 'Invoice Request from ' . $mgrName,
                    'message'     => $mgrName . ' requested a payout invoice of $' . number_format($amount, 2) . '.',
                    'link'        => '/admin/affiliate-managers/invoice-requests',
                    'is_read'     => 0,
                ]);
            } catch (\Throwable $_e) {}

            Helpers::flash('success', 'Your invoice request for $' . number_format($amount, 2) . ' has been submitted to the admin.');
            Helpers::redirect('/affiliate_manager/invoices?action=request_invoice');
        }
    }

    require BASE_PATH . '/views/affiliate_manager/request_invoice.php';
}

// ─── My Earnings — commission history for this manager ────────────────────────
elseif ($action === 'earnings') {
    if ($mgrHideEarnings) {
        Helpers::flash('error', 'This section has been disabled by the administrator.');
        Helpers::redirect('/affiliate_manager/invoices');
    }
    $tab  = 'earnings';
    $from = Helpers::get('from') ?: date('Y-m-d', strtotime('-29 days'));
    $to   = Helpers::get('to')   ?: date('Y-m-d');

    $earningsBalance = $mgrId
        ? ManagerCommissionService::getManagerBalance($mgrId)
        : ['balance' => 0.0, 'pending' => 0.0, 'approved' => 0.0, 'paid' => 0.0, 'total_earned' => 0.0];

    $earningsHistory = $mgrId
        ? ManagerCommissionService::getEarningsHistory($mgrId, $from, $to)
        : [];

    // Also load my invoices so the tab header badge works
    $myInvoices = [];
    $myPending  = 0.0;
    $myPaid     = 0.0;
    if ($mgrId) {
        try {
            $myInvoices = Database::fetchAll(
                "SELECT i.*, CONCAT(u.first_name,' ',u.last_name) as created_by_name
                 FROM invoices i
                 LEFT JOIN users u ON u.id=i.created_by
                 WHERE i.manager_id=? AND i.type='manager_fee'
                 ORDER BY i.created_at DESC",
                [$mgrId]
            );
            $myPending = array_sum(array_map(fn($i) => in_array($i['status'], ['sent','draft']) ? (float)($i['total'] ?? 0) : 0, $myInvoices));
            $myPaid    = array_sum(array_map(fn($i) => $i['status'] === 'paid' ? (float)($i['total'] ?? 0) : 0, $myInvoices));
        } catch (\Throwable $e) {}
    }

    require BASE_PATH . '/views/affiliate_manager/invoices.php';
}
