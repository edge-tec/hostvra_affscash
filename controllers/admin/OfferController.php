<?php
Auth::check('admin');
$pageTitle = 'Offers';

// Schema auto-migration for offer_type, banner, iframe fields
try { Database::query("ALTER TABLE offers ADD COLUMN offer_type VARCHAR(32) DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN banner_urls TEXT DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN iframe_url VARCHAR(512) DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN iframe_width INT DEFAULT 728"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN iframe_height INT DEFAULT 90"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers MODIFY COLUMN payout_type VARCHAR(32) NOT NULL DEFAULT 'CPA'"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN offer_image VARCHAR(512) DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN require_approval TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}
// Per-landing-page friendly names — JSON array aligned by index with `landing_pages`.
// Backwards compatible: when this column is empty/null, existing readers fall back to the URL.
try { Database::query("ALTER TABLE offers ADD COLUMN landing_page_names TEXT DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offers MODIFY COLUMN status ENUM('active','paused','expired','pending','deleted') DEFAULT 'pending'"); } catch (Exception $e) {}

// offer_links table
try { Database::query("CREATE TABLE IF NOT EXISTS `offer_links` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `offer_id`     INT UNSIGNED NOT NULL,
  `device_type`  ENUM('desktop','mobile','tablet','all') NOT NULL DEFAULT 'all',
  `geo_country`  VARCHAR(2) NOT NULL DEFAULT '',
  `label`        VARCHAR(100) DEFAULT '',
  `offer_url`    TEXT NOT NULL,
  `payout_rate`  DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `revenue_rate` DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `status`       ENUM('active','inactive') DEFAULT 'active',
  `sort_order`   TINYINT UNSIGNED DEFAULT 0,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_offer_id` (`offer_id`),
  INDEX `idx_offer_device` (`offer_id`,`device_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE offer_links ADD COLUMN geo_country VARCHAR(2) NOT NULL DEFAULT ''"); } catch (Exception $e) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'edit' : 'index');

if ($action === 'index') {
    $qFilter         = Helpers::get('q');
    $catFilter       = Helpers::get('category');
    $typeFilter      = Helpers::get('payout_type');
    $statusFilter    = Helpers::get('status_filter');
    $offerTypeFilter = Helpers::get('offer_type');
    $countryFilter   = Helpers::get('country');
    $deviceFilter    = Helpers::get('device');
    $idFilter        = (int)Helpers::get('offer_id');
    $accessFilter    = Helpers::get('access_filter') ?: '';

    $whereFilters = ['1=1'];
    $filterParams = [];
    if ($idFilter > 0)    { $whereFilters[] = "o.id = ?";                                    $filterParams[] = $idFilter; }
    if ($qFilter)         { $whereFilters[] = "o.name LIKE ?";                               $filterParams[] = '%' . $qFilter . '%'; }
    if ($catFilter)       { $whereFilters[] = "o.category = ?";                              $filterParams[] = $catFilter; }
    if ($typeFilter)      { $whereFilters[] = "o.payout_type = ?";                           $filterParams[] = $typeFilter; }
    if ($statusFilter)    { $whereFilters[] = "o.status = ?";                                $filterParams[] = $statusFilter; }
    if ($offerTypeFilter) { $whereFilters[] = "o.offer_type = ?";                            $filterParams[] = $offerTypeFilter; }
    if ($countryFilter)   { $whereFilters[] = "JSON_CONTAINS(o.geo_targeting, JSON_QUOTE(?))"; $filterParams[] = $countryFilter; }
    if ($deviceFilter)    { $whereFilters[] = "JSON_CONTAINS(o.device_targeting, JSON_QUOTE(?))"; $filterParams[] = $deviceFilter; }
    switch ($accessFilter) {
        case 'active':     $whereFilters[] = "o.status = 'active'"; break;
        case 'inactive':   $whereFilters[] = "o.status != 'active'"; break;
        case 'request':    $whereFilters[] = "o.require_approval = 1"; break;
        case 'all_access': $whereFilters[] = "o.require_approval = 0 AND COALESCE(o.visibility,'public') != 'private'"; break;
    }
    $filterWhere = implode(' AND ', $whereFilters);

    // Exclude in-house offers from the regular offers list (they have their own section)
    $whereFilters[] = "(o.is_inhouse IS NULL OR o.is_inhouse = 0)";
    $filterWhere = implode(' AND ', $whereFilters);

    $offers = Database::fetchAll(
        "SELECT o.*, COALESCE(CONCAT(u.first_name,' ',u.last_name), 'In-House') as adv_name
         FROM offers o
         LEFT JOIN advertisers adv ON adv.id=o.advertiser_id
         LEFT JOIN users u ON u.id=adv.user_id
         WHERE $filterWhere ORDER BY o.created_at DESC",
        $filterParams
    );

    $categories = Database::fetchAll("SELECT DISTINCT category FROM offers WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $offerTypes  = Database::fetchAll("SELECT DISTINCT offer_type FROM offers WHERE offer_type IS NOT NULL AND offer_type != '' ORDER BY offer_type");
    $allAffiliates = Database::fetchAll(
        "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates af JOIN users u ON u.id=af.user_id WHERE u.status='active' ORDER BY name"
    );
    $appUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');

    if (Helpers::get('export') === 'csv') {
        $filename = 'offers_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['ID','Name','Advertiser','Payout Type','Payout','Revenue','Status','Visibility','Daily Cap','Total Cap','Created']);
        foreach ($offers as $o) {
            fputcsv($f, [$o['id'],$o['name'],$o['adv_name'],$o['payout_type'],$o['payout_amount'],$o['revenue_amount'],$o['status'],$o['visibility'],$o['daily_cap'],$o['total_cap'],$o['created_at']]);
        }
        fclose($f);
        exit;
    }

    // Pass filter vars to view
    // $qFilter, $catFilter, $typeFilter, $statusFilter, $categories already set above
    require BASE_PATH . '/views/admin/offers/index.php';
}

elseif ($action === 'create') {
    $advertisers = Database::fetchAll(
        "SELECT adv.id, CONCAT(u.first_name,' ',u.last_name,' (',u.company,')') as label FROM advertisers adv JOIN users u ON u.id=adv.user_id WHERE u.status='active'"
    );
    $errors = [];
    $offerLinks = [];

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $name     = Helpers::post('name');
        $advId    = (int)Helpers::postRaw('advertiser_id');
        $payout   = (float)Helpers::postRaw('payout_amount');
        $revenue  = (float)Helpers::postRaw('revenue_amount');
        $type     = Helpers::post('payout_type');
        $status   = Helpers::post('status');
        $rawGeos  = $_POST['geo_targeting'] ?? [];
        $geos     = in_array('GLOBAL', $rawGeos) ? [] : array_filter($rawGeos, fn($g) => $g !== 'GLOBAL');
        $devices  = $_POST['device_targeting'] ?? [];
        $dailyCap = (int)Helpers::postRaw('daily_cap');
        $totalCap = (int)Helpers::postRaw('total_cap');
        $visibility = Helpers::post('visibility');
        $description = Helpers::postRaw('description');

        // Collect all landing page URLs; filter empty ones. Track original indexes so we
        // can pair each URL with its (optional) friendly name from landing_page_names[].
        $_rawUrlsAll  = array_map('trim', $_POST['landing_pages'] ?? []);
        $_rawNamesAll = array_map('trim', $_POST['landing_page_names'] ?? []);
        $rawUrls = []; $rawNames = [];
        foreach ($_rawUrlsAll as $_i => $_u) {
            if ($_u === '') continue;
            $rawUrls[]  = $_u;
            $rawNames[] = $_rawNamesAll[$_i] ?? '';
        }
        $offerUrl = $rawUrls[0] ?? '';
        $landingPages     = count($rawUrls) > 1 ? json_encode($rawUrls) : null;
        // Always persist names for any non-empty entry so single-LP offers can also have a label.
        $landingPageNames = (count($rawUrls) > 0 && array_filter($rawNames, 'strlen'))
            ? json_encode($rawNames) : null;

        // Country payouts
        $cpCountries = $_POST['country_payout_country'] ?? [];
        $cpAmounts   = $_POST['country_payout_amount']   ?? [];
        $countryPayouts = [];
        foreach ($cpCountries as $i => $cc) {
            $cc = strtoupper(trim($cc));
            $amt = (float)($cpAmounts[$i] ?? 0);
            if (strlen($cc) === 2 && $amt > 0) {
                $countryPayouts[] = ['country' => $cc, 'payout' => $amt];
            }
        }

        // Device payouts
        $devicePayouts = [];
        foreach (['desktop','mobile','tablet'] as $dev) {
            $amt = (float)Helpers::postRaw('device_payout_' . $dev);
            if ($amt > 0) $devicePayouts[$dev] = $amt;
        }

        $convOptimize = isset($_POST['conversion_optimize']) ? 1 : 0;
        $autoPauseCr        = max(0, min(100, (float)Helpers::postRaw('auto_pause_cr')));
        $autoPauseMinClicks = max(10, (int)Helpers::postRaw('auto_pause_min_clicks') ?: 100);

        // Offer type & creative assets
        $offerType  = Helpers::post('offer_type');
        $iframeUrl  = Helpers::postRaw('iframe_url');
        $iframeW    = max(1, (int)Helpers::postRaw('iframe_width') ?: 728);
        $iframeH    = max(1, (int)Helpers::postRaw('iframe_height') ?: 90);
        $bannerSizes = ['728x90','300x250','160x600','320x50','300x600','970x90'];
        $bannerData = [];
        foreach ($bannerSizes as $sz) {
            $v = trim(Helpers::postRaw('banner_' . str_replace('x','_',$sz)) ?: '');
            if ($v) $bannerData[$sz] = $v;
        }

        // Offer image upload
        $offerImage = null;
        if (!empty($_FILES['offer_image']['tmp_name'])) {
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['offer_image']['tmp_name']);
            finfo_close($finfo);
            if (in_array($mime, $allowed)) {
                $uploadDir = BASE_PATH . '/assets/uploads/offers/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($_FILES['offer_image']['name'], PATHINFO_EXTENSION);
                $filename = 'offer_' . time() . '_' . rand(1000,9999) . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['offer_image']['tmp_name'], $uploadDir . $filename)) {
                    $offerImage = '/assets/uploads/offers/' . $filename;
                }
            }
        }

        if (!$name) $errors[] = 'Offer name is required.';
        if ($name && Database::fetchOne("SELECT id FROM offers WHERE name=? AND status != 'deleted'", [$name])) {
            $errors[] = 'An offer with this name already exists. Please choose a different name.';
        }
        if (!$advId) $errors[] = 'Advertiser is required.';
        if (!$offerUrl) $errors[] = 'At least one Offer URL is required.';

        if (empty($errors)) {
            $id = Database::insert('offers', [
                'advertiser_id'      => $advId,
                'name'               => $name,
                'description'        => $description,
                'offer_url'          => $offerUrl,
                'landing_pages'      => $landingPages,
                'landing_page_names' => $landingPageNames,
                'preview_url'        => Helpers::postRaw('preview_url'),
                'category'           => Helpers::post('category'),
                'geo_targeting'      => $geos ? json_encode($geos) : null,
                'device_targeting'   => $devices ? json_encode($devices) : null,
                'offer_type'         => $offerType ?: null,
                'payout_type'        => $type,
                'payout_amount'      => $payout,
                'revenue_amount'     => $revenue,
                'country_payouts'    => $countryPayouts ? json_encode($countryPayouts) : null,
                'device_payouts'     => $devicePayouts  ? json_encode($devicePayouts)  : null,
                'conversion_optimize'=> $convOptimize,
                'auto_pause_cr'         => $autoPauseCr,
                'auto_pause_min_clicks' => $autoPauseMinClicks,
                'daily_cap'          => $dailyCap,
                'total_cap'          => $totalCap,
                'status'             => $status,
                'visibility'         => $visibility,
                'tracking_domain'    => Helpers::post('tracking_domain'),
                'require_approval'   => isset($_POST['require_approval']) ? 1 : 0,
                'offer_image'        => $offerImage,
                'terms'              => Helpers::postRaw('terms'),
                'banner_urls'        => $bannerData ? json_encode($bannerData) : null,
                'iframe_url'         => $iframeUrl ?: null,
                'iframe_width'       => $iframeW,
                'iframe_height'      => $iframeH,
                'created_by'         => Auth::id(),
            ]);

            // Save offer links
            _saveOfferLinks($id, $_POST);

            // ── Broadcast: instant email to every active affiliate ──────────
            // Drafts and paused offers stay quiet. The helper guards against
            // double-sends via offers.notify_sent_at, so it's safe to invoke
            // here AND from the edit/quick-toggle paths when status flips to
            // active later.
            $emailedCount = _maybeBroadcastNewOfferEmail((int)$id);

            $successMsg = 'Offer created successfully.';
            if ($emailedCount > 0) {
                $successMsg .= ' Notification email sent to ' . $emailedCount . ' affiliate(s).';
            }
            Helpers::flash('success', $successMsg);
            Helpers::redirect('/admin/offers');
        }
    }
    require BASE_PATH . '/views/admin/offers/create.php';
}

elseif ($action === 'edit' && isset($_GET['id'])) {
    $offerId = (int)$_GET['id'];
    $offer   = Database::fetchOne("SELECT * FROM offers WHERE id=?", [$offerId]);
    if (!$offer) Helpers::redirect('/admin/offers');

    $advertisers = Database::fetchAll(
        "SELECT adv.id, CONCAT(u.first_name,' ',u.last_name) as label FROM advertisers adv JOIN users u ON u.id=adv.user_id"
    );
    $errors = [];
    $offerLinks = Database::fetchAll(
        "SELECT * FROM offer_links WHERE offer_id=? ORDER BY sort_order, id", [$offerId]
    );

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $postAction = Helpers::post('action');

        if ($postAction === 'update') {
            $name        = Helpers::post('name');
            $advId       = (int)Helpers::postRaw('advertiser_id');
            $payout      = (float)Helpers::postRaw('payout_amount');
            $revenue     = (float)Helpers::postRaw('revenue_amount');
            $type        = Helpers::post('payout_type');
            $status      = Helpers::post('status');
            $rawGeos     = $_POST['geo_targeting'] ?? [];
            $geos        = in_array('GLOBAL', $rawGeos) ? [] : array_filter($rawGeos, fn($g) => $g !== 'GLOBAL');
            $devices     = $_POST['device_targeting'] ?? [];
            $dailyCap    = (int)Helpers::postRaw('daily_cap');
            $totalCap    = (int)Helpers::postRaw('total_cap');
            $visibility  = Helpers::post('visibility');
            $description = Helpers::postRaw('description');

            $_rawUrlsAll  = array_map('trim', $_POST['landing_pages'] ?? []);
            $_rawNamesAll = array_map('trim', $_POST['landing_page_names'] ?? []);
            $rawUrls = []; $rawNames = [];
            foreach ($_rawUrlsAll as $_i => $_u) {
                if ($_u === '') continue;
                $rawUrls[]  = $_u;
                $rawNames[] = $_rawNamesAll[$_i] ?? '';
            }
            $offerUrl     = $rawUrls[0] ?? '';
            $landingPages = count($rawUrls) > 1 ? json_encode($rawUrls) : null;
            $landingPageNames = (count($rawUrls) > 0 && array_filter($rawNames, 'strlen'))
                ? json_encode($rawNames) : null;

            // Country payouts
            $cpCountries = $_POST['country_payout_country'] ?? [];
            $cpAmounts   = $_POST['country_payout_amount']   ?? [];
            $countryPayouts = [];
            foreach ($cpCountries as $i => $cc) {
                $cc = strtoupper(trim($cc));
                $amt = (float)($cpAmounts[$i] ?? 0);
                if (strlen($cc) === 2 && $amt > 0) {
                    $countryPayouts[] = ['country' => $cc, 'payout' => $amt];
                }
            }

            // Device payouts
            $devicePayouts = [];
            foreach (['desktop','mobile','tablet'] as $dev) {
                $amt = (float)Helpers::postRaw('device_payout_' . $dev);
                if ($amt > 0) $devicePayouts[$dev] = $amt;
            }

            $convOptimize = isset($_POST['conversion_optimize']) ? 1 : 0;
            $autoPauseCr        = max(0, min(100, (float)Helpers::postRaw('auto_pause_cr')));
            $autoPauseMinClicks = max(10, (int)Helpers::postRaw('auto_pause_min_clicks') ?: 100);

            // Offer type & creative assets
            $offerType  = Helpers::post('offer_type');
            $iframeUrl  = Helpers::postRaw('iframe_url');
            $iframeW    = max(1, (int)Helpers::postRaw('iframe_width') ?: 728);
            $iframeH    = max(1, (int)Helpers::postRaw('iframe_height') ?: 90);
            $bannerSizes = ['728x90','300x250','160x600','320x50','300x600','970x90'];
            $bannerData = [];
            foreach ($bannerSizes as $sz) {
                $v = trim(Helpers::postRaw('banner_' . str_replace('x','_',$sz)) ?: '');
                if ($v) $bannerData[$sz] = $v;
            }

            // Offer image upload (keep existing image if no new one uploaded)
            $offerImage = $offer['offer_image'] ?? null;
            if (!empty($_FILES['offer_image']['tmp_name'])) {
                $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['offer_image']['tmp_name']);
                finfo_close($finfo);
                if (in_array($mime, $allowed)) {
                    $uploadDir = BASE_PATH . '/assets/uploads/offers/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $ext = pathinfo($_FILES['offer_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'offer_' . time() . '_' . rand(1000,9999) . '.' . strtolower($ext);
                    if (move_uploaded_file($_FILES['offer_image']['tmp_name'], $uploadDir . $filename)) {
                        $offerImage = '/assets/uploads/offers/' . $filename;
                    }
                }
            }

            if (!$name) $errors[] = 'Offer name is required.';
            if ($name && Database::fetchOne("SELECT id FROM offers WHERE name=? AND id != ? AND status != 'deleted'", [$name, $offerId])) {
                $errors[] = 'An offer with this name already exists. Please choose a different name.';
            }
            if (!$advId) $errors[] = 'Advertiser is required.';
            if (!$offerUrl) $errors[] = 'At least one Offer URL is required.';

            if (empty($errors)) {
                Database::update('offers', [
                    'name'               => $name,
                    'description'        => $description,
                    'offer_url'          => $offerUrl,
                    'landing_pages'      => $landingPages,
                    'landing_page_names' => $landingPageNames,
                    'preview_url'        => Helpers::postRaw('preview_url'),
                    'category'           => Helpers::post('category'),
                    'geo_targeting'      => $geos ? json_encode($geos) : null,
                    'device_targeting'   => $devices ? json_encode($devices) : null,
                    'offer_type'         => $offerType ?: null,
                    'payout_type'        => $type,
                    'payout_amount'      => $payout,
                    'revenue_amount'     => $revenue,
                    'country_payouts'    => $countryPayouts ? json_encode($countryPayouts) : null,
                    'device_payouts'     => $devicePayouts  ? json_encode($devicePayouts)  : null,
                    'conversion_optimize'=> $convOptimize,
                    'auto_pause_cr'         => $autoPauseCr,
                    'auto_pause_min_clicks' => $autoPauseMinClicks,
                    'daily_cap'          => $dailyCap,
                    'total_cap'          => $totalCap,
                    'status'             => $status,
                    'visibility'         => $visibility,
                    'tracking_domain'    => Helpers::post('tracking_domain'),
                    'require_approval'   => isset($_POST['require_approval']) ? 1 : 0,
                    'offer_image'        => $offerImage,
                    'terms'              => Helpers::postRaw('terms'),
                    'banner_urls'        => $bannerData ? json_encode($bannerData) : null,
                    'iframe_url'         => $iframeUrl ?: null,
                    'iframe_width'       => $iframeW,
                    'iframe_height'      => $iframeH,
                ], 'id=?', [$offerId]);

                // Save offer links (replace all)
                Database::query("DELETE FROM offer_links WHERE offer_id=?", [$offerId]);
                _saveOfferLinks($offerId, $_POST);

                $emailedCount = 0;
                $linkChanged = false;
                $statusChanged = false;

                // Check for offer details/link changes
                $oldOfferUrl = $offer['offer_url'] ?? '';
                $oldLandingPages = $offer['landing_pages'] ?? null;
                $oldName = $offer['name'] ?? '';
                $oldPayout = (float)($offer['payout_amount'] ?? 0);
                $oldDesc = $offer['description'] ?? '';
                $oldDailyCap = $offer['daily_cap'] ?? 0;
                $oldTotalCap = $offer['total_cap'] ?? 0;
                $oldCountryPayouts = $offer['country_payouts'] ?? null;
                $oldDevicePayouts = $offer['device_payouts'] ?? null;
                $oldDeviceTargeting = $offer['device_targeting'] ?? null;
                
                if ($oldOfferUrl !== $offerUrl 
                    || $oldLandingPages !== $landingPages
                    || $oldName !== $name
                    || (float)$payout !== $oldPayout
                    || $oldDesc !== $description
                    || (int)$oldDailyCap !== (int)$dailyCap
                    || (int)$oldTotalCap !== (int)$totalCap
                    || $oldCountryPayouts !== ($countryPayouts ? json_encode($countryPayouts) : null)
                    || $oldDevicePayouts !== ($devicePayouts ? json_encode($devicePayouts) : null)
                    || $oldDeviceTargeting !== ($devices ? json_encode($devices) : null)
                ) {
                    $linkChanged = true;
                    $emailedCount += _broadcastOfferLinkChangeEmail($offerId);
                }

                // Check for status changes
                $oldStatus = $offer['status'] ?? 'paused';
                if ($oldStatus !== $status && $oldStatus !== 'pending' && !empty($oldStatus)) {
                    $statusChanged = true;
                    $emailedCount += _broadcastOfferStatusChangeEmail($offerId, $oldStatus, $status);
                }

                // If this edit flipped the offer to active for the first time,
                // fire the new-offer broadcast now. Idempotent — won't double-send.
                $emailedCount += _maybeBroadcastNewOfferEmail($offerId);
                
                $msg = 'Offer updated successfully.';
                if ($emailedCount > 0) {
                    $msg .= ' Notification emails sent (' . $emailedCount . ').';
                }
                Helpers::flash('success', $msg);
                Helpers::redirect('/admin/offers');
            }

            // Re-fetch offer_links for form re-render on validation error
            $offerLinks = Database::fetchAll(
                "SELECT * FROM offer_links WHERE offer_id=? ORDER BY sort_order, id", [$offerId]
            );

            // Re-fetch offer with submitted values for form re-population
            $offer = array_merge($offer, [
                'name' => $name, 'advertiser_id' => $advId, 'payout_amount' => $payout,
                'revenue_amount' => $revenue, 'payout_type' => $type, 'status' => $status,
                'geo_targeting' => $geos ? json_encode($geos) : null,
                'device_targeting' => $devices ? json_encode($devices) : null,
                'daily_cap' => $dailyCap, 'total_cap' => $totalCap, 'visibility' => $visibility,
                'description' => $description,
                'country_payouts' => $countryPayouts ? json_encode($countryPayouts) : null,
                'device_payouts' => $devicePayouts ? json_encode($devicePayouts) : null,
                'conversion_optimize' => $convOptimize,
                'auto_pause_cr' => $autoPauseCr, 'auto_pause_min_clicks' => $autoPauseMinClicks,
                'offer_type' => $offerType,
                'banner_urls' => $bannerData ? json_encode($bannerData) : null,
                'iframe_url' => $iframeUrl, 'iframe_width' => $iframeW, 'iframe_height' => $iframeH,
            ]);
        } else {
            // Legacy: status-only toggle (e.g. from index quick-action)
            $newStatus = Helpers::post('status');
            if (in_array($newStatus, ['active','paused','expired','pending'])) {
                $oldStatus = $offer['status'] ?? 'paused';
                Database::update('offers', ['status' => $newStatus], 'id=?', [$offerId]);
                
                $emailedCount = 0;
                if ($oldStatus !== $newStatus && $oldStatus !== 'pending' && !empty($oldStatus)) {
                    $emailedCount += _broadcastOfferStatusChangeEmail($offerId, $oldStatus, $newStatus);
                }
                
                // First-time activation? Fire the broadcast.
                $emailedCount += _maybeBroadcastNewOfferEmail($offerId);
                
                $msg = 'Offer status updated.';
                if ($emailedCount > 0) {
                    $msg .= ' Notification emails sent (' . $emailedCount . ').';
                }
                Helpers::flash('success', $msg);
                Helpers::redirect('/admin/offers');
            }
        }
    }

    require BASE_PATH . '/views/admin/offers/create.php'; // reuse form for edit
}

// ── Delete Offer ───────────────────────────────────────────────────────────
elseif ($action === 'delete') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $offerId = (int)Helpers::postRaw('offer_id');
        $offer   = Database::fetchOne("SELECT id, name FROM offers WHERE id=?", [$offerId]);
        if ($offer) {
            // Hard delete: remove completely from the database
            Database::query("DELETE FROM offers WHERE id=?", [$offerId]);
            Database::query("DELETE FROM affiliate_offers WHERE offer_id=?", [$offerId]);
            Database::query("DELETE FROM smartlink_offers WHERE offer_id=?", [$offerId]);
            Database::query("DELETE FROM offer_links WHERE offer_id=?", [$offerId]);
            Helpers::flash('success', 'Offer "' . $offer['name'] . '" has been deleted.');
        } else {
            Helpers::flash('error', 'Offer not found.');
        }
    }
    Helpers::redirect('/admin/offers');
}

// ── Helper: save offer links from POST data ─────────────────────────────────
function _saveOfferLinks(int $offerId, array $post): void {
    $olLabels  = $post['ol_label']   ?? [];
    $olDevices = $post['ol_device']  ?? [];
    $olGeos    = $post['ol_geo']     ?? [];
    $olUrls    = $post['ol_url']     ?? [];
    $olPayouts = $post['ol_payout']  ?? [];
    $olRevs    = $post['ol_revenue'] ?? [];
    $olStats   = $post['ol_status']  ?? [];

    $validDevices = ['desktop', 'mobile', 'tablet', 'all'];
    foreach ($olUrls as $i => $olUrl) {
        $olUrl = trim($olUrl);
        if (!$olUrl) continue;
        $geoCountry = strtoupper(preg_replace('/[^a-zA-Z]/', '', $olGeos[$i] ?? ''));
        $geoCountry = substr($geoCountry, 0, 2);
        Database::insert('offer_links', [
            'offer_id'    => $offerId,
            'device_type' => in_array($olDevices[$i] ?? '', $validDevices) ? $olDevices[$i] : 'all',
            'geo_country' => $geoCountry,
            'label'       => substr(trim($olLabels[$i] ?? ''), 0, 100),
            'offer_url'   => $olUrl,
            'payout_rate' => max(0.0, (float)($olPayouts[$i] ?? 0)),
            'revenue_rate'=> max(0.0, (float)($olRevs[$i]    ?? 0)),
            'status'      => ($olStats[$i] ?? '') === 'inactive' ? 'inactive' : 'active',
            'sort_order'  => (int)$i,
        ]);
    }
}

/**
 * Email every active affiliate the moment a new active offer is published.
 * Returns the number of mails attempted. Failures on individual recipients
 * are swallowed so one bad address can't block the rest of the broadcast.
 */
/**
 * Run the new-offer broadcast at most once per offer, only when the offer is
 * currently active and the admin toggle is on. Idempotent — safe to call on
 * every save path (create, edit, legacy quick-toggle).
 */
function _maybeBroadcastNewOfferEmail(int $offerId): int {
    if ($offerId <= 0) return 0;
    if ((Config::get('config', 'app.new_offer_notify') ?? '1') !== '1') return 0;

    // Ensure the one-time stamp column exists. Idempotent.
    try { Database::query("ALTER TABLE offers ADD COLUMN notify_sent_at DATETIME DEFAULT NULL"); } catch (\Throwable $_) {}

    $row = Database::fetchOne(
        "SELECT status, notify_sent_at FROM offers WHERE id=? LIMIT 1",
        [$offerId]
    );
    if (!$row) return 0;
    if (($row['status'] ?? '') !== 'active') return 0;
    if (!empty($row['notify_sent_at']))       return 0;

    try {
        $sent = _broadcastNewOfferEmail($offerId);
        
        // Push Notification
        try {
            $offerData = Database::fetchOne("SELECT name FROM offers WHERE id=?", [$offerId]);
            if ($offerData) {
                Database::insert('notifications', [
                    'target_role' => 'affiliate',
                    'title' => "New Offer Added!",
                    'message' => "Offer '{$offerData['name']}' has been added.",
                    'link' => '/affiliate/offers'
                ]);
                require_once BASE_PATH . '/core/FirebaseMessaging.php';
                FirebaseMessaging::sendToRole('affiliate', "New Offer Added!", "Offer '{$offerData['name']}' has been added.", ['type' => 'offer', 'offer_id' => (string)$offerId]);
            }
        } catch (\Throwable $e) {}

        // Stamp regardless of recipient count — we attempted the broadcast,
        // so don't retry on every subsequent edit. Zero usually means there
        // were no active affiliates, not a transient failure.
        try {
            Database::update('offers', ['notify_sent_at' => date('Y-m-d H:i:s')], 'id=?', [$offerId]);
        } catch (\Throwable $_) {}
        return $sent;
    } catch (\Throwable $e) {
        // Surface the failure to the server log so admins can diagnose SMTP
        // issues instead of silently seeing zero emails sent.
        error_log('[OfferController] _broadcastNewOfferEmail failed for offer ' . $offerId . ': ' . $e->getMessage());
        return 0;
    }
}

function _broadcastNewOfferEmail(int $offerId): int {
    // Pull the freshly-saved offer (re-read so we have every column).
    $offer = Database::fetchOne(
        "SELECT o.id, o.name, o.description, o.payout_type, o.payout_amount,
                o.revenue_amount, o.category, o.status,
                o.geo_targeting, o.offer_image,
                COALESCE(adv.advertiser_code,'') AS advertiser_code,
                COALESCE(au.company, CONCAT(au.first_name,' ',au.last_name)) AS advertiser_name
         FROM offers o
         LEFT JOIN advertisers adv ON adv.id = o.advertiser_id
         LEFT JOIN users au ON au.id = adv.user_id
         WHERE o.id = ? LIMIT 1",
        [$offerId]
    );
    if (!$offer) return 0;

    $siteName = Config::get('config','app.name') ?? 'AffsCash';
    $appUrl   = rtrim((string)(Config::get('config','app.url') ?: ''), '/');

    // Build "commission information" text — payout type (CPA/CPL/RevShare),
    // base payout, plus revenue if visible to affiliates.
    $payoutType = strtoupper((string)($offer['payout_type'] ?? 'CPA'));
    $payoutAmt  = number_format((float)$offer['payout_amount'], 2);
    $commission = $payoutType . ' &middot; $' . $payoutAmt . ' per conversion';
    $geos       = '';
    if (!empty($offer['geo_targeting'])) {
        $g = json_decode($offer['geo_targeting'], true);
        if (is_array($g) && $g) $geos = implode(', ', array_map('strtoupper', $g));
    }
    $offerName  = (string)$offer['name'];
    $offerDesc  = trim((string)($offer['description'] ?? ''));
    $offerDescH = $offerDesc !== ''
        ? nl2br(htmlspecialchars($offerDesc, ENT_QUOTES, 'UTF-8'))
        : '<em style="color:#94A3B8">No description provided.</em>';
    $statusBadge = ($offer['status'] === 'active') ? 'Active &middot; New' : ucfirst((string)$offer['status']);
    $category    = htmlspecialchars((string)($offer['category'] ?? ''), ENT_QUOTES, 'UTF-8');
    $offersUrl   = $appUrl . '/affiliate/offers';

    // Fetch all active affiliates — must have a verified-active user account.
    try {
        $recipients = Database::fetchAll(
            "SELECT DISTINCT u.email, u.first_name, u.last_name
             FROM users u
             INNER JOIN affiliates af ON af.user_id = u.id
             WHERE u.role = 'affiliate' AND u.status = 'active' AND u.email IS NOT NULL AND u.email <> ''"
        );
    } catch (\Throwable $e) {
        error_log('[OfferController] new-offer recipients query failed: ' . $e->getMessage());
        return 0;
    }

    $sent = 0; $failed = 0;
    foreach ($recipients as $r) {
        $name = trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''));
        if ($name === '') $name = 'Affiliate';
        
        $vars = [
            'name'            => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'offer_name'      => htmlspecialchars($offerName, ENT_QUOTES, 'UTF-8'),
            'site_name'       => htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'),
            'advertiser_name' => htmlspecialchars((string)$offer['advertiser_name'], ENT_QUOTES, 'UTF-8'),
            'category'        => $category, // already escaped
            'is_category'     => $category !== '',
            'geos'            => htmlspecialchars($geos, ENT_QUOTES, 'UTF-8'),
            'is_geos'         => $geos !== '',
            'commission'      => $commission, // contains HTML
            'status_badge'    => $statusBadge, // contains HTML
            'offer_desc_html' => $offerDescH, // contains HTML
            'offers_url'      => htmlspecialchars($offersUrl, ENT_QUOTES, 'UTF-8'),
            'app_url'         => htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8')
        ];

        try {
            if (Mailer::sendEvent($r['email'], $name, 'offer_new', $vars)) {
                $sent++;
            } else {
                $failed++;
                error_log('[OfferController] new-offer email NOT sent to ' . $r['email'] . ' — Mailer::sendEvent returned false (check SMTP config in Settings → Email/SMTP)');
            }
        } catch (\Throwable $e) {
            $failed++;
            error_log('[OfferController] new-offer email error for ' . $r['email'] . ': ' . $e->getMessage());
        }
    }
    error_log('[OfferController] new-offer broadcast for offer ' . $offerId . ': ' . $sent . ' sent, ' . $failed . ' failed, ' . count($recipients) . ' recipients');
    return $sent;
}

function _broadcastOfferStatusChangeEmail(int $offerId, string $oldStatus, string $newStatus): int {
    if ((Config::get('config', 'app.offer_status_notify') ?? '0') !== '1') return 0;
    if ($oldStatus === $newStatus) return 0;

    $offer = Database::fetchOne(
        "SELECT o.id, o.name, o.category, COALESCE(au.company, CONCAT(au.first_name,' ',au.last_name)) AS advertiser_name
         FROM offers o
         LEFT JOIN advertisers adv ON adv.id = o.advertiser_id
         LEFT JOIN users au ON au.id = adv.user_id
         WHERE o.id = ? LIMIT 1",
        [$offerId]
    );
    if (!$offer) return 0;

    $siteName = Config::get('config','app.name') ?? 'AffsCash';
    $appUrl   = rtrim((string)(Config::get('config','app.url') ?: ''), '/');
    $offersUrl= $appUrl . '/affiliate/offers';
    $offerName= (string)$offer['name'];

    try {
        $recipients = Database::fetchAll(
            "SELECT DISTINCT u.email, u.first_name, u.last_name
             FROM users u
             INNER JOIN affiliates af ON af.user_id = u.id
             WHERE u.role = 'affiliate' AND u.status = 'active' AND u.email IS NOT NULL AND u.email <> ''"
        );
    } catch (\Throwable $e) { return 0; }

    $sent = 0;
    foreach ($recipients as $r) {
        $name = trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''));
        if ($name === '') $name = 'Affiliate';
        
        $vars = [
            'name'       => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'offer_name' => htmlspecialchars($offerName, ENT_QUOTES, 'UTF-8'),
            'site_name'  => htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'),
            'old_status' => htmlspecialchars(ucfirst($oldStatus), ENT_QUOTES, 'UTF-8'),
            'new_status' => htmlspecialchars(ucfirst($newStatus), ENT_QUOTES, 'UTF-8'),
            'offers_url' => htmlspecialchars($offersUrl, ENT_QUOTES, 'UTF-8'),
            'app_url'    => htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8')
        ];

        try { if (Mailer::sendEvent($r['email'], $name, 'offer_status', $vars)) $sent++; } catch (\Throwable $e) {}
    }
    return $sent;
}

function _broadcastOfferLinkChangeEmail(int $offerId): int {
    if ((Config::get('config', 'app.offer_link_notify') ?? '0') !== '1') return 0;

    $offer = Database::fetchOne(
        "SELECT o.id, o.name, o.category
         FROM offers o
         WHERE o.id = ? LIMIT 1",
        [$offerId]
    );
    if (!$offer) return 0;

    $siteName = Config::get('config','app.name') ?? 'AffsCash';
    $appUrl   = rtrim((string)(Config::get('config','app.url') ?: ''), '/');
    $offersUrl= $appUrl . '/affiliate/offers';
    $offerName= (string)$offer['name'];

    try {
        $recipients = Database::fetchAll(
            "SELECT DISTINCT u.email, u.first_name, u.last_name
             FROM users u
             INNER JOIN affiliates af ON af.user_id = u.id
             WHERE u.role = 'affiliate' AND u.status = 'active' AND u.email IS NOT NULL AND u.email <> ''"
        );
    } catch (\Throwable $e) { return 0; }

    $sent = 0;
    foreach ($recipients as $r) {
        $name = trim((string)($r['first_name'] ?? '') . ' ' . (string)($r['last_name'] ?? ''));
        if ($name === '') $name = 'Affiliate';
        
        $vars = [
            'name'       => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'offer_name' => htmlspecialchars($offerName, ENT_QUOTES, 'UTF-8'),
            'site_name'  => htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'),
            'offers_url' => htmlspecialchars($offersUrl, ENT_QUOTES, 'UTF-8'),
            'app_url'    => htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8')
        ];

        try { if (Mailer::sendEvent($r['email'], $name, 'offer_link', $vars)) $sent++; } catch (\Throwable $e) {}
    }
    return $sent;
}
