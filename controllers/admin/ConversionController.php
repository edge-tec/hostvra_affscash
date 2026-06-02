<?php
/**
 * Admin — Conversions
 *
 * Handles:
 *   - Listing / filtering conversions
 *   - Status changes (approved / rejected / chargebacked)
 *   - Fires affiliate postbacks on EVERY status change so the affiliate's
 *     tracker is always kept in sync with our conversion status.
 */
Auth::check('admin');
$pageTitle = 'Conversions';

// ── Require PostbackFirer ─────────────────────────────────────────────────
require_once BASE_PATH . '/core/PostbackFirer.php';
require_once BASE_PATH . '/core/ManagerCommissionService.php';

// ── Ensure offers.landing_page_names column exists ───────────────────────
// This column is created by OfferController but ConversionController queries
// it directly — add the same idempotent guard here so the column always exists.
try { Database::query("ALTER TABLE offers ADD COLUMN landing_page_names TEXT DEFAULT NULL"); } catch (\Throwable $e) {}

// ── Auto-fire pending: AJAX endpoint called automatically by the admin panel ──
// Fires conversions that were never sent (postback_sent=0, no log entries).
// Called silently by JS on every admin page load — no admin action needed.
// Also accepts regular POST with CSRF for any direct callers.
if (Helpers::isPost() && Helpers::post('action') === 'auto_fire_pending') {
    // Verify admin auth (session already checked by Auth::check above).
    // CSRF is verified if token is present; AJAX calls always include it.
    if (!empty(Helpers::postRaw('_token')) && !Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'CSRF mismatch']);
        exit;
    }
    header('Content-Type: application/json');

    // Only fire true orphans (no log entries at all) from the last 24 hours.
    // Conversions with existing log entries are handled by the cron retry mechanism.
    try {
        $orphans = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.offer_id, cv.affiliate_id,
                    cv.payout, cv.revenue, cv.status,
                    ck.sub1, ck.sub2, ck.sub3, ck.sub4, ck.sub5,
                    COALESCE(ck.sub6, '') as sub6, ck.source
             FROM conversions cv
             LEFT JOIN clicks ck ON ck.click_id = cv.click_id
             WHERE cv.postback_sent = 0
               AND cv.status IN ('approved','pending')
               AND COALESCE(cv.is_hidden, 0) = 0
               AND cv.converted_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
               AND NOT EXISTS (
                   SELECT 1 FROM postback_logs pl WHERE pl.conversion_id = cv.conversion_id
               )
             ORDER BY cv.converted_at ASC
             LIMIT 100",
            []
        );
    } catch (\Throwable $e) {
        echo json_encode(['status' => 'ok', 'fired' => 0, 'error' => $e->getMessage()]);
        exit;
    }

    $fired = 0;
    foreach ($orphans as $conv) {
        $convForPostback = [
            'conversion_id' => $conv['conversion_id'],
            'click_id'      => $conv['click_id'],
            'offer_id'      => $conv['offer_id'],
            'affiliate_id'  => $conv['affiliate_id'],
            'payout'        => (float)$conv['payout'],
            'revenue'       => (float)($conv['revenue'] ?? $conv['payout']),
            'status'        => 'approved', // force approved so global postback fires
            'source'        => $conv['source'] ?? '',
            'sub1'          => $conv['sub1'] ?? '',
            'sub2'          => $conv['sub2'] ?? '',
            'sub3'          => $conv['sub3'] ?? '',
            'sub4'          => $conv['sub4'] ?? '',
            'sub5'          => $conv['sub5'] ?? '',
            'sub6'          => $conv['sub6'] ?? '',
        ];
        try {
            PostbackFirer::fireAll($convForPostback, 'approved', true);
            $fired++;
        } catch (\Throwable $e) {
            PostbackFirer::log("[auto_fire_pending] Error for {$conv['conversion_id']}: " . $e->getMessage());
        }
    }

    if ($fired > 0) {
        PostbackFirer::log("[auto_fire_pending] Auto-fired {$fired} orphaned conversions.");
    }

    echo json_encode(['status' => 'ok', 'fired' => $fired, 'remaining' => count($orphans) - $fired]);
    exit;
}

// ── Handle approval/rejection ─────────────────────────────────────────────
if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
    // ── Handle bulk re-fire all failed postbacks ──────────────────────────
    if (Helpers::post('action') === 'refire_all_postbacks') {
        $fromDate = Helpers::postRaw('from') ?: date('Y-m-01');
        $toDate   = Helpers::postRaw('to')   ?: date('Y-m-d');
        $dateFrom = date('Y-m-d 00:00:00', strtotime($fromDate));
        $dateTo   = date('Y-m-d 23:59:59', strtotime($toDate));

        $pending = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.offer_id, cv.affiliate_id,
                    cv.payout, cv.revenue, cv.status,
                    ck.sub1, ck.sub2, ck.sub3, ck.sub4, ck.sub5,
                    COALESCE(ck.sub6, '') as sub6, ck.source
             FROM conversions cv
             LEFT JOIN clicks ck ON ck.click_id = cv.click_id
             WHERE cv.postback_sent = 0
               AND cv.status IN ('approved','pending')
               AND COALESCE(cv.is_hidden, 0) = 0
               AND cv.converted_at BETWEEN ? AND ?
             ORDER BY cv.converted_at ASC
             LIMIT 500",
            [$dateFrom, $dateTo]
        );

        $fired = 0;
        foreach ($pending as $conv) {
            $convForPostback = [
                'conversion_id' => $conv['conversion_id'],
                'click_id'      => $conv['click_id'],
                'offer_id'      => $conv['offer_id'],
                'affiliate_id'  => $conv['affiliate_id'],
                'payout'        => (float)$conv['payout'],
                'revenue'       => (float)($conv['revenue'] ?? $conv['payout']),
                'status'        => $conv['status'],
                'source'        => $conv['source'] ?? '',
                'sub1'          => $conv['sub1'] ?? '',
                'sub2'          => $conv['sub2'] ?? '',
                'sub3'          => $conv['sub3'] ?? '',
                'sub4'          => $conv['sub4'] ?? '',
                'sub5'          => $conv['sub5'] ?? '',
                'sub6'          => $conv['sub6'] ?? '',
            ];
            try {
                PostbackFirer::fireAll($convForPostback, $conv['status'], true);
                $fired++;
            } catch (\Throwable $e) {
                PostbackFirer::log("[ConversionController] Bulk re-fire error for {$conv['conversion_id']}: " . $e->getMessage());
            }
        }
        PostbackFirer::log("[ConversionController] Bulk re-fire: fired {$fired} of " . count($pending) . " pending conversions.");
        Helpers::flash('success', "Re-fired postbacks for {$fired} conversion(s). They should now appear in your affiliate tracker.");
        $back = Helpers::postRaw('redirect_back') ?: '/admin/reports?tab=conversions';
        Helpers::redirect($back);
    }

    // ── Handle manual postback re-fire ────────────────────────────────────
    if (Helpers::post('action') === 'refire_postback') {
        $convId = Helpers::postRaw('conversion_id');
        $conv   = Database::fetchOne(
            "SELECT cv.*, ck.source, ck.sub1, ck.sub2, ck.sub3, ck.sub4, ck.sub5,
                    COALESCE(ck.sub6, '') as sub6
             FROM conversions cv
             LEFT JOIN clicks ck ON ck.click_id = cv.click_id
             WHERE cv.conversion_id = ?",
            [$convId]
        );
        if ($conv) {
            $convForPostback = [
                'conversion_id' => $conv['conversion_id'],
                'click_id'      => $conv['click_id'],
                'offer_id'      => $conv['offer_id'],
                'affiliate_id'  => $conv['affiliate_id'],
                'payout'        => (float)$conv['payout'],
                'revenue'       => (float)($conv['revenue'] ?? $conv['payout']),
                'status'        => $conv['status'],
                'source'        => $conv['source'] ?? '',
                'sub1'          => $conv['sub1'] ?? '',
                'sub2'          => $conv['sub2'] ?? '',
                'sub3'          => $conv['sub3'] ?? '',
                'sub4'          => $conv['sub4'] ?? '',
                'sub5'          => $conv['sub5'] ?? '',
                'sub6'          => $conv['sub6'] ?? '',
            ];
            try {
                PostbackFirer::fireAll($convForPostback, $conv['status'], true);
                PostbackFirer::log("[ConversionController] Manual re-fire for {$convId}: status={$conv['status']}");
                Helpers::flash('success', 'Postback re-fired successfully for conversion ' . substr($convId, 0, 8) . '...');
            } catch (\Throwable $e) {
                PostbackFirer::log("[ConversionController] Re-fire error for {$convId}: " . $e->getMessage());
                Helpers::flash('error', 'Re-fire failed: ' . $e->getMessage());
            }
        }
        // Redirect back to the referring page (reports or conversions)
        $back = Helpers::postRaw('redirect_back') ?: '/admin/conversions';
        Helpers::redirect($back);
    }

    $convId    = Helpers::postRaw('conversion_id');
    $newStatus = Helpers::post('status');

    if (in_array($newStatus, ['approved', 'rejected', 'chargebacked'])) {
        // Load full conversion + click sub parameters for postback macro resolution
        $conv = Database::fetchOne(
            "SELECT cv.*,
                    ck.source, ck.sub1, ck.sub2, ck.sub3, ck.sub4, ck.sub5,
                    COALESCE(ck.sub6, '') as sub6
             FROM conversions cv
             LEFT JOIN clicks ck ON ck.click_id = cv.click_id
             WHERE cv.conversion_id = ?",
            [$convId]
        );

        if ($conv) {
            $oldStatus = $conv['status'];

            // ── 1. Update status (with rejection reason + timestamp) ──────
            $rejectReason = trim((string)Helpers::postRaw('rejection_reason'));
            $payload = RejectionHelper::buildUpdatePayload($newStatus, $rejectReason, (int)(Auth::id() ?? 0));
            Database::update('conversions', $payload, 'conversion_id=?', [$convId]);

            // Instant affiliate notification (+ optional email) — fired only when
            // the conversion was actually flipped into a rejected-style state.
            if (in_array($newStatus, ['rejected', 'chargebacked'], true) && $newStatus !== $oldStatus) {
                try { RejectionNotifier::afterReject((string)$convId); } catch (\Throwable $_rn) {}
            }

            // ── 2. Update affiliate balance ───────────────────────────────
            if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                Database::query(
                    "UPDATE affiliates SET balance = balance + ? WHERE id = ?",
                    [$conv['payout'], $conv['affiliate_id']]
                );
                // Update daily stats
                try {
                    Database::upsertStats(
                        date('Y-m-d', strtotime($conv['converted_at'] ?? 'now')),
                        (int)$conv['affiliate_id'],
                        (int)$conv['offer_id'],
                        ['approved' => 1, 'payout' => (float)$conv['payout']]
                    );
                } catch (\Throwable $e) {}
                // ── 2b. Manager commission: profit-based, never from payout ──
                try {
                    ManagerCommissionService::recordForConversion((int)$conv['id']);
                } catch (\Throwable $e) {}
                // ── 2d. Points + Rewards layer (read-only, additive) ───────
                try {
                    PointsService::onConversionApproved((int)$conv['id']);
                    RewardsService::checkAndGrant((int)$conv['affiliate_id']);
                } catch (\Throwable $e) {}

                // ── IP Conversion Protection System (One per IP) ─────────────────
                try {
                    $visitorIp = trim($conv['ip_address'] ?? '');
                    if ($visitorIp !== '' && $visitorIp !== '0.0.0.0') {
                        Database::insert('offer_conversion_history', [
                            'affiliate_id'      => (int)$conv['affiliate_id'],
                            'offer_id'          => (int)$conv['offer_id'],
                            'visitor_ip'        => $visitorIp,
                            'conversion_time'   => date('Y-m-d H:i:s'),
                            'conversion_status' => 'approved'
                        ]);
                    }
                } catch (\Throwable $e) {
                    PostbackFirer::log('[ConversionController.php] IP Conversion Protection History insertion failed: ' . $e->getMessage());
                }
            } elseif (in_array($newStatus, ['rejected', 'chargebacked']) && $oldStatus === 'approved') {
                Database::query(
                    "UPDATE affiliates SET balance = balance - ? WHERE id = ?",
                    [$conv['payout'], $conv['affiliate_id']]
                );
                // Reverse stats
                try {
                    Database::upsertStats(
                        date('Y-m-d', strtotime($conv['converted_at'] ?? 'now')),
                        (int)$conv['affiliate_id'],
                        (int)$conv['offer_id'],
                        ['approved' => -1, 'payout' => -(float)$conv['payout']]
                    );
                } catch (\Throwable $e) {}
                // ── 2c. Reverse manager commission ────────────────────────────
                try {
                    ManagerCommissionService::reverseForConversion((int)$conv['id']);
                } catch (\Throwable $e) {}

                // ── IP Conversion Protection System (Delete on rejection) ────────
                try {
                    $visitorIp = trim($conv['ip_address'] ?? '');
                    if ($visitorIp !== '' && $visitorIp !== '0.0.0.0') {
                        Database::delete(
                            'offer_conversion_history',
                            'affiliate_id=? AND offer_id=? AND visitor_ip=? AND conversion_status=\'approved\'',
                            [(int)$conv['affiliate_id'], (int)$conv['offer_id'], $visitorIp]
                        );
                    }
                } catch (\Throwable $e) {
                    PostbackFirer::log('[ConversionController.php] IP Conversion Protection History deletion failed: ' . $e->getMessage());
                }
            }

            // ── 3. Fire postbacks for the new status ─────────────────────
            // Only fire when the status actually changed — no duplicate fires.
            if ($newStatus !== $oldStatus) {
                try {
                    $convForPostback = [
                        'conversion_id' => $conv['conversion_id'],
                        'click_id'      => $conv['click_id'],
                        'offer_id'      => $conv['offer_id'],
                        'affiliate_id'  => $conv['affiliate_id'],
                        'payout'        => (float)$conv['payout'],
                        'revenue'       => (float)($conv['revenue'] ?? $conv['payout']),
                        'status'        => $newStatus,
                        'source'        => $conv['source'] ?? '',
                        'sub1'          => $conv['sub1'] ?? '',
                        'sub2'          => $conv['sub2'] ?? '',
                        'sub3'          => $conv['sub3'] ?? '',
                        'sub4'          => $conv['sub4'] ?? '',
                        'sub5'          => $conv['sub5'] ?? '',
                        'sub6'          => $conv['sub6'] ?? '',
                    ];

                    // Always mark postback_sent=1 after every admin-triggered status change.
                    // Using markSent=true unconditionally ensures the DB record is updated
                    // even if a previous fire attempt failed (e.g. missing postback_sent_at
                    // column on older installs). Updating when already 1 is a safe no-op.
                    PostbackFirer::fireAll($convForPostback, $newStatus, true);

                    PostbackFirer::log(
                        "[ConversionController] Fired postbacks for {$convId}: "
                        . "{$oldStatus} → {$newStatus}"
                    );
                } catch (\Throwable $pbEx) {
                    PostbackFirer::log(
                        "[ConversionController] PostbackFirer error for {$convId}: "
                        . $pbEx->getMessage()
                    );
                }
            }

            // ── 4. Notify affiliate ───────────────────────────────────────
            try {
                $affUser = Database::fetchOne(
                    "SELECT user_id FROM affiliates WHERE id = ?",
                    [$conv['affiliate_id']]
                );
                if ($affUser) {
                    $statusLabel = ucfirst($newStatus);
                    $notifType   = $newStatus === 'approved' ? 'success' : 'warning';
                    Database::insert('notifications', [
                        'user_id'     => $affUser['user_id'],
                        'target_role' => 'affiliate',
                        'type'        => $notifType,
                        'title'       => "Conversion {$statusLabel}",
                        'message'     => "A conversion has been {$newStatus}. Payout: $"
                                       . number_format((float)$conv['payout'], 2),
                        'link'        => '/affiliate/reports?tab=conversion',
                    ]);
                }
            } catch (\Throwable $e) {}

            Helpers::flash('success', 'Conversion updated to ' . $newStatus . '.');
        }
    }

    // Honour an optional `redirect_back` so admins approving/rejecting from
    // /admin/reports?tab=conversions land back on the Reports page they came from.
    // Restricted to same-host paths to avoid open-redirect.
    $back = Helpers::postRaw('redirect_back') ?: '/admin/conversions';
    if (!is_string($back) || !str_starts_with($back, '/')) $back = '/admin/conversions';
    Helpers::redirect($back);
}

// ── List conversions ──────────────────────────────────────────────────────
// Ensure fraud_score + postback_sent columns exist before querying them
PostbackFirer::ensurePostbackSentColumn();
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_score`      TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_checked_at` DATETIME         DEFAULT NULL"); } catch (\Throwable $_e) {}
RejectionHelper::ensureSchema();

$status = Helpers::get('status') ?: 'all';
$clickId = Helpers::get('click_id');

// Date-range filter (defaults to current month, matching /admin/reports).
$from = Helpers::get('from') ?: date('Y-m-01');
$to   = Helpers::get('to')   ?: date('Y-m-d');
$dateFrom = date('Y-m-d 00:00:00', strtotime($from));
$dateTo   = date('Y-m-d 23:59:59', strtotime($to));

$whereParts = ['c.converted_at BETWEEN ? AND ?'];
$whereParams = [$dateFrom, $dateTo];
if ($status !== 'all') {
    $whereParts[]  = 'c.status = ?';
    $whereParams[] = $status;
}
if (!empty($clickId)) {
    $whereParts[] = 'c.click_id = ?';
    $whereParams[] = trim($clickId);
}
$where = implode(' AND ', $whereParts);

$conversions = Database::fetchAll(
    "SELECT c.*, o.name as offer_name, o.landing_pages as offer_landing_pages,
            o.landing_page_names as offer_landing_page_names,
            CONCAT(u.first_name,' ',u.last_name) as aff_name,
            af.affiliate_code,
            ck.user_agent      as ck_user_agent,
            ck.device_type     as ck_device_type,
            ck.os              as ck_os,
            ck.referer         as ck_referer,
            ck.landing_page_idx as ck_lp_idx
     FROM conversions c
     LEFT JOIN offers o ON o.id = c.offer_id
     JOIN affiliates af ON af.id = c.affiliate_id
     JOIN users u ON u.id = af.user_id
     LEFT JOIN clicks ck ON ck.click_id = c.click_id
     WHERE {$where}
     ORDER BY c.converted_at DESC
     LIMIT 5000",
    $whereParams
);

$_exportFmt = Helpers::get('export');
if ($_exportFmt === 'csv' || $_exportFmt === 'xls') {
    $headerRow = ['Conversion ID','Click ID','Offer','Affiliate','Aff Code',
                  'Payout','Revenue','Status','Rejection Reason','Rejected At','Device Brand','Device Model','OS Version','Landing Page','Landing Page Name','Referrer','Fraud Score','Country','IP','Postback Sent','Converted At'];
    if ($_exportFmt === 'xls') {
        ExportHelper::beginXls('conversions');
        ExportHelper::xlsHeaderRow($headerRow);
    } else {
        $filename = 'conversions_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        $f = fopen('php://output', 'w');
        fputcsv($f, $headerRow);
    }
    foreach ($conversions as $c) {
        // Resolve device/OS/landing/referrer — prefer stored conversion columns, fall back to click row
        $csvUa    = $c['user_agent'] ?: ($c['ck_user_agent'] ?? '');
        $csvBrand = $c['device_brand'] ?: ucfirst($c['ck_device_type'] ?? '');
        $csvModel = $c['device_model'] ?? '';
        if (($csvBrand === '' || $csvModel === '') && $csvUa !== '') {
            if (preg_match('/Android[^;]*;\s*([^;)]+?)(?:\s+Build\/|\s*[;)])/i', $csvUa, $m)) {
                $raw = trim($m[1]);
                foreach (['Samsung','Xiaomi','Huawei','OnePlus','OPPO','Vivo','Realme','Motorola','Nokia','Sony','LG','HTC','Asus','Google','Pixel','Lenovo','ZTE','Alcatel','TCL','Honor'] as $b) {
                    if (stripos($raw, $b) === 0) { $csvBrand = $b; $csvModel = trim(substr($raw, strlen($b))) ?: $raw; break; }
                }
                if ($csvModel === '') { $csvBrand = 'Android'; $csvModel = $raw; }
            } elseif (stripos($csvUa, 'iPhone') !== false)  { $csvBrand = 'Apple'; $csvModel = 'iPhone'; }
            elseif (stripos($csvUa, 'iPad') !== false)      { $csvBrand = 'Apple'; $csvModel = 'iPad'; }
            elseif (stripos($csvUa, 'Macintosh') !== false) { $csvBrand = 'Apple'; $csvModel = 'Mac'; }
            elseif (stripos($csvUa, 'Windows') !== false)   { $csvBrand = 'PC';    $csvModel = 'Windows'; }
        }
        $csvOs = $c['os_version'] ?? '';
        if ($csvOs === '' && $csvUa !== '') {
            if      (preg_match('/Android\s+([\d.]+)/i', $csvUa, $ov))      { $csvOs = 'Android ' . $ov[1]; }
            elseif  (preg_match('/iPhone OS ([\d_]+)/i', $csvUa, $ov))      { $csvOs = 'iOS ' . str_replace('_', '.', $ov[1]); }
            elseif  (preg_match('/iPad.*?OS ([\d_]+)/i', $csvUa, $ov))      { $csvOs = 'iPadOS ' . str_replace('_', '.', $ov[1]); }
            elseif  (preg_match('/Windows NT ([\d.]+)/i', $csvUa, $ov))     { $ntM = ['10.0'=>'10/11','6.3'=>'8.1','6.2'=>'8','6.1'=>'7']; $csvOs = 'Windows ' . ($ntM[$ov[1]] ?? $ov[1]); }
            elseif  (preg_match('/Mac OS X ([\d_]+)/i', $csvUa, $ov))       { $csvOs = 'macOS ' . str_replace('_', '.', $ov[1]); }
        }
        if ($csvOs === '' && !empty($c['ck_os'])) { $csvOs = $c['ck_os']; }
        $csvLp  = $c['landing_page'] ?? '';
        $csvLpName = '';
        if (isset($c['ck_lp_idx']) && $c['ck_lp_idx'] !== null) {
            $lpArr  = !empty($c['offer_landing_pages'])      ? json_decode($c['offer_landing_pages'], true)      : null;
            $lpNArr = !empty($c['offer_landing_page_names']) ? json_decode($c['offer_landing_page_names'], true) : null;
            $_idx   = (int)$c['ck_lp_idx'];
            if ($csvLp === '' && is_array($lpArr) && isset($lpArr[$_idx])) { $csvLp = $lpArr[$_idx]; }
            if (is_array($lpNArr) && isset($lpNArr[$_idx])) { $csvLpName = $lpNArr[$_idx]; }
        }
        $csvRef = $c['referrer'] ?: ($c['ck_referer'] ?? '');
        $rowCells = [
            $c['conversion_id'], $c['click_id'], $c['offer_name'],
            $c['aff_name'], $c['affiliate_code'],
            $c['payout'], $c['revenue'], $c['status'],
            $c['rejection_reason'] ?? '', $c['rejected_at'] ?? '',
            $csvBrand, $csvModel, $csvOs, $csvLp, $csvLpName, $csvRef,
            !empty($c['fraud_checked_at']) ? (int)($c['fraud_score'] ?? 0) : 'pending',
            $c['country'] ?? '', $c['ip_address'],
            $c['postback_sent'] ? 'Yes' : 'No',
            $c['converted_at'],
        ];
        if ($_exportFmt === 'xls') ExportHelper::xlsRow($rowCells);
        else                       fputcsv($f, $rowCells);
    }
    if ($_exportFmt === 'xls') ExportHelper::endXls();
    else                       fclose($f);
    exit;
}

require BASE_PATH . '/views/admin/conversions.php';
