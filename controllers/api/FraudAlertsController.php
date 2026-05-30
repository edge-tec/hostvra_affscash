<?php
header('Content-Type: application/json');
if (!Auth::id()) { echo json_encode(['error'=>'Unauthorized']); exit; }

FraudAutoNotify::ensureSchema();

$role   = Auth::role();
$action = Helpers::get('action') ?: Helpers::postRaw('action');

/**
 * Resolve the set of user_ids whose fraud alerts the current viewer can see.
 * - admin           → null (no user filter)
 * - affiliate       → [Auth::id()]   (their own)
 * - affiliate_manager → user_ids of every assigned affiliate (read-only access
 *                       to the affiliates they manage)
 */
$_faScopeUserIds = function() use ($role): ?array {
    if ($role === 'admin') return null;
    if ($role === 'affiliate_manager') {
        try {
            $affIds = Auth::managerAffiliateIds();
            if (!$affIds) return [0]; // sentinel → "no rows"
            $place  = implode(',', array_fill(0, count($affIds), '?'));
            $rows   = Database::fetchAll(
                "SELECT user_id FROM affiliates WHERE id IN ($place)",
                array_map('intval', $affIds)
            );
            $ids = array_filter(array_map(function($r){ return (int)$r['user_id']; }, $rows));
            return $ids ?: [0];
        } catch (\Throwable $e) { return [0]; }
    }
    // Affiliate (and any other non-admin) — own notifications only.
    return [(int)Auth::id()];
};

// ── One-time-per-session backfill ─────────────────────────────────────────
// Ensures historical high-risk conversions (those that pre-date the realtime
// FraudAutoNotify hook, or any that slipped through during downtime) show up
// in the bell. We only run this for the `list` action — opened when the user
// clicks the bell — so the high-frequency `since` poll stays cheap. A single
// pass is bounded to 500 rows; if more remain, the next bell-open finishes.
$_faMaybeBackfill = function() use ($role, $_faScopeUserIds) {
    try {
        if ($role === 'admin') {
            if (empty($_SESSION['fa_backfill_admin_done'])) {
                $n = FraudAutoNotify::backfillRecent(0, 500);
                if ($n < 500) $_SESSION['fa_backfill_admin_done'] = 1;
            }
        } elseif ($role === 'affiliate_manager') {
            // Backfill for every affiliate this manager handles, once per session.
            if (empty($_SESSION['fa_backfill_mgr_done'])) {
                $ids = $_faScopeUserIds();
                $total = 0;
                if ($ids) {
                    foreach ($ids as $uid) {
                        if ($uid <= 0) continue;
                        $total += FraudAutoNotify::backfillRecent((int)$uid, 500);
                    }
                }
                // No further work expected if every per-affiliate pass returned
                // under 500 (drained). Mark done.
                $_SESSION['fa_backfill_mgr_done'] = 1;
            }
        } else {
            if (empty($_SESSION['fa_backfill_done'])) {
                $n = FraudAutoNotify::backfillRecent((int)Auth::id(), 500);
                if ($n < 500) $_SESSION['fa_backfill_done'] = 1;
            }
        }
    } catch (\Throwable $e) {}
};

// ─── ACTION: list — last N fraud alerts for the current user ─────────────
// Affiliate sees their own; admin sees everyone's (filterable by affiliate_id).
if ($action === '' || $action === 'list') {
    // Backfill on bell-open only (not on every fast `since` poll).
    $_faMaybeBackfill();
    $limit  = min(50, max(5, (int)(Helpers::get('limit') ?? 20)));
    $onlyOpen = Helpers::get('only_open') === '1';
    $where  = "n.category = 'fraud'";
    $params = [];
    if ($role === 'admin') {
        $affId = (int)Helpers::get('affiliate_id');
        if ($affId > 0) {
            // Admin filtering to one affiliate's user_id.
            $row = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$affId]);
            if ($row) { $where .= " AND n.user_id = ?"; $params[] = (int)$row['user_id']; }
        }
    } else {
        // Non-admin: scoped user_ids (own for affiliate, assigned set for manager).
        $scope = $_faScopeUserIds() ?: [0];
        $place = implode(',', array_fill(0, count($scope), '?'));
        $where .= " AND n.user_id IN ($place)";
        $params = array_merge($params, $scope);
    }
    if ($onlyOpen) $where .= " AND n.resolved_at IS NULL";

    try {
        $rows = Database::fetchAll(
            "SELECT n.id, n.user_id, n.type, n.title, n.message, n.link, n.is_read,
                    n.created_at, n.category, n.meta, n.resolved_at, n.resolved_by
             FROM notifications n
             WHERE $where
             ORDER BY n.id DESC
             LIMIT $limit",
            $params
        );
    } catch (\Throwable $e) { $rows = []; }

    $unread = 0;
    $out = [];
    foreach ($rows as $r) {
        if (empty($r['is_read'])) $unread++;
        $meta = [];
        if (!empty($r['meta'])) { $tmp = json_decode($r['meta'], true); if (is_array($tmp)) $meta = $tmp; }
        $out[] = [
            'id'            => (int)$r['id'],
            'title'         => $r['title'],
            'message'       => $r['message'],
            'link'          => $r['link'],
            'is_read'       => (int)$r['is_read'],
            'created_at'    => $r['created_at'],
            'resolved_at'   => $r['resolved_at'],
            'conversion_id' => $meta['conversion_id'] ?? '',
            'short_id'      => $meta['short_id']      ?? '',
            'offer_name'    => $meta['offer_name']    ?? '',
            'offer_id'      => $meta['offer_id']      ?? 0,
            'risk_level'    => $meta['risk_level']    ?? 'medium',
            // fraud_score deliberately omitted for non-admin
            'fraud_score'   => $role === 'admin' ? (int)($meta['fraud_score'] ?? 0) : null,
            'detected_at'   => $meta['detected_at']   ?? $r['created_at'],
        ];
    }

    echo json_encode([
        'alerts' => $out,
        'unread' => $unread,
        'total'  => count($out),
    ]);
    exit;
}

// ─── ACTION: since — real-time poll: returns alerts newer than since_id ───
// Cheap query used by the live notification bar (polls every few seconds).
// Returns ONLY new alerts for the current user, plus the new max_id and the
// total unread count so the bell badge can update without a full list fetch.
if ($action === 'since') {
    $sinceId = (int)Helpers::get('since_id');
    if ($sinceId < 0) $sinceId = 0;
    $where  = "n.category = 'fraud' AND n.id > ?";
    $params = [$sinceId];
    if ($role === 'admin') {
        $affId = (int)Helpers::get('affiliate_id');
        if ($affId > 0) {
            $row = Database::fetchOne("SELECT user_id FROM affiliates WHERE id=?", [$affId]);
            if ($row) { $where .= " AND n.user_id = ?"; $params[] = (int)$row['user_id']; }
        }
    } else {
        $scope = $_faScopeUserIds() ?: [0];
        $place = implode(',', array_fill(0, count($scope), '?'));
        $where .= " AND n.user_id IN ($place)";
        $params = array_merge($params, $scope);
    }
    try {
        $rows = Database::fetchAll(
            "SELECT n.id, n.title, n.message, n.link, n.is_read, n.created_at,
                    n.meta, n.resolved_at
             FROM notifications n
             WHERE $where
             ORDER BY n.id ASC
             LIMIT 20",
            $params
        );
    } catch (\Throwable $e) { $rows = []; }

    $out = [];
    $maxId = $sinceId;
    foreach ($rows as $r) {
        if ((int)$r['id'] > $maxId) $maxId = (int)$r['id'];
        $meta = [];
        if (!empty($r['meta'])) { $tmp = json_decode($r['meta'], true); if (is_array($tmp)) $meta = $tmp; }
        $out[] = [
            'id'            => (int)$r['id'],
            'title'         => $r['title'],
            'message'       => $r['message'],
            'link'          => $r['link'],
            'is_read'       => (int)$r['is_read'],
            'created_at'    => $r['created_at'],
            'resolved_at'   => $r['resolved_at'],
            'conversion_id' => $meta['conversion_id'] ?? '',
            'short_id'      => $meta['short_id']      ?? '',
            'offer_name'    => $meta['offer_name']    ?? '',
            'risk_level'    => $meta['risk_level']    ?? 'medium',
            'detected_at'   => $meta['detected_at']   ?? $r['created_at'],
        ];
    }

    // Aggregate unread count for the badge.
    try {
        $w = "category = 'fraud' AND is_read = 0";
        $p = [];
        if ($role !== 'admin') {
            $scope = $_faScopeUserIds() ?: [0];
            $place = implode(',', array_fill(0, count($scope), '?'));
            $w .= " AND user_id IN ($place)";
            $p  = array_merge($p, $scope);
        }
        $cntRow = Database::fetchOne("SELECT COUNT(*) AS c FROM notifications WHERE $w", $p);
        $unread = (int)($cntRow['c'] ?? 0);
    } catch (\Throwable $e) { $unread = 0; }

    echo json_encode([
        'new'    => $out,
        'max_id' => $maxId,
        'unread' => $unread,
    ]);
    exit;
}

// ─── ACTION: unread_count — fast number for the topbar badge ─────────────
if ($action === 'unread_count') {
    try {
        $w  = "category = 'fraud' AND is_read = 0";
        $p  = [];
        if ($role !== 'admin') {
            $scope = $_faScopeUserIds() ?: [0];
            $place = implode(',', array_fill(0, count($scope), '?'));
            $w .= " AND user_id IN ($place)";
            $p  = array_merge($p, $scope);
        }
        $row = Database::fetchOne("SELECT COUNT(*) AS c FROM notifications WHERE $w", $p);
        echo json_encode(['count' => (int)($row['c'] ?? 0)]);
    } catch (\Throwable $e) { echo json_encode(['count' => 0]); }
    exit;
}

// ─── ACTION: mark_read — silence the badge for the affiliate ─────────────
if ($action === 'mark_read') {
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) { echo json_encode(['error'=>'CSRF']); exit; }
    $id  = (int)Helpers::postRaw('id');
    $all = Helpers::postRaw('all') === '1';
    try {
        if ($all) {
            if ($role === 'admin') {
                Database::query("UPDATE notifications SET is_read=1 WHERE category='fraud' AND is_read=0");
            } else {
                $scope = $_faScopeUserIds() ?: [0];
                $place = implode(',', array_fill(0, count($scope), '?'));
                Database::query("UPDATE notifications SET is_read=1 WHERE category='fraud' AND is_read=0 AND user_id IN ($place)", $scope);
            }
        } elseif ($id > 0) {
            // Allow marking only alerts within the viewer's scope.
            if ($role === 'admin') {
                Database::query("UPDATE notifications SET is_read=1 WHERE id=? AND category='fraud'", [$id]);
            } else {
                $scope = $_faScopeUserIds() ?: [0];
                $place = implode(',', array_fill(0, count($scope), '?'));
                Database::query(
                    "UPDATE notifications SET is_read=1 WHERE id=? AND category='fraud' AND user_id IN ($place)",
                    array_merge([$id], $scope)
                );
            }
        }
        echo json_encode(['ok' => true]);
    } catch (\Throwable $e) { echo json_encode(['error' => 'Failed']); }
    exit;
}

// ─── ACTION: resolve — admin-only: mark a fraud alert as handled ─────────
if ($action === 'resolve' || $action === 'unresolve') {
    if ($role !== 'admin') { echo json_encode(['error' => 'Forbidden']); exit; }
    if (!Auth::verifyCsrf(Helpers::postRaw('_token'))) { echo json_encode(['error'=>'CSRF']); exit; }
    $id = (int)Helpers::postRaw('id');
    if ($id <= 0) { echo json_encode(['error' => 'Missing id']); exit; }
    try {
        if ($action === 'resolve') {
            Database::query(
                "UPDATE notifications SET resolved_at = NOW(), resolved_by = ? WHERE id = ? AND category = 'fraud'",
                [(int)Auth::id(), $id]
            );
        } else {
            Database::query(
                "UPDATE notifications SET resolved_at = NULL, resolved_by = NULL WHERE id = ? AND category = 'fraud'",
                [$id]
            );
        }
        echo json_encode(['ok' => true]);
    } catch (\Throwable $e) { echo json_encode(['error' => 'Failed']); }
    exit;
}

echo json_encode(['error' => 'Unknown action']);
