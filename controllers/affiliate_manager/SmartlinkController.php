<?php
Auth::check('affiliate_manager');
$affIds = Auth::managerAffiliateIds();
$action = Helpers::get('action') ?: 'list';
$pageTitle = $action === 'requests' ? 'Smartlink Requests' : 'Smart Links';

// Ensure status column exists with correct default (handles legacy installs)
try { Database::query("ALTER TABLE smartlinks ADD COLUMN IF NOT EXISTS status ENUM('active','paused') NOT NULL DEFAULT 'active'"); } catch (\Throwable $e) {}
// Back-fill any NULL statuses left by older schema versions
try { Database::query("UPDATE smartlinks SET status='active' WHERE status IS NULL OR status=''"); } catch (\Throwable $e) {}

// ── Browse All Smartlinks ──────────────────────────────────────────────────
if ($action === 'list') {
    $qSmartlink = Helpers::get('q');
    // Show active smartlinks (treat NULL/empty status as active for legacy rows)
    $whereSl = ["(sl.status = 'active' OR sl.status IS NULL OR sl.status = '')"];
    $slParams = [];
    if ($qSmartlink) { $whereSl[] = "sl.name LIKE ?"; $slParams[] = '%'.$qSmartlink.'%'; }
    $slWhere = implode(' AND ', $whereSl);

    $affInSql = !empty($affIds) ? implode(',', array_fill(0, count($affIds), '?')) : '0';
    $slAffParams = !empty($affIds) ? $affIds : [];

    $smartlinks = Database::fetchAll(
        "SELECT sl.*,
                COUNT(DISTINCT CASE WHEN sr.status='approved' AND sr.affiliate_id IN ($affInSql) THEN sr.affiliate_id END) as my_approved,
                COUNT(DISTINCT CASE WHEN sr.status='pending'  AND sr.affiliate_id IN ($affInSql) THEN sr.affiliate_id END) as my_pending,
                COUNT(DISTINCT CASE WHEN sr.status='approved' THEN sr.affiliate_id END) as total_approved
         FROM smartlinks sl
         LEFT JOIN smartlink_requests sr ON sr.smartlink_id = sl.id
         WHERE $slWhere
         GROUP BY sl.id
         ORDER BY sl.name ASC",
        array_merge($slParams, $slAffParams, $slAffParams)
    );

    $pendingRequestsCount = 0;
    if (!empty($affIds)) {
        $in = implode(',', array_fill(0, count($affIds), '?'));
        $pendingRequestsCount = Database::fetchOne(
            "SELECT COUNT(*) as c FROM smartlink_requests WHERE status='pending' AND affiliate_id IN ($in)",
            $affIds
        )['c'] ?? 0;
    }

    $appUrl = rtrim(Config::get('config', 'app.url') ?? '', '/');

    // Managed affiliates for link generator
    $managedAffiliates = [];
    if (!empty($affIds)) {
        $inSql = implode(',', array_fill(0, count($affIds), '?'));
        $managedAffiliates = Database::fetchAll(
            "SELECT af.id, af.affiliate_code, CONCAT(u.first_name,' ',u.last_name) as name
             FROM affiliates af JOIN users u ON u.id=af.user_id
             WHERE af.id IN ($inSql) AND u.status='active'
             ORDER BY name",
            $affIds
        );
    }

    require BASE_PATH . '/views/affiliate_manager/smartlinks_list.php';
}

// ── List Requests (for managed affiliates) ─────────────────────────────────
elseif ($action === 'requests') {
    $statusFilter = Helpers::get('status') ?: 'pending';
    $whereStatus  = $statusFilter !== 'all' ? "AND sr.status=?" : "";
    $statusParams = $statusFilter !== 'all' ? [$statusFilter] : [];

    if (empty($affIds)) {
        $requests     = [];
        $pendingCount = ['cnt' => 0];
    } else {
        $in  = implode(',', array_fill(0, count($affIds), '?'));
        $requests = Database::fetchAll(
            "SELECT sr.*, sl.name as smartlink_name, sl.slug,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name, af.affiliate_code, u.email as aff_email
             FROM smartlink_requests sr
             JOIN smartlinks sl ON sl.id=sr.smartlink_id
             JOIN affiliates af ON af.id=sr.affiliate_id
             JOIN users u ON u.id=af.user_id
             WHERE sr.affiliate_id IN ($in) $whereStatus
             ORDER BY sr.created_at DESC",
            array_merge($affIds, $statusParams)
        );
        $pendingCount = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM smartlink_requests WHERE affiliate_id IN ($in) AND status='pending'",
            $affIds
        );
    }
    require BASE_PATH . '/views/affiliate_manager/smartlink_requests.php';
}

// ── Review a Request ───────────────────────────────────────────────────────
elseif ($action === 'review_request') {
    if (!Helpers::isPost() || !Auth::verifyCsrf(Helpers::postRaw('_token'))) {
        Helpers::flash('error', 'Invalid request.');
        Helpers::redirect('/affiliate_manager/smartlinks');
    }

    $reqId    = (int)Helpers::postRaw('request_id');
    $decision = Helpers::postRaw('decision');
    $note     = Helpers::postRaw('admin_note');

    if (!in_array($decision, ['approved', 'rejected'])) {
        Helpers::flash('error', 'Invalid decision.');
        Helpers::redirect('/affiliate_manager/smartlinks');
    }

    // Verify this request belongs to a managed affiliate
    if (empty($affIds)) {
        Helpers::flash('error', 'Access denied.');
        Helpers::redirect('/affiliate_manager/smartlinks');
    }

    $in  = implode(',', array_fill(0, count($affIds), '?'));
    $req = Database::fetchOne(
        "SELECT sr.*, sl.name as sl_name,
                CONCAT(u.first_name,' ',u.last_name) as aff_name, u.email as aff_email,
                u.id as user_id_val
         FROM smartlink_requests sr
         JOIN smartlinks sl ON sl.id=sr.smartlink_id
         JOIN affiliates af ON af.id=sr.affiliate_id
         JOIN users u ON u.id=af.user_id
         WHERE sr.id=? AND sr.affiliate_id IN ($in)",
        array_merge([$reqId], $affIds)
    );

    if (!$req) {
        Helpers::flash('error', 'Request not found.');
        Helpers::redirect('/affiliate_manager/smartlinks');
    }

    Database::update('smartlink_requests', [
        'status'      => $decision,
        'admin_note'  => $note,
        'reviewed_by' => Auth::id(),
        'reviewed_at' => date('Y-m-d H:i:s'),
    ], 'id=?', [$reqId]);

    // In-app notification
    Database::insert('notifications', [
        'user_id' => $req['user_id_val'],
        'type'    => $decision === 'approved' ? 'success' : 'warning',
        'title'   => 'Smartlink Request ' . ucfirst($decision),
        'message' => 'Your request for smartlink "' . $req['sl_name'] . '" has been ' . $decision . '.',
        'link'    => '/affiliate/smartlinks',
    ]);

    // Email
    $eventType = $decision === 'approved' ? 'smartlink_approved' : 'smartlink_rejected';
    Mailer::sendEvent($req['aff_email'], $req['aff_name'], $eventType, [
        'name'           => $req['aff_name'],
        'email'          => $req['aff_email'],
        'smartlink_name' => $req['sl_name'],
        'admin_note'     => $note ?: '',
        'site_name'      => Config::get('config','app.name') ?? 'AffiliateTracker',
        'app_url'        => rtrim(Config::get('config','app.url') ?? '', '/'),
    ]);

    Helpers::flash('success', 'Request ' . $decision . ' and affiliate notified.');
    Helpers::redirect('/affiliate_manager/smartlinks');
}

else {
    Helpers::redirect('/affiliate_manager/smartlinks');
}
