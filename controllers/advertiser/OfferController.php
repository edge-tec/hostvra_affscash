<?php
Auth::check('advertiser');
$pageTitle = 'My Offers';
$advId = Auth::advertiserId();
AdvBudget::ensureSchema();
// Budget exemption still respected for admin-exempted advertisers.
$budgetRequired = !AdvBudget::isBudgetExempt((int)$advId);

$action = Helpers::get('action') ?: 'index';

if ($action === 'index') {
    $offers = Database::fetchAll("SELECT * FROM offers WHERE advertiser_id=? ORDER BY created_at DESC", [$advId]);
    require BASE_PATH . '/views/advertiser/offers/index.php';
}

elseif ($action === 'create') {
    $errors = [];

    // ── Balance gate: advertiser must have funds before creating any offer ──
    $advRow = Database::fetchOne("SELECT balance FROM advertisers WHERE id=?", [$advId]);
    $advBalance = (float)($advRow['balance'] ?? 0);
    if ($budgetRequired && $advBalance <= 0) {
        Helpers::flash('error', 'You must add a balance to your account before creating an offer. Please top up your account first.');
        Helpers::redirect('/advertiser/billing/top-up');
    }

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $name     = Helpers::post('name');
        $offerUrl = Helpers::postRaw('offer_url');
        $payout   = (float)Helpers::postRaw('payout_amount');
        $revenue  = (float)Helpers::postRaw('revenue_amount');
        $type     = Helpers::post('payout_type');
        $rawGeos  = $_POST['geo_targeting'] ?? [];
        $geos     = in_array('GLOBAL', $rawGeos) ? [] : array_filter($rawGeos, fn($g) => $g !== 'GLOBAL');

        // Budget — always required (unless admin exempts the advertiser).
        $budgetTotalRaw = trim((string)Helpers::postRaw('budget_total') ?? '');
        $budgetTotal    = $budgetTotalRaw !== '' ? max(0, (float)$budgetTotalRaw) : null;
        if ($budgetRequired) {
            if ($budgetTotal === null || $budgetTotal <= 0) {
                $errors[] = 'Offer budget is required and must be greater than zero.';
            } elseif ($budgetTotal > $advBalance) {
                $errors[] = 'Offer budget ($' . number_format($budgetTotal, 2) . ') cannot exceed your available balance ($' . number_format($advBalance, 2) . ').';
            }
        }

        if (!$name) $errors[] = 'Offer name is required.';
        if (!$offerUrl) $errors[] = 'Offer URL is required.';

        // ── Advanced features ─────────────────────────────────────────────
        // Device targeting (Mobile / Desktop / Tablet checkboxes).
        $rawDevices = $_POST['device_targeting'] ?? [];
        $devices    = array_values(array_intersect(['desktop','mobile','tablet'], (array)$rawDevices));

        // Multiple landing pages — paired with optional friendly names.
        $rawLpUrls  = $_POST['landing_pages']       ?? [];
        $rawLpNames = $_POST['landing_page_names']  ?? [];
        $lpUrls = []; $lpNames = [];
        foreach ((array)$rawLpUrls as $i => $u) {
            $u = trim((string)$u);
            if ($u === '') continue;
            $lpUrls[]  = $u;
            $lpNames[] = trim((string)($rawLpNames[$i] ?? ''));
        }

        // Country-specific payouts: [{country: 'US', payout: 5.00}, ...]
        $cpCountries = $_POST['country_payout_country'] ?? [];
        $cpAmounts   = $_POST['country_payout_amount']  ?? [];
        $countryPayouts = [];
        foreach ((array)$cpCountries as $i => $cc) {
            $cc = strtoupper(trim((string)$cc));
            $amt = (float)($cpAmounts[$i] ?? 0);
            if ($cc !== '' && $amt > 0) $countryPayouts[] = ['country' => $cc, 'payout' => $amt];
        }

        // Device-specific payouts (one fixed-name input per device).
        $devicePayouts = [];
        foreach (['desktop','mobile','tablet'] as $dv) {
            $amt = (float)Helpers::postRaw('device_payout_' . $dv);
            if ($amt > 0) $devicePayouts[$dv] = $amt;
        }

        // Caps — Daily Conversion Cap + Total Conversion Cap.
        $dailyCap = max(0, (int)Helpers::postRaw('daily_cap'));
        $totalCap = max(0, (int)Helpers::postRaw('total_cap'));

        // Smart routing rows for offer_links (device + GEO → URL + payout).
        $smartLabels  = $_POST['ol_label']    ?? [];
        $smartDevices = $_POST['ol_device']   ?? [];
        $smartGeos    = $_POST['ol_geo']      ?? [];
        $smartUrls    = $_POST['ol_url']      ?? [];
        $smartPayouts = $_POST['ol_payout']   ?? [];
        $smartRoutes  = [];
        foreach ((array)$smartUrls as $i => $u) {
            $u = trim((string)$u);
            if ($u === '') continue;
            $dv = $smartDevices[$i] ?? 'all';
            if (!in_array($dv, ['all','desktop','mobile','tablet'], true)) $dv = 'all';
            $smartRoutes[] = [
                'label'   => trim((string)($smartLabels[$i] ?? '')),
                'device'  => $dv,
                'geo'     => strtoupper(substr(trim((string)($smartGeos[$i] ?? '')), 0, 2)),
                'url'     => $u,
                'payout'  => (float)($smartPayouts[$i] ?? 0),
            ];
        }

        if (!$errors) {
            $offerInsert = [
                'advertiser_id'   => $advId,
                'name'            => $name,
                'description'     => Helpers::postRaw('description'),
                'offer_url'       => $offerUrl,
                'preview_url'     => Helpers::postRaw('preview_url'),
                'category'        => Helpers::post('category'),
                'geo_targeting'   => $geos ? json_encode($geos) : null,
                'device_targeting'=> $devices ? json_encode($devices) : null,
                'landing_pages'   => $lpUrls    ? json_encode($lpUrls)    : null,
                'country_payouts' => $countryPayouts ? json_encode($countryPayouts) : null,
                'device_payouts'  => $devicePayouts  ? json_encode($devicePayouts)  : null,
                'daily_cap'       => $dailyCap,
                'total_cap'       => $totalCap,
                'payout_type'     => $type,
                'payout_amount'   => $payout,
                'revenue_amount'  => $revenue,
                'budget_total'    => $budgetTotal,
                'budget_spent'    => 0,
                'status'          => 'pending', // Pending admin approval
                'visibility'      => 'public',
                'created_by'      => Auth::id(),
            ];

            // landing_page_names is a newer column — insert it only if it exists
            // so the form still works on installs that haven't migrated yet.
            try {
                $hasLpNames = Database::fetchOne("SHOW COLUMNS FROM offers LIKE 'landing_page_names'");
                if ($hasLpNames) $offerInsert['landing_page_names'] = $lpNames ? json_encode($lpNames) : null;
            } catch (\Throwable $_) {}

            $newOfferId = Database::insert('offers', $offerInsert);

            // Smart-routing rules: one offer_links row per device/GEO bucket.
            if ($newOfferId && !empty($smartRoutes)) {
                foreach ($smartRoutes as $sort => $row) {
                    try {
                        Database::insert('offer_links', [
                            'offer_id'    => $newOfferId,
                            'device_type' => $row['device'],
                            'geo_country' => $row['geo'],
                            'label'       => $row['label'],
                            'offer_url'   => $row['url'],
                            'payout_rate' => $row['payout'],
                            'revenue_rate'=> 0,
                            'status'      => 'active',
                            'sort_order'  => $sort,
                        ]);
                    } catch (\Throwable $_) {}
                }
            }

            Database::insert('notifications', [
                'user_id'     => null,
                'target_role' => 'admin',
                'type'        => 'info',
                'title'       => 'New Offer Submitted',
                'message'     => 'An advertiser submitted a new offer: ' . $name,
                'link'        => '/admin/offers',
            ]);

            Helpers::flash('success', 'Offer submitted for admin review.');
            Helpers::redirect('/advertiser/offers');
        }
    }
    require BASE_PATH . '/views/advertiser/offers/create.php';
}
