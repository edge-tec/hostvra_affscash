<?php
Auth::check('admin');
$pageTitle = 'In-House Offers';

// ── Schema migrations ─────────────────────────────────────────────────────────
// Allow advertiser_id to be NULL for in-house offers (offers + conversions tables)
try { Database::query("ALTER TABLE offers MODIFY COLUMN advertiser_id INT UNSIGNED NULL DEFAULT NULL"); } catch (\Throwable $e) {}
// Allow NULL advertiser_id in conversions so in-house offer postbacks can INSERT successfully
try { Database::query("ALTER TABLE `conversions` MODIFY COLUMN `advertiser_id` INT UNSIGNED NULL DEFAULT NULL"); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `hide_reason` VARCHAR(500) NOT NULL DEFAULT ''"); } catch (\Throwable $e) {}
// Add is_inhouse flag
try { Database::query("ALTER TABLE offers ADD COLUMN is_inhouse TINYINT(1) NOT NULL DEFAULT 0"); } catch (\Throwable $e) {}
// payout_type must accept varchar for inhouse
try { Database::query("ALTER TABLE offers MODIFY COLUMN payout_type VARCHAR(32) NOT NULL DEFAULT 'CPA'"); } catch (\Throwable $e) {}
// Ensure landing_pages column exists on offers
try { Database::query("ALTER TABLE offers ADD COLUMN landing_pages TEXT NULL DEFAULT NULL"); } catch (\Throwable $e) {}
// Offer type for in-house offers
try { Database::query("ALTER TABLE offers ADD COLUMN offer_type VARCHAR(50) NOT NULL DEFAULT ''"); } catch (\Throwable $e) {}
// Ensure offer_links table exists (copied from OfferController)
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
  INDEX `idx_offer_id` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); } catch (\Throwable $e) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'edit' : 'index');
$appUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');

// ── INDEX + EXPORT ────────────────────────────────────────────────────────────
if ($action === 'index') {
    $qFilter         = Helpers::get('q');
    $statusFilter    = Helpers::get('status_filter');
    $offerTypeFilter = Helpers::get('offer_type_filter');
    $idFilter        = (int)Helpers::get('offer_id');
    $accessFilter    = Helpers::get('access_filter') ?: '';
    $isExport        = Helpers::get('export') === 'csv';

    $where  = ["o.is_inhouse = 1", "o.status != 'deleted'"];
    $params = [];
    if ($idFilter > 0)    { $where[] = "o.id = ?";          $params[] = $idFilter; }
    if ($qFilter)         { $where[] = "o.name LIKE ?";     $params[] = '%'.$qFilter.'%'; }
    if ($statusFilter)    { $where[] = "o.status = ?";      $params[] = $statusFilter; }
    if ($offerTypeFilter) { $where[] = "o.offer_type = ?";  $params[] = $offerTypeFilter; }
    switch ($accessFilter) {
        case 'active':     $where[] = "o.status = 'active'"; break;
        case 'inactive':   $where[] = "o.status != 'active'"; break;
        case 'request':    $where[] = "o.require_approval = 1"; break;
        case 'all_access': $where[] = "o.require_approval = 0"; break;
    }
    $whereStr = implode(' AND ', $where);

    $offers = Database::fetchAll(
        "SELECT o.*,
                (SELECT COUNT(*) FROM affiliate_offers ao WHERE ao.offer_id=o.id AND ao.status='approved') as aff_count,
                (SELECT COUNT(*) FROM clicks c WHERE c.offer_id=o.id AND DATE(c.clicked_at)=CURDATE()) as today_clicks,
                (SELECT COUNT(*) FROM conversions cv WHERE cv.offer_id=o.id AND DATE(cv.converted_at)=CURDATE()) as today_convs
         FROM offers o
         WHERE $whereStr
         ORDER BY o.created_at DESC",
        $params
    );

    // ── CSV Export ────────────────────────────────────────────────────────────
    if ($isExport) {
        $filename = 'inhouse-offers-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        $f = fopen('php://output', 'w');
        // BOM for Excel UTF-8 compatibility
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, [
            'ID', 'Name', 'Offer Type', 'Payout Type', 'Payout ($)', 'Revenue ($)',
            'Status', 'Visibility', 'Require Approval',
            'Category', 'Daily Cap', 'Total Cap',
            'GEO Targeting', 'Device Targeting',
            'Offer URL', 'Description',
            'Approved Affiliates', 'Today Clicks', 'Today Conversions',
            'Tracking Link', 'Created At',
        ]);
        foreach ($offers as $o) {
            $geos = $o['geo_targeting']    ? implode(', ', json_decode($o['geo_targeting'],    true) ?: []) : 'All';
            $devs = $o['device_targeting'] ? implode(', ', json_decode($o['device_targeting'], true) ?: []) : 'All';
            fputcsv($f, [
                $o['id'],
                $o['name'],
                $o['offer_type'] ?: '',
                $o['payout_type'],
                number_format((float)$o['payout_amount'],  4, '.', ''),
                number_format((float)$o['revenue_amount'], 4, '.', ''),
                $o['status'],
                $o['visibility'],
                $o['require_approval'] ? 'Yes' : 'No',
                $o['category'] ?: '',
                $o['daily_cap'] ?: 0,
                $o['total_cap'] ?: 0,
                $geos,
                $devs,
                $o['offer_url'],
                strip_tags($o['description'] ?? ''),
                (int)$o['aff_count'],
                (int)$o['today_clicks'],
                (int)$o['today_convs'],
                Helpers::trackingUrl() . '/click/' . $o['id'] . '?aff={AFFILIATE_CODE}',
                $o['created_at'],
            ]);
        }
        fclose($f);
        exit;
    }

    require BASE_PATH . '/views/admin/inhouse/index.php';
}

// ── CREATE ────────────────────────────────────────────────────────────────────
elseif ($action === 'create') {
    $errors = [];
    $offer  = null;

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        [$errors, $offerId] = _ihSave(null);
        if (empty($errors)) {
            Helpers::flash('success', 'In-House offer created successfully.');
            Helpers::redirect('/admin/inhouse-offers');
        }
    }

    require BASE_PATH . '/views/admin/inhouse/form.php';
}

// ── EDIT ──────────────────────────────────────────────────────────────────────
elseif ($action === 'edit' && isset($_GET['id'])) {
    $offerId = (int)$_GET['id'];
    $offer   = Database::fetchOne("SELECT * FROM offers WHERE id=? AND is_inhouse=1", [$offerId]);
    if (!$offer) Helpers::redirect('/admin/inhouse-offers');

    $errors = [];

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        [$errors, $offerId] = _ihSave($offerId);
        if (empty($errors)) {
            Helpers::flash('success', 'In-House offer updated successfully.');
            Helpers::redirect('/admin/inhouse-offers');
        }
        // Re-fetch for form re-render
        $offer = Database::fetchOne("SELECT * FROM offers WHERE id=?", [$offerId]);
    }

    require BASE_PATH . '/views/admin/inhouse/form.php';
}

// ── TOGGLE STATUS (AJAX quick action) ─────────────────────────────────────────
elseif ($action === 'toggle_status') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $offerId   = (int)Helpers::postRaw('offer_id');
        $newStatus = Helpers::postRaw('status');
        if (in_array($newStatus, ['active','paused']) && $offerId) {
            Database::update('offers', ['status' => $newStatus], 'id=? AND is_inhouse=1', [$offerId]);
        }
    }
    Helpers::redirect('/admin/inhouse-offers');
}

// ── DELETE ─────────────────────────────────────────────────────────────────────
elseif ($action === 'delete') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $offerId = (int)Helpers::postRaw('offer_id');
        $o = Database::fetchOne("SELECT id, name FROM offers WHERE id=? AND is_inhouse=1", [$offerId]);
        if ($o) {
            Database::update('offers', ['status' => 'deleted'], 'id=?', [$offerId]);
            Database::query("DELETE FROM affiliate_offers WHERE offer_id=?", [$offerId]);
            Database::query("DELETE FROM offer_links WHERE offer_id=?", [$offerId]);
            Helpers::flash('success', 'Offer "'.$o['name'].'" deleted.');
        }
    }
    Helpers::redirect('/admin/inhouse-offers');
}

// ── Helper: save / update in-house offer from POST ───────────────────────────
function _ihSave(?int $existingId): array
{
    $errors = [];

    $name       = trim(Helpers::post('name') ?? '');
    $payout     = max(0, (float)Helpers::postRaw('payout_amount'));
    $revenue    = max(0, (float)Helpers::postRaw('revenue_amount'));
    $payType    = Helpers::post('payout_type') ?: 'CPA';
    $status     = in_array(Helpers::post('status'), ['active','paused','pending']) ? Helpers::post('status') : 'active';
    $validOfferTypes = ['DOI','SOI','CPL','CPS','CPI','CPA','COD','FINANCE','CPM','CPC','REVSHARE','TRIAL'];
    $offerType  = in_array(Helpers::post('offer_type'), $validOfferTypes) ? Helpers::post('offer_type') : '';
    // Collect all landing page URLs; filter empty ones
    $rawUrls    = array_values(array_filter(array_map('trim', $_POST['landing_pages'] ?? []), 'strlen'));
    $offerUrl   = $rawUrls[0] ?? '';
    $landingPages = count($rawUrls) > 1 ? json_encode($rawUrls) : null;
    $description= Helpers::postRaw('description');
    $category   = Helpers::post('category');
    $visibility = in_array(Helpers::post('visibility'), ['public','private','require_approval']) ? Helpers::post('visibility') : 'public';
    $dailyCap   = (int)Helpers::postRaw('daily_cap');
    $totalCap   = (int)Helpers::postRaw('total_cap');
    $rawGeos    = array_map('trim', (array)($_POST['geo_targeting'] ?? []));
    $geos       = in_array('GLOBAL', $rawGeos) ? [] : array_filter($rawGeos, fn($g) => $g !== 'GLOBAL');
    $devices    = array_filter(array_map('trim', (array)($_POST['device_targeting'] ?? [])));
    $requireApproval = isset($_POST['require_approval']) ? 1 : 0;

    if (!$name)     $errors[] = 'Offer name is required.';
    if (!$offerUrl) $errors[] = 'At least one Landing Page URL is required.';

    // Duplicate name check
    if ($name) {
        $dupCheck = $existingId
            ? Database::fetchOne("SELECT id FROM offers WHERE name=? AND id!=? AND status!='deleted'", [$name, $existingId])
            : Database::fetchOne("SELECT id FROM offers WHERE name=? AND status!='deleted'", [$name]);
        if ($dupCheck) $errors[] = 'An offer with this name already exists.';
    }

    if (!empty($errors)) return [$errors, null];

    $data = [
        'is_inhouse'       => 1,
        'advertiser_id'    => null,
        'name'             => $name,
        'description'      => $description,
        'offer_url'        => $offerUrl,
        'landing_pages'    => $landingPages,
        'category'         => $category,
        'offer_type'       => $offerType,
        'payout_type'      => $payType,
        'payout_amount'    => $payout,
        'revenue_amount'   => $revenue,
        'status'           => $status,
        'visibility'       => $visibility,
        'daily_cap'        => $dailyCap,
        'total_cap'        => $totalCap,
        'geo_targeting'    => $geos   ? json_encode(array_values($geos))   : null,
        'device_targeting' => $devices? json_encode(array_values($devices)): null,
        'require_approval' => $requireApproval,
        'created_by'       => Auth::id(),
    ];

    if ($existingId) {
        unset($data['created_by']);
        Database::update('offers', $data, 'id=?', [$existingId]);
        $offerId = $existingId;
    } else {
        $offerId = Database::insert('offers', $data);
    }

    return [[], $offerId];
}
