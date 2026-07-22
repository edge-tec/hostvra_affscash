<?php
/**
 * Admin App API — Login Activity & Real-Time Live Users
 */
Auth::check('admin');
require_once BASE_PATH . '/core/Activity.php';

Activity::ensureTables();
// Strictly purge any sessions that have been idle > 5 minutes
Activity::cleanExpired(5);

try {
    $action = $_GET['action'] ?? 'live_users';

    if ($action === 'live_users') {
        // Query active sessions updated within the last 5 minutes
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
            WHERE s.last_active >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
         ORDER BY s.last_active DESC"
        ) ?: [];

        $formatted = [];
        foreach ($rows as $r) {
            $idleSec = (int)($r['idle_sec'] ?? 0);
            if ($idleSec < 0) $idleSec = 0;

            if ($idleSec < 15) {
                $activeText = 'Active now (Live)';
            } else if ($idleSec < 60) {
                $activeText = $idleSec . 's ago';
            } else {
                $activeText = floor($idleSec / 60) . 'm ago';
            }

            $formatted[] = [
                'id' => (int)$r['id'],
                'session_id' => $r['session_id'],
                'user_id' => (int)$r['user_id'],
                'user_name' => !empty($r['user_name']) ? trim($r['user_name']) : ('User #' . $r['user_id']),
                'email' => $r['email'] ?? '',
                'role' => strtoupper($r['role'] ?? 'USER'),
                'affiliate_code' => $r['affiliate_code'] ?? '',
                'ip_address' => $r['ip_address'] ?? '',
                'country' => $r['country'] ?? '',
                'country_code' => $r['country_code'] ?? '',
                'city' => $r['city'] ?? '',
                'device_type' => $r['device_type'] ?? 'Unknown',
                'browser' => $r['browser'] ?? 'Unknown',
                'os' => $r['os'] ?? '',
                'platform_source' => $r['platform_source'] ?? 'Web',
                'current_page' => $r['current_page'] ?? '/',
                'logged_in_at' => date('M d, H:i', strtotime($r['logged_in_at'])),
                'last_active' => $activeText,
                'idle_sec' => $idleSec,
                'session_sec' => (int)($r['session_sec'] ?? 0)
            ];
        }

        Helpers::json([
            'status' => 'success',
            'data' => [
                'live_users' => $formatted,
                'total' => count($formatted),
                'server_time' => date('H:i:s')
            ]
        ]);
        exit;
    }

    if ($action === 'login_logs') {
        $from = $_GET['from'] ?? ($_GET['start_date'] ?? null);
        $to = $_GET['to'] ?? ($_GET['end_date'] ?? null);
        $search = trim($_GET['search'] ?? '');

        $where = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[] = "(l.ip_address LIKE ? OR l.country LIKE ? OR u.email LIKE ? OR l.user_name LIKE ?)";
            $s = "%$search%";
            array_push($params, $s, $s, $s, $s);
        }

        if ($from && $to) {
            $where[] = "l.login_time BETWEEN ? AND ?";
            array_push($params, date('Y-m-d 00:00:00', strtotime($from)), date('Y-m-d 23:59:59', strtotime($to)));
        }

        $wStr = implode(' AND ', $where);

        $logs = Database::fetchAll(
            "SELECT l.*, u.email, CONCAT(u.first_name,' ',u.last_name) as full_name,
                    CASE l.role WHEN 'affiliate' THEN af.affiliate_code ELSE NULL END as affiliate_code,
                    IF(s.id IS NOT NULL, 1, 0) as is_active
             FROM user_login_logs l
             JOIN users u ON u.id = l.user_id
             LEFT JOIN affiliates af ON af.user_id = l.user_id
             LEFT JOIN user_active_sessions s ON s.session_id = l.session_id AND s.last_active >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             WHERE $wStr
             ORDER BY l.login_time DESC
             LIMIT 500",
            $params
        ) ?: [];

        $formatted = [];
        foreach ($logs as $l) {
            $formatted[] = [
                'id' => (int)$l['id'],
                'user_id' => (int)$l['user_id'],
                'user_name' => !empty($l['full_name']) ? trim($l['full_name']) : ($l['user_name'] ?? 'User'),
                'email' => $l['email'] ?? '',
                'role' => strtoupper($l['role'] ?? 'USER'),
                'affiliate_code' => $l['affiliate_code'] ?? '',
                'ip_address' => $l['ip_address'] ?? '',
                'country' => $l['country'] ?? '',
                'country_code' => $l['country_code'] ?? '',
                'city' => $l['city'] ?? '',
                'device_type' => $l['device_type'] ?? 'Unknown',
                'browser' => $l['browser'] ?? 'Unknown',
                'os' => $l['os'] ?? '',
                'platform_source' => $l['platform_source'] ?? 'Web',
                'login_time' => date('M d, Y H:i:s', strtotime($l['login_time'])),
                'is_active' => (int)$l['is_active'] === 1
            ];
        }

        Helpers::json(['status' => 'success', 'data' => ['login_logs' => $formatted, 'total' => count($formatted)]]);
        exit;
    }

    if ($action === 'force_logout') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $sessionId = $input['session_id'] ?? ($_POST['session_id'] ?? '');
        $userId = (int)($input['user_id'] ?? ($_POST['user_id'] ?? 0));
        if ($sessionId && $userId) {
            Activity::logLogout($userId, $sessionId);
            Helpers::json(['status' => 'success', 'message' => 'Session terminated successfully']);
        } else {
            Helpers::json(['status' => 'error', 'message' => 'Missing session parameters']);
        }
        exit;
    }

} catch (\Throwable $e) {
    Helpers::json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
