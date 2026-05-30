<?php
Auth::check('admin');
$pageTitle = 'Auto-Generate Invoices';

// ── Schema migrations ──────────────────────────────────────────────────────
try { Database::query("ALTER TABLE invoices ADD COLUMN balance_deducted TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE invoices ADD COLUMN period_hash VARCHAR(64) DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE conversions ADD COLUMN invoice_id INT UNSIGNED DEFAULT NULL"); } catch(Exception $e) {}
try { Database::query("ALTER TABLE conversions ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e) {}

/**
 * Compute the "current period" date range for a given payment term.
 * Returns [period_start, period_end, due_date] as Y-m-d strings.
 */
function computePeriod(string $terms): array
{
    $today = new DateTime();
    switch ($terms) {
        case 'weekly':
            $end   = clone $today;
            $end->modify('last sunday');
            $start = clone $end;
            $start->modify('-6 days');
            $due   = clone $today;
            $due->modify('+3 days');
            break;
        case 'net15':
            $start = new DateTime('first day of last month');
            $end   = new DateTime('last day of last month');
            $due   = clone $today;
            $due->modify('+15 days');
            break;
        case 'net30':
            $start = new DateTime('first day of last month');
            $end   = new DateTime('last day of last month');
            $due   = clone $today;
            $due->modify('+30 days');
            break;
        default: // monthly
            $start = new DateTime('first day of last month');
            $end   = new DateTime('last day of last month');
            $due   = clone $today;
            $due->modify('+30 days');
            break;
    }
    return [$start->format('Y-m-d'), $end->format('Y-m-d'), $due->format('Y-m-d')];
}

/**
 * Build detailed invoice items from uninvoiced conversions in [pStart, pEnd].
 * Groups by offer so each offer becomes one line item.
 * Returns ['items' => [...], 'total' => float, 'db_ids' => [int,...]]
 */
function buildInvoiceItems(int $affId, string $pStart, string $pEnd): array
{
    try {
        $convRows = Database::fetchAll(
            "SELECT c.id AS db_id, c.conversion_id, c.payout,
                    c.offer_id, o.name AS offer_name
             FROM conversions c
             JOIN offers o ON o.id = c.offer_id
             WHERE c.affiliate_id = ?
               AND c.converted_at BETWEEN ? AND ?
               AND c.status = 'approved'
               AND COALESCE(c.is_hidden, 0) = 0
               AND (c.invoice_id IS NULL OR c.invoice_id = 0)
             ORDER BY o.id ASC, c.converted_at ASC",
            [$affId, $pStart . ' 00:00:00', $pEnd . ' 23:59:59']
        );
    } catch (Exception $e) {
        $convRows = [];
    }

    if (empty($convRows)) {
        return ['items' => [], 'total' => 0.0, 'db_ids' => []];
    }

    // Group by offer
    $byOffer = [];
    foreach ($convRows as $c) {
        $oid = (int)$c['offer_id'];
        if (!isset($byOffer[$oid])) {
            $byOffer[$oid] = [
                'offer_id'       => $oid,
                'offer_name'     => $c['offer_name'],
                'conversion_ids' => [],
                'db_ids'         => [],
                'total'          => 0.0,
            ];
        }
        $byOffer[$oid]['conversion_ids'][] = $c['conversion_id'];
        $byOffer[$oid]['db_ids'][]         = (int)$c['db_id'];
        $byOffer[$oid]['total']           += (float)$c['payout'];
    }

    $items = [];
    $dbIds = [];
    $grand = 0.0;
    foreach ($byOffer as $offer) {
        $count   = count($offer['conversion_ids']);
        $total   = round($offer['total'], 4);
        $rate    = $count > 0 ? round($total / $count, 4) : 0.0;
        $offCode = 'OFF-' . str_pad($offer['offer_id'], 4, '0', STR_PAD_LEFT);

        $items[] = [
            'offer_id'         => $offer['offer_id'],
            'offer_name'       => $offer['offer_name'],
            'description'      => $offer['offer_name'] . ' (' . $offCode . ')',
            'conversion_count' => $count,
            'conversion_ids'   => $offer['conversion_ids'],
            'qty'              => $count,
            'rate'             => $rate,
            'amount'           => $total,
        ];
        $dbIds  = array_merge($dbIds, $offer['db_ids']);
        $grand += $total;
    }

    return ['items' => $items, 'total' => round($grand, 4), 'db_ids' => $dbIds];
}

// ── POST: generate confirmed invoices ────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    $selectedIds = $_POST['affiliate_ids'] ?? [];
    $dueOverride = Helpers::post('due_date_override') ?: null;
    $generated   = 0;
    $skipped     = 0;

    foreach ($selectedIds as $affId) {
        $affId = (int)$affId;
        $aff   = Database::fetchOne(
            "SELECT af.*, u.email, u.first_name, u.last_name
             FROM affiliates af JOIN users u ON u.id=af.user_id WHERE af.id=?",
            [$affId]
        );
        if (!$aff) { $skipped++; continue; }

        [$pStart, $pEnd, $dueDate] = computePeriod($aff['payment_terms'] ?? 'monthly');
        if ($dueOverride) $dueDate = $dueOverride;

        // Period hash for robust duplicate detection
        $periodHash = md5($affId . '|' . $pStart);

        // Avoid duplicate invoices for same affiliate + same period
        $exists = Database::fetchOne(
            "SELECT id FROM invoices WHERE affiliate_id=? AND period_start=? AND status != 'void'",
            [$affId, $pStart]
        );
        if ($exists) { $skipped++; continue; }

        // Build detailed items from actual uninvoiced conversions in period
        $built  = buildInvoiceItems($affId, $pStart, $pEnd);

        if (!empty($built['items'])) {
            $amount = $built['total'];
            $items  = $built['items'];
        } else {
            // Fallback: no uninvoiced conversions found — use current balance as single item
            $amount = (float)$aff['balance'];
            if ($amount <= 0) { $skipped++; continue; }
            $items = [[
                'description' => 'Affiliate Earnings ' . $pStart . ' – ' . $pEnd,
                'qty'         => 1,
                'rate'        => $amount,
                'amount'      => $amount,
            ]];
        }

        // Respect payment threshold
        if ($amount < (float)$aff['payment_threshold']) { $skipped++; continue; }

        $invNum = 'INV-' . date('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $invoiceId = Database::insert('invoices', [
            'invoice_number'   => $invNum,
            'type'             => 'affiliate_payout',
            'affiliate_id'     => $affId,
            'period_start'     => $pStart,
            'period_end'       => $pEnd,
            'items'            => json_encode($items),
            'subtotal'         => $amount,
            'tax_rate'         => 0,
            'tax_amount'       => 0,
            'total'            => $amount,
            'status'           => 'sent',
            'due_date'         => $dueDate,
            'period_hash'      => $periodHash,
            'balance_deducted' => 0,
            'created_by'       => Auth::id(),
        ]);

        // Mark conversions as invoiced — prevents re-use in future invoices
        if (!empty($built['db_ids'])) {
            $ph     = implode(',', array_fill(0, count($built['db_ids']), '?'));
            $params = array_merge([$invoiceId], $built['db_ids']);
            try {
                Database::query("UPDATE conversions SET invoice_id = ? WHERE id IN ($ph)", $params);
            } catch (Exception $e) {}
        }

        // Auto-deduct exact invoice amount from affiliate balance (floor at 0)
        try {
            Database::query(
                "UPDATE affiliates SET balance = GREATEST(0, balance - ?) WHERE id = ?",
                [$amount, $affId]
            );
            Database::query(
                "UPDATE invoices SET balance_deducted = 1 WHERE id = ?",
                [$invoiceId]
            );
        } catch (Exception $e) {}

        // Send invoice email
        try {
            Mailer::sendEvent($aff['email'], $aff['first_name'] . ' ' . $aff['last_name'], 'invoice_created', [
                'name'           => $aff['first_name'] . ' ' . $aff['last_name'],
                'email'          => $aff['email'],
                'site_name'      => Config::get('config', 'app.name') ?? 'AffiliateTracker',
                'app_url'        => rtrim(Config::get('config', 'app.url') ?? '', '/'),
                'invoice_number' => $invNum,
                'total'          => '$' . number_format($amount, 2),
                'due_date'       => $dueDate,
            ]);
        } catch (Exception $e) {}

        $generated++;
    }

    Helpers::flash('success', "$generated invoice(s) generated, $skipped skipped (duplicate, below threshold, or no qualifying conversions).");
    Helpers::redirect('/admin/invoices');
}

// ── GET: preview who qualifies ────────────────────────────────────────────────
$candidates = Database::fetchAll(
    "SELECT af.id, af.balance, af.payment_threshold, af.payment_method, af.payment_terms,
            u.first_name, u.last_name, u.email
     FROM affiliates af JOIN users u ON u.id=af.user_id
     WHERE u.status='active' AND af.balance > 0
     ORDER BY u.first_name"
);

// Annotate: period, duplicate check, uninvoiced conversion totals
foreach ($candidates as &$c) {
    [$pStart, $pEnd, $due] = computePeriod($c['payment_terms'] ?? 'monthly');
    $c['period_start'] = $pStart;
    $c['period_end']   = $pEnd;
    $c['due_date']     = $due;

    // Count uninvoiced conversions for the period
    try {
        $convTot = Database::fetchOne(
            "SELECT COUNT(*) as cnt, COALESCE(SUM(payout),0) as tot
             FROM conversions
             WHERE affiliate_id = ?
               AND converted_at BETWEEN ? AND ?
               AND status = 'approved'
               AND COALESCE(is_hidden,0) = 0
               AND (invoice_id IS NULL OR invoice_id = 0)",
            [$c['id'], $pStart . ' 00:00:00', $pEnd . ' 23:59:59']
        );
        $c['conv_count'] = (int)($convTot['cnt'] ?? 0);
        $c['conv_total'] = round((float)($convTot['tot'] ?? 0), 2);
    } catch (Exception $e) {
        $c['conv_count'] = 0;
        $c['conv_total'] = 0.0;
    }

    // Effective invoice amount
    $effectiveAmount = $c['conv_total'] > 0 ? $c['conv_total'] : (float)$c['balance'];
    $c['qualifies']  = $effectiveAmount >= (float)$c['payment_threshold'];

    $dup = Database::fetchOne(
        "SELECT id FROM invoices WHERE affiliate_id=? AND period_start=? AND status != 'void'",
        [$c['id'], $pStart]
    );
    $c['duplicate'] = (bool)$dup;
}
unset($c);

require BASE_PATH . '/views/admin/invoices/auto_generate.php';
