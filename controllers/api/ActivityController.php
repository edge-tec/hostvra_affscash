<?php
header('Content-Type: application/json');
if (!Auth::id()) { echo json_encode(['error'=>'Unauthorized']); exit; }

$action = Helpers::get('action') ?: Helpers::postRaw('action');

// ── Heartbeat (every user, every 30s) ─────────────────────────────────────
if ($action === 'heartbeat') {
    $page = Helpers::postRaw('page') ?: (Helpers::get('page') ?: '/');
    $valid = Activity::heartbeat(Auth::id(), session_id(), $page);
    if (!$valid) {
        // Force logout: session was removed by admin or idle timeout
        session_destroy();
        if (ob_get_length()) ob_clean();
        echo json_encode(['error' => 'session_killed']);
        exit;
    }
    // ── Auto-trigger Affiliate Inactivity (Background, Throttled to 1 hr) ──
    $lastRunFile = BASE_PATH . '/logs/last_inactivity_run.txt';
    $lastRun = @file_get_contents($lastRunFile);
    if (!$lastRun || time() - (int)$lastRun > 3600) {
        @file_put_contents($lastRunFile, time());
        try {
            if (function_exists('exec') && is_callable('exec') && false === stripos(ini_get('disable_functions'), 'exec')) {
                exec('php ' . escapeshellarg(BASE_PATH . '/cron/affiliate_inactivity.php') . ' > /dev/null 2>&1 &');
            } else {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $url    = $scheme . '://' . $host . '/cron/affiliate-inactivity';
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
                curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_exec($ch);
                curl_close($ch);
            }
        } catch (\Throwable $e) {}
    }

    echo json_encode(['ok' => true, 'ts' => time()]);
    exit;
}

// ── Admin-only below ────────────────────────────────────────────────────────
if (Auth::role() !== 'admin') {
    echo json_encode(['error' => 'Forbidden']); exit;
}

Activity::ensureTables();
Activity::cleanExpired(10);

// ── Live users list ────────────────────────────────────────────────────────
if ($action === 'live_users') {
    $rows = Database::fetchAll(
        "SELECT s.id, s.session_id, s.user_id, s.role, s.user_name, s.ip_address,
                s.country, s.country_code, s.city,
                s.device_type, s.browser, s.os, s.platform_source,
                s.current_page, s.logged_in_at, s.last_active,
                u.email,
                TIMESTAMPDIFF(SECOND, s.last_active, NOW()) as idle_sec,
                TIMESTAMPDIFF(SECOND, s.logged_in_at, NOW()) as session_sec,
                CASE s.role
                    WHEN 'affiliate' THEN af.affiliate_code
                    ELSE NULL END as affiliate_code
         FROM user_active_sessions s
         JOIN users u ON u.id = s.user_id
         LEFT JOIN affiliates af ON af.user_id = s.user_id
         ORDER BY s.last_active DESC"
    );
    echo json_encode(['users' => $rows, 'total' => count($rows), 'ts' => date('Y-m-d H:i:s')]);
    exit;
}

// ── Online count ────────────────────────────────────────────────────────────
if ($action === 'online_count') {
    $r = Database::fetchOne("SELECT COUNT(*) as cnt FROM user_active_sessions");
    echo json_encode(['count' => (int)($r['cnt'] ?? 0)]);
    exit;
}

// ── Login log history ──────────────────────────────────────────────────────
if ($action === 'login_logs') {
    $page    = max(1, (int)(Helpers::get('page') ?? 1));
    $limit   = min(100, max(10, (int)(Helpers::get('limit') ?? 25)));
    $offset  = ($page - 1) * $limit;
    $search  = trim(Helpers::get('search') ?? '');
    $role    = trim(Helpers::get('role')   ?? '');
    $country = trim(Helpers::get('country') ?? '');
    $device  = trim(Helpers::get('device')  ?? '');
    $from    = trim(Helpers::get('from')    ?? '');
    $to      = trim(Helpers::get('to')      ?? '');

    $where = ['1=1'];
    $params = [];

    if ($search) {
        $where[] = "(l.ip_address LIKE ? OR l.country LIKE ? OR l.city LIKE ? OR u.email LIKE ? OR l.user_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $s = "%$search%";
        array_push($params, $s,$s,$s,$s,$s,$s,$s);
    }
    if ($role)    { $where[] = "l.role=?";        $params[] = $role; }
    if ($country) { $where[] = "l.country_code=?"; $params[] = $country; }
    if ($device)  { $where[] = "l.device_type=?";  $params[] = $device; }
    if ($from)    { $where[] = "l.login_time >= ?"; $params[] = $from . ' 00:00:00'; }
    if ($to)      { $where[] = "l.login_time <= ?"; $params[] = $to   . ' 23:59:59'; }

    $w = implode(' AND ', $where);

    $total = Database::fetchOne(
        "SELECT COUNT(*) as cnt FROM user_login_logs l JOIN users u ON u.id=l.user_id WHERE $w",
        $params
    );

    $rows = Database::fetchAll(
        "SELECT l.*, u.email, CONCAT(u.first_name,' ',u.last_name) as full_name,
                CASE l.role WHEN 'affiliate' THEN af.affiliate_code ELSE NULL END as affiliate_code
         FROM user_login_logs l
         JOIN users u ON u.id = l.user_id
         LEFT JOIN affiliates af ON af.user_id = l.user_id
         WHERE $w
         ORDER BY l.login_time DESC
         LIMIT $limit OFFSET $offset",
        $params
    );

    echo json_encode([
        'rows'        => $rows,
        'total'       => (int)($total['cnt'] ?? 0),
        'page'        => $page,
        'limit'       => $limit,
        'total_pages' => max(1, (int)ceil(($total['cnt'] ?? 0) / $limit)),
    ]);
    exit;
}

// ── Login log stats (for summary cards) ────────────────────────────────────
if ($action === 'log_stats') {
    $today = date('Y-m-d');
    $total   = Database::fetchOne("SELECT COUNT(*) as cnt FROM user_login_logs");
    $today_c = Database::fetchOne("SELECT COUNT(*) as cnt FROM user_login_logs WHERE DATE(login_time)=?", [$today]);
    $active  = Database::fetchOne("SELECT COUNT(*) as cnt FROM user_active_sessions");
    $devices = Database::fetchAll("SELECT device_type as label, COUNT(*) as cnt FROM user_active_sessions GROUP BY device_type ORDER BY cnt DESC");
    $roles   = Database::fetchAll("SELECT role as label, COUNT(*) as cnt FROM user_active_sessions GROUP BY role ORDER BY cnt DESC");
    $top_countries = Database::fetchAll(
        "SELECT country, country_code, COUNT(*) as cnt FROM user_login_logs
         WHERE DATE(login_time)>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND country IS NOT NULL AND country!=''
         GROUP BY country, country_code ORDER BY cnt DESC LIMIT 8"
    );
    $by_hour = Database::fetchAll(
        "SELECT HOUR(login_time) as h, COUNT(*) as cnt FROM user_login_logs
         WHERE DATE(login_time)=? GROUP BY HOUR(login_time)", [$today]
    );
    $hourly = array_fill(0, 24, 0);
    foreach ($by_hour as $r) $hourly[(int)$r['h']] = (int)$r['cnt'];

    echo json_encode([
        'total_logins'    => (int)($total['cnt']   ?? 0),
        'logins_today'    => (int)($today_c['cnt'] ?? 0),
        'online_now'      => (int)($active['cnt']  ?? 0),
        'devices'         => $devices,
        'roles'           => $roles,
        'top_countries'   => $top_countries,
        'hourly_today'    => array_values($hourly),
    ]);
    exit;
}

// ── Force logout a user ────────────────────────────────────────────────────
if ($action === 'force_logout' && Helpers::isPost()) {
    $sessionId = Helpers::postRaw('session_id') ?: '';
    $userId    = (int)(Helpers::postRaw('user_id') ?? 0);
    if ($sessionId && $userId) {
        Activity::logLogout($userId, $sessionId);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['error' => 'Missing params']);
    }
    exit;
}

echo json_encode(['error' => 'Unknown action']);
