<?php
/**
 * AutoInvoiceGeneratorController — Admin Automatic Invoice Generator & Billing Scheduler
 *
 * Sub-modules:
 * 1. Global Scheduler Settings
 * 2. Affiliate Billing Rules
 * 3. Offer Billing Rules
 * 4. Generated Invoices
 * 5. Invoice Logs
 * 6. Manual Generate & Preview
 */

Auth::check('admin');
AutoInvoiceEngine::ensureSchema();

$pageTitle = 'Automatic Invoice Generator';
$tab = $_GET['tab'] ?? 'scheduler';
$action = $_GET['action'] ?? ($_POST['action'] ?? null);

// ──────────────────────────────────────────────────────────────────────────────
// POST / AJAX ACTIONS
// ──────────────────────────────────────────────────────────────────────────────

if (Helpers::isPost()) {
    $token = Helpers::postRaw('_token') ?: ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!Auth::verifyCsrf($token)) {
        if (Helpers::isAjax()) {
            Helpers::jsonResponse(['success' => false, 'error' => 'CSRF token mismatch or expired. Please refresh the page.']);
        }
        Helpers::flash('error', 'Invalid security token.');
        Helpers::redirect('/admin/auto-invoices?tab=' . urlencode($tab));
    }

    $adminId = Auth::id();

    switch ($action) {
        // ── 1. Save Global Schedule ──
        case 'save_schedule':
            try {
                $saved = AutoInvoiceEngine::saveGlobalSchedule($_POST, $adminId);
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse(['success' => true, 'message' => 'Global invoice scheduler settings saved successfully.', 'schedule' => $saved]);
                }
                Helpers::flash('success', 'Global scheduler configuration updated.');
            } catch (\Throwable $e) {
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse(['success' => false, 'error' => $e->getMessage()]);
                }
                Helpers::flash('error', $e->getMessage());
            }
            Helpers::redirect('/admin/auto-invoices?tab=scheduler');
            break;

        // ── 2. Run Auto Invoice Now (Manual Trigger) ──
        case 'run_scheduler_now':
            try {
                $res = AutoInvoiceEngine::runAutoGeneration('admin_manual', $adminId);
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse([
                        'success' => true,
                        'message' => "Scheduler completed: {$res['generated']} generated, {$res['skipped']} skipped. Total: \${$res['total_amt']}",
                        'result'  => $res,
                    ]);
                }
                Helpers::flash('success', "Scheduler ran: {$res['generated']} generated, {$res['skipped']} skipped. Total: \${$res['total_amt']}");
            } catch (\Throwable $e) {
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse(['success' => false, 'error' => $e->getMessage()]);
                }
                Helpers::flash('error', $e->getMessage());
            }
            Helpers::redirect('/admin/auto-invoices?tab=scheduler');
            break;

        // ── 3. Save Affiliate Rule ──
        case 'save_affiliate_rule':
            try {
                $affiliateIds = $_POST['affiliate_ids'] ?? [];
                if (!is_array($affiliateIds)) {
                    $affiliateIds = !empty($_POST['affiliate_id']) ? [$_POST['affiliate_id']] : [];
                }

                if (empty($affiliateIds)) {
                    throw new Exception('Please select at least one affiliate.');
                }

                $count = AutoInvoiceEngine::batchSaveAffiliateRules($affiliateIds, $_POST, $adminId);
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse(['success' => true, 'message' => "Saved billing rules for {$count} affiliate(s)."]);
                }
                Helpers::flash('success', "Saved billing rules for {$count} affiliate(s).");
            } catch (\Throwable $e) {
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse(['success' => false, 'error' => $e->getMessage()]);
                }
                Helpers::flash('error', $e->getMessage());
            }
            Helpers::redirect('/admin/auto-invoices?tab=affiliate_rules');
            break;

        // ── 4. Delete Affiliate Rule ──
        case 'delete_affiliate_rule':
            $id = (int)($_POST['id'] ?? 0);
            AutoInvoiceEngine::deleteAffiliateRule($id, $adminId);
            if (Helpers::isAjax()) {
                Helpers::jsonResponse(['success' => true, 'message' => 'Affiliate billing rule deleted.']);
            }
            Helpers::flash('success', 'Affiliate rule removed.');
            Helpers::redirect('/admin/auto-invoices?tab=affiliate_rules');
            break;

        // ── 5. Save Offer Rule ──
        case 'save_offer_rule':
            try {
                $offerIds = $_POST['offer_ids'] ?? [];
                if (!is_array($offerIds)) {
                    $offerIds = !empty($_POST['offer_id']) ? [$_POST['offer_id']] : [];
                }

                if (empty($offerIds)) {
                    throw new Exception('Please select at least one offer.');
                }

                $count = AutoInvoiceEngine::batchSaveOfferRules($offerIds, $_POST, $adminId);
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse(['success' => true, 'message' => "Saved billing rules for {$count} offer(s)."]);
                }
                Helpers::flash('success', "Saved billing rules for {$count} offer(s).");
            } catch (\Throwable $e) {
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse(['success' => false, 'error' => $e->getMessage()]);
                }
                Helpers::flash('error', $e->getMessage());
            }
            Helpers::redirect('/admin/auto-invoices?tab=offer_rules');
            break;

        // ── 6. Delete Offer Rule ──
        case 'delete_offer_rule':
            $id = (int)($_POST['id'] ?? 0);
            AutoInvoiceEngine::deleteOfferRule($id, $adminId);
            if (Helpers::isAjax()) {
                Helpers::jsonResponse(['success' => true, 'message' => 'Offer billing rule deleted.']);
            }
            Helpers::flash('success', 'Offer rule removed.');
            Helpers::redirect('/admin/auto-invoices?tab=offer_rules');
            break;

        // ── 6b. Save Advertiser Rule ──
        case 'save_advertiser_rule':
            try {
                $advIds = $_POST['advertiser_ids'] ?? [];
                if (!is_array($advIds)) {
                    $advIds = !empty($_POST['advertiser_id']) ? [$_POST['advertiser_id']] : [];
                }

                if (empty($advIds)) {
                    throw new Exception('Please select at least one advertiser.');
                }

                $count = AutoInvoiceEngine::batchSaveAdvertiserRules($advIds, $_POST, $adminId);
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse(['success' => true, 'message' => "Saved billing rules for {$count} advertiser(s)."]);
                }
                Helpers::flash('success', "Saved billing rules for {$count} advertiser(s).");
            } catch (\Throwable $e) {
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse(['success' => false, 'error' => $e->getMessage()]);
                }
                Helpers::flash('error', $e->getMessage());
            }
            Helpers::redirect('/admin/auto-invoices?tab=advertiser_rules');
            break;

        // ── 6c. Delete Advertiser Rule ──
        case 'delete_advertiser_rule':
            $id = (int)($_POST['id'] ?? 0);
            AutoInvoiceEngine::deleteAdvertiserRule($id, $adminId);
            if (Helpers::isAjax()) {
                Helpers::jsonResponse(['success' => true, 'message' => 'Advertiser billing rule deleted.']);
            }
            Helpers::flash('success', 'Advertiser rule removed.');
            Helpers::redirect('/admin/auto-invoices?tab=advertiser_rules');
            break;

        // ── 7. Generate Manual Invoice ──
        case 'generate_manual_invoice':
            try {
                $res = AutoInvoiceEngine::createInvoice($_POST, $adminId, false);
                if (!$res['success']) {
                    throw new Exception($res['error'] ?? 'Could not generate invoice.');
                }
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse([
                        'success'        => true,
                        'message'        => "Invoice {$res['invoice_number']} generated successfully!",
                        'invoice_id'     => $res['invoice_id'],
                        'invoice_number' => $res['invoice_number'],
                        'total'          => $res['total'],
                    ]);
                }
                Helpers::flash('success', "Invoice {$res['invoice_number']} generated successfully for \${$res['total']}.");
            } catch (\Throwable $e) {
                if (Helpers::isAjax()) {
                    Helpers::jsonResponse(['success' => false, 'error' => $e->getMessage()]);
                }
                Helpers::flash('error', $e->getMessage());
            }
            Helpers::redirect('/admin/auto-invoices?tab=invoices');
            break;

        // ── 8. Update Invoice Status ──
        case 'update_status':
            $id = (int)($_POST['invoice_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');
            $res = AutoInvoiceEngine::updateInvoiceStatus($id, $newStatus, $adminId);
            if (Helpers::isAjax()) {
                Helpers::jsonResponse(['success' => $res]);
            }
            Helpers::flash($res ? 'success' : 'error', $res ? "Status updated to {$newStatus}" : 'Could not update status.');
            Helpers::redirect('/admin/auto-invoices?tab=invoices');
            break;

        // ── 9. Cancel Invoice ──
        case 'cancel_invoice':
            $id = (int)($_POST['invoice_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            $res = AutoInvoiceEngine::cancelInvoice($id, $adminId, $reason);
            if (Helpers::isAjax()) {
                Helpers::jsonResponse(['success' => $res]);
            }
            Helpers::flash($res ? 'success' : 'error', $res ? 'Invoice cancelled and balance restored.' : 'Failed to cancel invoice.');
            Helpers::redirect('/admin/auto-invoices?tab=invoices');
            break;

        // ── 10. Resend Invoice Email ──
        case 'resend_email':
            $id = (int)($_POST['invoice_id'] ?? 0);
            $sent = AutoInvoiceEngine::sendInvoiceEmail($id);
            if (Helpers::isAjax()) {
                Helpers::jsonResponse(['success' => $sent, 'message' => $sent ? 'Email sent successfully!' : 'Failed to send email. Check SMTP settings.']);
            }
            Helpers::flash($sent ? 'success' : 'error', $sent ? 'Email notification sent.' : 'Email sending failed.');
            Helpers::redirect('/admin/auto-invoices?tab=invoices');
            break;

        // ── 11. Delete Invoice & Sync Balance ──
        case 'delete_invoice':
            $id = (int)($_POST['invoice_id'] ?? 0);
            $res = AutoInvoiceEngine::deleteInvoice($id, $adminId);
            if (Helpers::isAjax()) {
                Helpers::jsonResponse(['success' => $res, 'message' => $res ? 'Invoice deleted and affiliate balance reconciled.' : 'Failed to delete invoice.']);
            }
            Helpers::flash($res ? 'success' : 'error', $res ? 'Invoice deleted and affiliate balance reconciled.' : 'Failed to delete invoice.');
            Helpers::redirect('/admin/auto-invoices?tab=invoices');
            break;

        // ── 12. Recalculate & Restore Affiliate Balances ──
        case 'recalculate_balances':
            $affId = !empty($_POST['affiliate_id']) ? (int)$_POST['affiliate_id'] : null;
            $res = AutoInvoiceEngine::recalculateAffiliateBalance($affId);
            if (Helpers::isAjax()) {
                Helpers::jsonResponse(['success' => true, 'message' => "Successfully synchronized exact balance for {$res['updated_count']} affiliate(s).", 'data' => $res]);
            }
            Helpers::flash('success', "Synchronized exact balance for {$res['updated_count']} affiliate(s) based on approved conversions.");
            Helpers::redirect('/admin/auto-invoices?tab=' . urlencode($tab));
            break;
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// GET / AJAX QUERIES
// ──────────────────────────────────────────────────────────────────────────────

if ($action === 'preview_conversions') {
    $affId     = (int)($_GET['affiliate_id'] ?? 0);
    $startDate = $_GET['start_date'] ?? date('Y-m-01', strtotime('last month'));
    $endDate   = $_GET['end_date']   ?? date('Y-m-t', strtotime('last month'));
    $offerId   = !empty($_GET['offer_id']) ? (is_array($_GET['offer_id']) ? $_GET['offer_id'] : explode(',', $_GET['offer_id'])) : null;
    $country   = $_GET['country'] ?? null;

    if ($affId <= 0) {
        Helpers::jsonResponse(['success' => false, 'error' => 'Please select an affiliate.']);
    }

    $aff = Database::fetchOne("
        SELECT af.*, u.first_name, u.last_name, u.email
        FROM `affiliates` af JOIN `users` u ON u.id=af.user_id WHERE af.id=?
    ", [$affId]);

    if (!$aff) {
        Helpers::jsonResponse(['success' => false, 'error' => 'Affiliate not found.']);
    }

    $convs = AutoInvoiceEngine::getEligibleConversions($affId, $startDate, $endDate, [
        'offer_id' => $offerId,
        'country'  => $country,
    ]);

    $built = AutoInvoiceEngine::buildInvoiceItemsFromConversions($convs);
    $rule  = AutoInvoiceEngine::resolveEffectiveRule($affId);
    $isDup = AutoInvoiceEngine::isDuplicate($affId, $startDate, $endDate);

    Helpers::jsonResponse([
        'success'          => true,
        'affiliate'        => [
            'id'            => $aff['id'],
            'name'          => trim($aff['first_name'] . ' ' . $aff['last_name']),
            'email'         => $aff['email'],
            'balance'       => (float)$aff['balance'],
            'threshold'     => (float)$aff['payment_threshold'],
            'payment_method'=> $aff['payment_method'],
        ],
        'rule'             => $rule,
        'is_duplicate'     => $isDup,
        'conversion_count' => $built['conversion_count'],
        'subtotal'         => $built['total'],
        'items'            => $built['items'],
        'raw_conversions'  => array_slice($built['raw_items'], 0, 100), // Preview top 100
        'total_raw_count'  => count($built['raw_items']),
    ]);
}

// Download PDF action
if ($action === 'download_pdf') {
    $invId = (int)($_GET['id'] ?? 0);
    $inv = Database::fetchOne("
        SELECT i.*, af.payment_method, af.payment_details, u.first_name, u.last_name, u.email
        FROM `invoices` i
        LEFT JOIN `affiliates` af ON af.id = i.affiliate_id
        LEFT JOIN `users` u ON u.id = af.user_id
        WHERE i.id = ?
    ", [$invId]);

    if (!$inv) {
        Helpers::flash('error', 'Invoice not found.');
        Helpers::redirect('/admin/auto-invoices?tab=invoices');
    }

    // Check if PDF file exists on disk, otherwise generate on the fly
    $pdfFile = BASE_PATH . '/uploads/invoices/' . preg_replace('/[^A-Za-z0-9\-_]/', '_', $inv['invoice_number']) . '.pdf';
    if (!file_exists($pdfFile)) {
        $items = json_decode($inv['items'] ?? '[]', true) ?: [];
        $name  = trim($inv['first_name'] . ' ' . $inv['last_name']) ?: 'Affiliate #' . $inv['affiliate_id'];
        $pdfFile = InvoicePDF::generate(
            $inv,
            $items,
            $name,
            $inv['email'] ?? '',
            $inv['payment_method'] ?? '',
            $inv['payment_details'] ?? ''
        );
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($pdfFile) . '"');
    header('Content-Length: ' . filesize($pdfFile));
    readfile($pdfFile);
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// DATA LOADING FOR TABBED VIEWS
// ──────────────────────────────────────────────────────────────────────────────

$stats = AutoInvoiceEngine::getDashboardStats();
$schedule = AutoInvoiceEngine::getGlobalSchedule();

// Active Affiliates list for selects
$affiliates = Database::fetchAll("
    SELECT af.id, af.balance, af.payment_threshold, af.payment_method, u.first_name, u.last_name, u.email
    FROM `affiliates` af
    JOIN `users` u ON u.id = af.user_id
    WHERE u.status = 'active'
    ORDER BY u.first_name ASC
");

// Active Offers list for selects
$offers = Database::fetchAll("
    SELECT id, name, payout_amount, payout_type, status
    FROM `offers`
    ORDER BY name ASC
");

$advertisers = Database::fetchAll("
    SELECT adv.id, u.company, u.first_name, u.last_name, u.email,
           COALESCE(NULLIF(u.company, ''), CONCAT(u.first_name, ' ', u.last_name)) AS name
    FROM `advertisers` adv
    JOIN `users` u ON u.id = adv.user_id
    WHERE u.status = 'active'
    ORDER BY u.company ASC, u.first_name ASC
") ?: [];

// Load tab specific data
$affRules = [];
$offerRules = [];
$advRules = [];
$invoices = [];
$logs = [];

if ($tab === 'affiliate_rules') {
    $affRules = AutoInvoiceEngine::getAffiliateRules([
        'search'    => $_GET['search'] ?? '',
        'enabled'   => $_GET['enabled'] ?? '',
        'frequency' => $_GET['frequency'] ?? '',
    ]);
} elseif ($tab === 'offer_rules') {
    $offerRules = AutoInvoiceEngine::getOfferRules([
        'search'    => $_GET['search'] ?? '',
        'enabled'   => $_GET['enabled'] ?? '',
        'frequency' => $_GET['frequency'] ?? '',
    ]);
} elseif ($tab === 'advertiser_rules') {
    $advRules = AutoInvoiceEngine::getAdvertiserRules([
        'search'    => $_GET['search'] ?? '',
        'enabled'   => $_GET['enabled'] ?? '',
        'frequency' => $_GET['frequency'] ?? '',
    ]);
} elseif ($tab === 'invoices') {
    $invSql = "
        SELECT i.*, u.first_name, u.last_name, u.email as affiliate_email, af.id as aff_id
        FROM `invoices` i
        LEFT JOIN `affiliates` af ON af.id = i.affiliate_id
        LEFT JOIN `users` u ON u.id = af.user_id
        WHERE 1=1
    ";
    $invParams = [];
    if (!empty($_GET['status'])) {
        $invSql .= " AND i.status = ?";
        $invParams[] = $_GET['status'];
    }
    if (!empty($_GET['affiliate_id'])) {
        $invSql .= " AND i.affiliate_id = ?";
        $invParams[] = (int)$_GET['affiliate_id'];
    }
    if (!empty($_GET['is_auto']) && $_GET['is_auto'] !== '') {
        $invSql .= " AND i.is_auto = ?";
        $invParams[] = (int)$_GET['is_auto'];
    }
    $invSql .= " ORDER BY i.id DESC LIMIT 100";
    $invoices = Database::fetchAll($invSql, $invParams);
} elseif ($tab === 'logs') {
    $logs = AutoInvoiceEngine::getLogs([
        'action'       => $_GET['action_filter'] ?? '',
        'affiliate_id' => $_GET['aff_filter'] ?? '',
        'is_auto'      => $_GET['auto_filter'] ?? '',
    ], 100, 0);
}

require BASE_PATH . '/views/admin/auto_invoices/index.php';
