<?php
Auth::check('admin');
$pageTitle = 'Smartlinks';

// Schema migrations
try {
    Database::query("CREATE TABLE IF NOT EXISTS smartlink_requests (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        smartlink_id  INT NOT NULL,
        affiliate_id  INT NOT NULL,
        status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        affiliate_note TEXT DEFAULT NULL,
        admin_note    TEXT DEFAULT NULL,
        reviewed_by   INT DEFAULT NULL,
        reviewed_at   DATETIME DEFAULT NULL,
        created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_sl_aff (smartlink_id, affiliate_id),
        INDEX idx_status (status),
        INDEX idx_aff (affiliate_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}
// smartlink_offers enhancements: per-offer geo/device rules and payout override
try { Database::query("ALTER TABLE smartlink_offers ADD COLUMN geo_rules TEXT DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE smartlink_offers ADD COLUMN device_rules TEXT DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE smartlink_offers ADD COLUMN payout_type ENUM('default','fixed','skip','percent') NOT NULL DEFAULT 'default'"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE smartlink_offers ADD COLUMN payout_value DECIMAL(10,4) DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE smartlink_offers ADD COLUMN direct_url TEXT DEFAULT NULL"); } catch (Exception $e) {}
try { Database::query("ALTER TABLE smartlinks ADD COLUMN require_approval TINYINT(1) NOT NULL DEFAULT 0"); } catch (Exception $e) {}
// Allow offer_id to be NULL (for custom URL entries without a linked offer)
try { Database::query("ALTER TABLE smartlink_offers MODIFY COLUMN offer_id INT DEFAULT NULL"); } catch (Exception $e) {}

$action = Helpers::get('action') ?: (isset($_GET['id']) ? 'edit' : 'index');

// Handle toggle_status POST before index render
if (Helpers::isPost() && Helpers::postRaw('toggle_status') && $action === 'index') {
    if (Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id  = (int)Helpers::postRaw('id');
        $cur = Database::fetchOne("SELECT status FROM smartlinks WHERE id=?", [$id]);
        if ($cur) {
            $newStatus = $cur['status'] === 'active' ? 'paused' : 'active';
            Database::update('smartlinks', ['status' => $newStatus], 'id=?', [$id]);
        }
    }
    Helpers::redirect('/admin/smartlinks');
}

if ($action === 'index') {
    $smartlinks = Database::fetchAll(
        "SELECT sl.*, COUNT(DISTINCT so.id) as offer_count,
                COALESCE(MAX(cl_stats.total_clicks), 0)  as total_clicks,
                COALESCE(MAX(cv_stats.total_convs),  0)  as total_convs
         FROM smartlinks sl
         LEFT JOIN smartlink_offers so ON so.smartlink_id = sl.id
         LEFT JOIN (
             SELECT smartlink_id, COUNT(*) as total_clicks
             FROM clicks WHERE smartlink_id IS NOT NULL GROUP BY smartlink_id
         ) cl_stats ON cl_stats.smartlink_id = sl.id
         LEFT JOIN (
             SELECT cl.smartlink_id, COUNT(*) as total_convs
             FROM conversions cv
             JOIN clicks cl ON cl.click_id = cv.click_id
             WHERE cl.smartlink_id IS NOT NULL
             GROUP BY cl.smartlink_id
         ) cv_stats ON cv_stats.smartlink_id = sl.id
         GROUP BY sl.id ORDER BY sl.created_at DESC"
    );
    require BASE_PATH . '/views/admin/smartlinks/index.php';
}

elseif ($action === 'create') {
    $errors  = [];
    $appUrl  = Config::get('config','app.url') ?? '';
    $activeOffers = Database::fetchAll(
        "SELECT o.id,o.name,o.payout_amount,COALESCE(o.visibility,'public') as visibility FROM offers o WHERE o.status='active' ORDER BY o.name"
    );

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $name           = Helpers::post('name');
        $slug           = preg_replace('/[^a-z0-9-]/', '-', strtolower(Helpers::postRaw('slug')));
        $rotation       = Helpers::post('rotation_type');
        $description    = Helpers::postRaw('description');
        $requireApproval = isset($_POST['require_approval']) ? 1 : 0;
        $offerIds    = $_POST['offer_ids']        ?? [];
        $weights     = $_POST['weights']           ?? [];
        $soGeos      = $_POST['so_geos']           ?? [];
        $soDevices   = $_POST['so_devices']        ?? [];
        $soPayTypes  = $_POST['so_payout_type']    ?? [];
        $soPayValues = $_POST['so_payout_value']   ?? [];
        $soUrls      = $_POST['so_direct_url']     ?? [];

        if (!$name) $errors[] = 'Smartlink name is required.';
        if (!$slug) $errors[] = 'Slug is required.';
        $hasValidEntry = false;
        foreach ($offerIds as $i => $oid) {
            if ((int)$oid > 0 || !empty(trim($soUrls[$i] ?? ''))) { $hasValidEntry = true; break; }
        }
        if (!$hasValidEntry) $errors[] = 'Add at least one offer or custom URL entry.';

        // Check slug uniqueness
        if (!$errors && Database::fetchOne("SELECT id FROM smartlinks WHERE slug=?", [$slug])) {
            $errors[] = 'Slug already in use. Choose another.';
        }

        if (!$errors) {
            Database::begin();
            try {
                $slId = Database::insert('smartlinks', [
                    'name'             => $name,
                    'slug'             => $slug,
                    'created_by'       => Auth::id(),
                    'description'      => $description,
                    'rotation_type'    => $rotation,
                    'status'           => 'active',
                    'require_approval' => $requireApproval,
                ]);
                _saveSmartlinkOffers($slId, $offerIds, $weights, $soGeos, $soDevices, $soPayTypes, $soPayValues, $soUrls);
                Database::commit();
                Helpers::flash('success', 'Smartlink created: ' . $appUrl . '/smartlink/' . $slug);
                Helpers::redirect('/admin/smartlinks');
            } catch (\Exception $e) {
                Database::rollback();
                $errors[] = 'Failed to create smartlink: ' . $e->getMessage();
            }
        }
    }
    require BASE_PATH . '/views/admin/smartlinks/create.php';
}

elseif ($action === 'edit') {
    $errors  = [];
    $appUrl  = Config::get('config','app.url') ?? '';
    $id      = (int)Helpers::get('id');

    $sl = Database::fetchOne("SELECT * FROM smartlinks WHERE id=?", [$id]);
    if (!$sl) {
        Helpers::flash('error', 'Smartlink not found.');
        Helpers::redirect('/admin/smartlinks');
    }

    $smartlinkOffers = Database::fetchAll(
        "SELECT so.*, o.name FROM smartlink_offers so LEFT JOIN offers o ON o.id=so.offer_id WHERE so.smartlink_id=?",
        [$id]
    );

    $activeOffers = Database::fetchAll(
        "SELECT o.id,o.name,o.payout_amount,COALESCE(o.visibility,'public') as visibility FROM offers o WHERE o.status='active' ORDER BY o.name"
    );

    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $name            = Helpers::post('name');
        $slug            = preg_replace('/[^a-z0-9-]/', '-', strtolower(Helpers::postRaw('slug')));
        $rotation        = Helpers::post('rotation_type');
        $description     = Helpers::postRaw('description');
        $status          = Helpers::post('status');
        $requireApproval = isset($_POST['require_approval']) ? 1 : 0;
        $offerIds    = $_POST['offer_ids']        ?? [];
        $weights     = $_POST['weights']           ?? [];
        $soGeos      = $_POST['so_geos']           ?? [];
        $soDevices   = $_POST['so_devices']        ?? [];
        $soPayTypes  = $_POST['so_payout_type']    ?? [];
        $soPayValues = $_POST['so_payout_value']   ?? [];
        $soUrls      = $_POST['so_direct_url']     ?? [];

        if (!$name) $errors[] = 'Smartlink name is required.';
        if (!$slug) $errors[] = 'Slug is required.';
        $hasValidEntry = false;
        foreach ($offerIds as $i => $oid) {
            if ((int)$oid > 0 || !empty(trim($soUrls[$i] ?? ''))) { $hasValidEntry = true; break; }
        }
        if (!$hasValidEntry) $errors[] = 'Add at least one offer or custom URL entry.';

        // Check slug uniqueness excluding current record
        if (!$errors) {
            $existing = Database::fetchOne("SELECT id FROM smartlinks WHERE slug=? AND id!=?", [$slug, $id]);
            if ($existing) $errors[] = 'Slug already in use. Choose another.';
        }

        if (!$errors) {
            Database::begin();
            try {
                Database::update('smartlinks', [
                    'name'             => $name,
                    'slug'             => $slug,
                    'rotation_type'    => $rotation,
                    'description'      => $description,
                    'status'           => $status,
                    'require_approval' => $requireApproval,
                ], 'id=?', [$id]);

                Database::query("DELETE FROM smartlink_offers WHERE smartlink_id=?", [$id]);
                _saveSmartlinkOffers($id, $offerIds, $weights, $soGeos, $soDevices, $soPayTypes, $soPayValues, $soUrls);

                Database::commit();
                Helpers::flash('success', 'Smartlink updated successfully.');
                Helpers::redirect('/admin/smartlinks');
            } catch (\Exception $e) {
                Database::rollback();
                $errors[] = 'Failed to update smartlink: ' . $e->getMessage();
            }
        }

        // Reload for re-display on error
        if ($errors) {
            $smartlinkOffers = Database::fetchAll(
                "SELECT so.*, o.name FROM smartlink_offers so LEFT JOIN offers o ON o.id=so.offer_id WHERE so.smartlink_id=?",
                [$id]
            );
        }
    }

    require BASE_PATH . '/views/admin/smartlinks/edit.php';
}

elseif ($action === 'delete') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $id = (int)Helpers::postRaw('id');
        Database::query("DELETE FROM smartlink_offers WHERE smartlink_id=?", [$id]);
        Database::query("DELETE FROM smartlink_requests WHERE smartlink_id=?", [$id]);
        Database::query("DELETE FROM smartlinks WHERE id=?", [$id]);
        Helpers::flash('success', 'Smartlink deleted.');
    }
    Helpers::redirect('/admin/smartlinks');
}

// ── Smartlink Access Requests (list) ──────────────────────────────────────
elseif ($action === 'requests') {
    $statusFilter = Helpers::get('status') ?: 'pending';
    $whereStatus  = $statusFilter !== 'all' ? "sr.status=?" : "1=1";
    $statusParams = $statusFilter !== 'all' ? [$statusFilter] : [];

    $requests = Database::fetchAll(
        "SELECT sr.*, sl.name as smartlink_name, sl.slug,
                CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code, u.email as aff_email
         FROM smartlink_requests sr
         JOIN smartlinks sl ON sl.id=sr.smartlink_id
         JOIN affiliates af ON af.id=sr.affiliate_id
         JOIN users u ON u.id=af.user_id
         WHERE $whereStatus
         ORDER BY sr.created_at DESC",
        $statusParams
    );
    $pendingCount = Database::fetchOne("SELECT COUNT(*) as cnt FROM smartlink_requests WHERE status='pending'");
    require BASE_PATH . '/views/admin/smartlinks/requests.php';
}

// ── Review a request (approve / reject) ───────────────────────────────────
elseif ($action === 'review_request') {
    if (Helpers::isPost() && Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        $reqId     = (int)Helpers::postRaw('request_id');
        $decision  = Helpers::postRaw('decision'); // 'approved' | 'rejected'
        $adminNote = Helpers::postRaw('admin_note');

        if (!in_array($decision, ['approved', 'rejected'])) {
            Helpers::flash('error', 'Invalid decision.');
            Helpers::redirect('/admin/smartlinks?action=requests');
        }

        $req = Database::fetchOne(
            "SELECT sr.*, sl.name as sl_name, sl.slug,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, u.email as aff_email
             FROM smartlink_requests sr
             JOIN smartlinks sl ON sl.id=sr.smartlink_id
             JOIN affiliates af ON af.id=sr.affiliate_id
             JOIN users u ON u.id=af.user_id
             WHERE sr.id=?", [$reqId]
        );

        if ($req) {
            Database::update('smartlink_requests', [
                'status'      => $decision,
                'admin_note'  => $adminNote,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => date('Y-m-d H:i:s'),
            ], 'id=?', [$reqId]);

            // In-app notification to affiliate
            $affUserId = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$req['affiliate_id']]);
            if ($affUserId) {
                Database::insert('notifications', [
                    'user_id' => $affUserId['user_id'],
                    'type'    => $decision === 'approved' ? 'success' : 'warning',
                    'title'   => 'Smartlink Request ' . ucfirst($decision),
                    'message' => 'Your request to use smartlink "' . $req['sl_name'] . '" has been ' . $decision . '.',
                    'link'    => '/affiliate/smartlinks',
                ]);
            }

            // Email notification
            $eventType = $decision === 'approved' ? 'smartlink_approved' : 'smartlink_rejected';
            Mailer::sendEvent($req['aff_email'], $req['aff_name'], $eventType, [
                'name'           => $req['aff_name'],
                'email'          => $req['aff_email'],
                'smartlink_name' => $req['sl_name'],
                'admin_note'     => $adminNote ?: '',
                'site_name'      => Config::get('config','app.name') ?? 'AffiliateTracker',
                'app_url'        => rtrim(Config::get('config','app.url') ?? '', '/'),
            ]);

            Helpers::flash('success', 'Request ' . $decision . ' and affiliate notified.');
        } else {
            Helpers::flash('error', 'Request not found.');
        }
    }
    Helpers::redirect('/admin/smartlinks?action=requests');
}

// ── Helper: save smartlink offer rows from POST arrays ─────────────────────
function _saveSmartlinkOffers(int $slId, array $offerIds, array $weights, array $soGeos, array $soDevices, array $soPayTypes, array $soPayValues, array $soUrls = []): void
{
    $validDevices  = ['desktop', 'mobile', 'tablet'];
    $validPayTypes = ['default', 'fixed', 'skip', 'percent'];

    foreach ($offerIds as $i => $oid) {
        $oid       = (int)$oid;
        $directUrl = trim($soUrls[$i] ?? '') ?: null;
        if (!$oid && !$directUrl) continue; // Skip empty rows

        // Parse geo codes (comma-separated, 2-letter, alpha-only)
        $geoStr = trim($soGeos[$i] ?? '');
        $geoArr = $geoStr
            ? array_values(array_unique(array_filter(
                array_map(fn($c) => strtoupper(trim($c)), explode(',', $geoStr)),
                fn($c) => strlen($c) === 2 && ctype_alpha($c)
              )))
            : [];

        // Parse device list (comma-separated from hidden input synced by JS)
        $devStr = trim($soDevices[$i] ?? '');
        $devArr = $devStr
            ? array_values(array_unique(array_filter(
                array_map('trim', explode(',', $devStr)),
                fn($d) => in_array($d, $validDevices, true)
              )))
            : [];

        // Payout override
        $payType  = in_array($soPayTypes[$i] ?? 'default', $validPayTypes) ? $soPayTypes[$i] : 'default';
        $payValue = in_array($payType, ['fixed', 'percent'])
            ? max(0.0, (float)($soPayValues[$i] ?? 0))
            : null;

        Database::insert('smartlink_offers', [
            'smartlink_id' => $slId,
            'offer_id'     => $oid ?: null, // NULL for custom URL entries
            'weight'       => max(1, (int)($weights[$i] ?? 10)),
            'geo_rules'    => !empty($geoArr) ? json_encode($geoArr) : null,
            'device_rules' => !empty($devArr) ? json_encode($devArr) : null,
            'payout_type'  => $payType,
            'payout_value' => $payValue,
            'direct_url'   => $directUrl,
        ]);
    }
}
