<?php
/**
 * Admin App API — Fraud Score Report
 */
Auth::requireRole('admin');

try { PostbackFirer::ensurePostbackSentColumn(); } catch (\Throwable $e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_checked_at` DATETIME DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` MODIFY COLUMN `fraud_score` TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}

$action = Helpers::get('action') ?: (Helpers::isPost() ? Helpers::post('action') : 'report');

if ($action === 'tick' && Helpers::isPost()) {
    require_once BASE_PATH . '/core/FraudIQ.php';
    @set_time_limit(30);

    $cfg = Config::get('fraud') ?? [];
    $batch = (int)Helpers::post('batch');
    if ($batch < 1)  $batch = 5;
    if ($batch > 25) $batch = 25;

    $pending = Database::fetchAll(
        "SELECT id, conversion_id, click_id, ip_address, affiliate_id, payout
         FROM conversions
         WHERE (fraud_checked_at IS NULL
                OR ipquery_risk_score IS NULL
                OR scamalytics_score IS NULL
                OR proxycheck_score IS NULL
                OR frauddefense_score IS NULL
                OR fraudlabspro_score IS NULL)
           AND COALESCE(is_hidden, 0) = 0
           AND converted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
         ORDER BY converted_at DESC
         LIMIT $batch"
    ) ?: [];

    $updates = [];
    foreach ($pending as $conv) {
        $ip = trim($conv['ip_address'] ?? '');
        $ipValid  = ($ip !== '' && $ip !== '0.0.0.0' && filter_var($ip, FILTER_VALIDATE_IP) !== false);
        $ipPublic = $ipValid && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

        // 1) IPQuery.io
        if ($ipValid) {
            try {
                $_ipqR = FraudIQ::checkIPQuery($ip);
                Database::query(
                    "UPDATE `conversions` SET `ipquery_risk_score`=?, `ipquery_risk_level`=?,
                            `ipquery_vpn`=?, `ipquery_proxy`=?, `ipquery_tor`=?, `ipquery_datacenter`=?
                     WHERE `conversion_id`=?",
                    [$_ipqR['risk_score'], $_ipqR['risk_level'], $_ipqR['is_vpn'], $_ipqR['is_proxy'], $_ipqR['is_tor'], $_ipqR['is_datacenter'], $conv['conversion_id']]
                );
            } catch (\Throwable $_) {
                try { Database::query("UPDATE `conversions` SET `ipquery_risk_score`=0,`ipquery_risk_level`='low' WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `ipquery_risk_score`=0,`ipquery_risk_level`='low' WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
        }

        // 2) IPQS
        if (!empty($cfg['ipqs_api_key']) && (bool)($cfg['ipqs_enabled'] ?? false) && $ipValid) {
            try {
                $_ipqsR = FraudIQ::checkIPQS($ip, $conv['click_id']);
                $_score = (int)($_ipqsR['score'] ?? 0);
                Database::query("UPDATE `conversions` SET `fraud_score`=?, `fraud_checked_at`=NOW() WHERE `conversion_id`=?", [$_score, $conv['conversion_id']]);
            } catch (\Throwable $_) {
                try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
        }

        // 3-6) Optional providers
        $extras = [
            ['scamalytics_api_key', 'scamalytics_enabled', 'checkFraud',         ['scamalytics_score','scamalytics_status'], 'scamalytics_score'],
            ['proxycheck_api_key',  'proxycheck_enabled',  'checkProxyCheck',    ['proxycheck_score','proxycheck_status'],   'proxycheck_score'],
            ['frauddefense_api_key','frauddefense_enabled','checkFraudDefense',  ['frauddefense_score','frauddefense_status'],'frauddefense_score'],
            ['fraudlabspro_api_key','fraudlabspro_enabled','checkFraudLabsPro',  ['fraudlabspro_score','fraudlabspro_status'],'fraudlabspro_score'],
        ];
        foreach ($extras as [$kKey, $kEnabled, $fn, $cols, $zeroCol]) {
            if (!empty($cfg[$kKey]) && (!array_key_exists($kEnabled, $cfg) || (bool)$cfg[$kEnabled]) && $ipPublic) {
                try {
                    $r = FraudIQ::$fn($ip);
                    $score  = (int)($r['score']  ?? $r['risk_score'] ?? 0);
                    $status = (string)($r['status'] ?? '');
                    Database::query("UPDATE `conversions` SET `{$cols[0]}`=?, `{$cols[1]}`=? WHERE `conversion_id`=?", [$score, $status, $conv['conversion_id']]);
                } catch (\Throwable $_) {
                    try { Database::query("UPDATE `conversions` SET `{$zeroCol}`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
                }
            } else {
                try { Database::query("UPDATE `conversions` SET `{$zeroCol}`=0 WHERE `conversion_id`=? AND `{$zeroCol}` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        }

        try { FraudAutoNotify::afterCheck($conv['conversion_id']); } catch (\Throwable $_) {}

        $fresh = Database::fetchOne(
            "SELECT conversion_id, fraud_score, fraud_checked_at,
                    ipquery_risk_score, ipquery_risk_level, ipquery_vpn, ipquery_proxy, ipquery_tor, ipquery_datacenter,
                    scamalytics_score, scamalytics_status, proxycheck_score, proxycheck_is_proxy, proxycheck_is_vpn,
                    frauddefense_score, frauddefense_status, fraudlabspro_score, fraudlabspro_flp_status
             FROM conversions WHERE conversion_id = ? LIMIT 1",
            [$conv['conversion_id']]
        );
        if ($fresh) $updates[$conv['conversion_id']] = $fresh;
    }

    $remaining = (int)(Database::fetchOne(
        "SELECT COUNT(*) AS c FROM conversions
         WHERE (fraud_checked_at IS NULL OR ipquery_risk_score IS NULL OR scamalytics_score IS NULL
                OR proxycheck_score IS NULL OR frauddefense_score IS NULL OR fraudlabspro_score IS NULL)
           AND COALESCE(is_hidden,0) = 0
           AND converted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)"
    )['c'] ?? 0);

    Helpers::jsonResponse(['status' => 'success', 'processed' => count($pending), 'remaining' => $remaining, 'updates' => $updates]);
}

if ($action === 'recheck_pending' && Helpers::isPost()) {
    require_once BASE_PATH . '/core/FraudIQ.php';
    $cfg = Config::get('fraud') ?? [];

    $pending = Database::fetchAll(
        "SELECT conversion_id, click_id, ip_address, affiliate_id, payout
         FROM conversions
         WHERE (fraud_checked_at IS NULL
                OR ipquery_risk_score IS NULL
                OR scamalytics_score IS NULL
                OR proxycheck_score IS NULL
                OR frauddefense_score IS NULL
                OR fraudlabspro_score IS NULL)
           AND COALESCE(is_hidden, 0) = 0
           AND converted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
         ORDER BY converted_at DESC LIMIT 100"
    );

    $checked = 0;
    foreach ($pending as $conv) {
        $ip = trim($conv['ip_address'] ?? '');
        $ipValid  = ($ip !== '' && $ip !== '0.0.0.0' && filter_var($ip, FILTER_VALIDATE_IP) !== false);
        $ipPublic = $ipValid && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

        // 1) IPQuery.io
        if ($ipValid) {
            try {
                $_ipqR = FraudIQ::checkIPQuery($ip);
                Database::query(
                    "UPDATE `conversions` SET `ipquery_risk_score`=?, `ipquery_risk_level`=?, `ipquery_vpn`=?, `ipquery_proxy`=?, `ipquery_tor`=?, `ipquery_datacenter`=? WHERE `conversion_id`=?",
                    [$_ipqR['risk_score'], $_ipqR['risk_level'], $_ipqR['is_vpn'], $_ipqR['is_proxy'], $_ipqR['is_tor'], $_ipqR['is_datacenter'], $conv['conversion_id']]
                );
            } catch (\Throwable $_) {
                try { Database::query("UPDATE `conversions` SET `ipquery_risk_score`=0,`ipquery_risk_level`='low' WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `ipquery_risk_score`=0,`ipquery_risk_level`='low' WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
        }

        // 2) IPQS
        if (!empty($cfg['ipqs_api_key']) && (bool)($cfg['ipqs_enabled'] ?? false) && $ipValid) {
            try {
                $_ipqsR = FraudIQ::checkIPQS($ip, $conv['click_id']);
                Database::query("UPDATE `conversions` SET `fraud_score`=?, `fraud_checked_at`=NOW() WHERE `conversion_id`=?", [(int)($_ipqsR['score'] ?? 0), $conv['conversion_id']]);
            } catch (\Throwable $_e) {}
        } else {
            try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }

        // 3) Scamalytics
        if (!empty($cfg['scamalytics_api_key']) && !empty($cfg['scamalytics_user']) && $ipPublic) {
            if (!array_key_exists('scamalytics_enabled', $cfg) || (bool)$cfg['scamalytics_enabled']) {
                try {
                    $_scR = FraudIQ::checkFraud($ip);
                    Database::query("UPDATE `conversions` SET `scamalytics_score`=?,`scamalytics_status`=?,`scamalytics_mode`=? WHERE `conversion_id`=?", [$_scR['score'], $_scR['status'], $_scR['mode'] ?? 'score_only', $conv['conversion_id']]);
                } catch (\Throwable $_e) {
                    try { Database::query("UPDATE `conversions` SET `scamalytics_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
                }
            } else {
                try { Database::query("UPDATE `conversions` SET `scamalytics_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `scamalytics_score`=0 WHERE `conversion_id`=? AND `scamalytics_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
        }

        // 4) ProxyCheck
        if (!empty($cfg['proxycheck_api_key']) && $ipPublic) {
            if (!array_key_exists('proxycheck_enabled', $cfg) || (bool)$cfg['proxycheck_enabled']) {
                try {
                    $_pcR = FraudIQ::checkProxyCheck($ip);
                    Database::query("UPDATE `conversions` SET `proxycheck_score`=?,`proxycheck_is_proxy`=?,`proxycheck_is_vpn`=?,`proxycheck_status`=? WHERE `conversion_id`=?", [$_pcR['score'], $_pcR['is_proxy'], $_pcR['is_vpn'], $_pcR['status'], $conv['conversion_id']]);
                } catch (\Throwable $_e) {
                    try { Database::query("UPDATE `conversions` SET `proxycheck_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
                }
            } else {
                try { Database::query("UPDATE `conversions` SET `proxycheck_score`=0 WHERE `conversion_id`=? AND `proxycheck_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `proxycheck_score`=0 WHERE `conversion_id`=? AND `proxycheck_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
        }

        // 5) FraudDefense
        if (!empty($cfg['frauddefense_api_key']) && $ipPublic) {
            if (!array_key_exists('frauddefense_enabled', $cfg) || (bool)$cfg['frauddefense_enabled']) {
                try {
                    $_fdR = FraudIQ::checkFraudDefense($ip);
                    Database::query("UPDATE `conversions` SET `frauddefense_score`=?,`frauddefense_status`=? WHERE `conversion_id`=?", [$_fdR['score'], $_fdR['status'], $conv['conversion_id']]);
                } catch (\Throwable $_e) {
                    try { Database::query("UPDATE `conversions` SET `frauddefense_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
                }
            } else {
                try { Database::query("UPDATE `conversions` SET `frauddefense_score`=0 WHERE `conversion_id`=? AND `frauddefense_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `frauddefense_score`=0 WHERE `conversion_id`=? AND `frauddefense_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
        }

        // 6) FraudLabs Pro
        if (!empty($cfg['fraudlabspro_api_key']) && $ipPublic) {
            if (!array_key_exists('fraudlabspro_enabled', $cfg) || (bool)$cfg['fraudlabspro_enabled']) {
                try {
                    $_flR = FraudIQ::checkFraudLabsPro($ip);
                    Database::query("UPDATE `conversions` SET `fraudlabspro_score`=?,`fraudlabspro_status`=?,`fraudlabspro_flp_status`=? WHERE `conversion_id`=?", [$_flR['score'], $_flR['status'], $_flR['flp_status'], $conv['conversion_id']]);
                } catch (\Throwable $_e) {
                    try { Database::query("UPDATE `conversions` SET `fraudlabspro_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_) {}
                }
            } else {
                try { Database::query("UPDATE `conversions` SET `fraudlabspro_score`=0 WHERE `conversion_id`=? AND `fraudlabspro_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `fraudlabspro_score`=0 WHERE `conversion_id`=? AND `fraudlabspro_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_) {}
        }

        try { FraudAutoNotify::afterCheck($conv['conversion_id']); } catch (\Throwable $_naEx) {}
        $checked++;
        usleep(200000);
    }

    $remaining = (int)(Database::fetchOne(
        "SELECT COUNT(*) as c FROM conversions
         WHERE fraud_checked_at IS NULL
            OR ipquery_risk_score IS NULL
            OR scamalytics_score IS NULL
            OR proxycheck_score IS NULL
            OR frauddefense_score IS NULL
            OR fraudlabspro_score IS NULL",
        []
    )['c'] ?? 0);

    Helpers::jsonResponse(['status' => 'success', 'checked' => $checked, 'remaining' => $remaining]);
}

if ($action === 'update_status' && Helpers::isPost()) {
    $data = Helpers::getJsonPayload();
    $convId = $data['conversion_id'] ?? '';
    $newStatus = $data['status'] ?? '';

    if (in_array($newStatus, ['approved', 'rejected'])) {
        $conv = Database::fetchOne("SELECT * FROM conversions WHERE conversion_id=?", [$convId]);
        if ($conv) {
            $oldStatus    = $conv['status'];
            $rejectReason = trim((string)($data['rejection_reason'] ?? ''));
            $payload      = RejectionHelper::buildUpdatePayload($newStatus, $rejectReason, (int)(Auth::id() ?? 0));
            Database::update('conversions', $payload, 'conversion_id=?', [$convId]);

            if ($newStatus === 'rejected' && $oldStatus !== 'rejected') {
                try { RejectionNotifier::afterReject((string)$convId); } catch (\Throwable $_rn) {}
            }
            if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                Database::query("UPDATE affiliates SET balance = balance + ? WHERE id = ?", [$conv['payout'], $conv['affiliate_id']]);
                try {
                    require_once BASE_PATH . '/core/ManagerCommissionService.php';
                    ManagerCommissionService::recordForConversion((int)$conv['id']);
                } catch (\Throwable $_mce) {}
            } elseif ($newStatus === 'rejected' && $oldStatus === 'approved') {
                Database::query("UPDATE affiliates SET balance = balance - ? WHERE id = ?", [$conv['payout'], $conv['affiliate_id']]);
                try {
                    require_once BASE_PATH . '/core/ManagerCommissionService.php';
                    ManagerCommissionService::reverseForConversion((int)$conv['id']);
                } catch (\Throwable $_mce) {}
            }
            Helpers::jsonResponse(['status' => 'success', 'message' => "Conversion $newStatus."]);
        }
    }
    Helpers::jsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
}

if ($action === 'bulk_reject' && Helpers::isPost()) {
    $data = Helpers::getJsonPayload();
    $convIds = $data['conversion_ids'] ?? [];
    $rejectReason = trim((string)($data['rejection_reason'] ?? '')) ?: 'Bulk Fraud Rejection';
    $count = 0;

    if (is_array($convIds)) {
        foreach ($convIds as $convId) {
            $convId = trim((string)$convId);
            if ($convId === '') continue;

            $conv = Database::fetchOne("SELECT * FROM conversions WHERE conversion_id=?", [$convId]);
            if ($conv && $conv['status'] !== 'rejected') {
                $oldStatus = $conv['status'];
                $payload   = RejectionHelper::buildUpdatePayload('rejected', $rejectReason, (int)(Auth::id() ?? 0));
                Database::update('conversions', $payload, 'conversion_id=?', [$convId]);

                if ($oldStatus !== 'rejected') {
                    try { RejectionNotifier::afterReject((string)$convId); } catch (\Throwable $_rn) {}
                }
                if ($oldStatus === 'approved') {
                    Database::query("UPDATE affiliates SET balance = balance - ? WHERE id = ?", [$conv['payout'], $conv['affiliate_id']]);
                    try {
                        require_once BASE_PATH . '/core/ManagerCommissionService.php';
                        ManagerCommissionService::reverseForConversion((int)$conv['id']);
                    } catch (\Throwable $_mce) {}
                }
                $count++;
            }
        }
    }
    Helpers::jsonResponse(['status' => 'success', 'rejected_count' => $count]);
}

if ($action === 'report') {
    $filterStatus    = Helpers::get('status') ?: 'all';
    $filterAffiliate = (int)Helpers::get('affiliate_id');
    $filterOffer     = (int)Helpers::get('offer_id');
    $filterClickId   = trim(Helpers::get('click_id') ?? '');
    $filterScoreMin  = Helpers::get('score_min') !== null && Helpers::get('score_min') !== '' ? (int)Helpers::get('score_min') : 0;
    $filterScoreMax  = Helpers::get('score_max') !== null && Helpers::get('score_max') !== '' ? (int)Helpers::get('score_max') : 100;
    $sortBy          = in_array(Helpers::get('sort'), ['fraud_score','converted_at']) ? Helpers::get('sort') : 'converted_at';
    $sortDir         = Helpers::get('dir') === 'asc' ? 'ASC' : 'DESC';

    $from = Helpers::get('from') ?: date('Y-m-01');
    $to   = Helpers::get('to')   ?: date('Y-m-d');
    $dateFrom = date('Y-m-d 00:00:00', strtotime($from));
    $dateTo   = date('Y-m-d 23:59:59', strtotime($to));

    $where  = ['COALESCE(cv.is_hidden, 0) = 0', 'cv.converted_at BETWEEN ? AND ?'];
    $params = [$dateFrom, $dateTo];

    if (in_array($filterStatus, ['pending','approved','rejected'])) {
        $where[]  = 'cv.status = ?';
        $params[] = $filterStatus;
    }
    if ($filterAffiliate) {
        $where[]  = 'cv.affiliate_id = ?';
        $params[] = $filterAffiliate;
    }
    if ($filterOffer) {
        $where[]  = 'cv.offer_id = ?';
        $params[] = $filterOffer;
    }
    if ($filterClickId !== '') {
        $where[]  = 'cv.click_id = ?';
        $params[] = $filterClickId;
    }
    if ($filterScoreMin > 0) {
        $where[]  = 'COALESCE(cv.fraud_score, 0) >= ?';
        $params[] = $filterScoreMin;
    }
    if ($filterScoreMax < 100) {
        $where[]  = 'COALESCE(cv.fraud_score, 0) <= ?';
        $params[] = $filterScoreMax;
    }

    $whereSQL = implode(' AND ', $where);
    $orderClause = $sortBy === 'fraud_score' ? "ISNULL(cv.fraud_score) ASC, cv.fraud_score $sortDir" : "cv.converted_at $sortDir";

    try {
        $conversions = Database::fetchAll(
            "SELECT cv.conversion_id, cv.click_id, cv.affiliate_id, af.affiliate_code,
                    cv.offer_id, o.name as offer_name,
                    cv.ip_address, cv.fraud_score, cv.fraud_checked_at,
                    cv.status, cv.payout, cv.converted_at,
                    CONCAT(u.first_name,' ',u.last_name) as aff_name,
                    cv.ipquery_risk_score, cv.ipquery_risk_level,
                    cv.ipquery_vpn, cv.ipquery_proxy, cv.ipquery_tor, cv.ipquery_datacenter,
                    cv.scamalytics_score, cv.scamalytics_status,
                    cv.proxycheck_score, cv.proxycheck_is_proxy, cv.proxycheck_is_vpn,
                    cv.frauddefense_score, cv.frauddefense_status,
                    cv.fraudlabspro_score, cv.fraudlabspro_flp_status
             FROM conversions cv
             LEFT JOIN offers o ON o.id = cv.offer_id
             JOIN affiliates af ON af.id = cv.affiliate_id
             JOIN users u ON u.id = af.user_id
             WHERE $whereSQL
             ORDER BY $orderClause
             LIMIT 2000",
            $params
        );
    } catch (\Throwable $e) { $conversions = []; }

    try {
        $statsRaw = Database::fetchOne(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN cv.fraud_checked_at IS NOT NULL THEN 1 ELSE 0 END) as checked,
                    SUM(CASE WHEN cv.fraud_checked_at IS NULL THEN 1 ELSE 0 END) as pending_check,
                    AVG(CASE WHEN cv.fraud_checked_at IS NOT NULL AND cv.fraud_score > 0 THEN cv.fraud_score ELSE NULL END) as avg_score,
                    MAX(CASE WHEN cv.fraud_checked_at IS NOT NULL THEN cv.fraud_score ELSE NULL END) as max_score,
                    SUM(CASE WHEN COALESCE(cv.fraud_score,0) >= 75 AND cv.fraud_checked_at IS NOT NULL THEN 1 ELSE 0 END) as high_risk
             FROM conversions cv WHERE COALESCE(cv.is_hidden, 0) = 0"
        );
        $stats = [
            'total_conversions' => (int)($statsRaw['total'] ?? 0),
            'pending_check' => (int)($statsRaw['pending_check'] ?? 0),
            'avg_fraud_score' => round((float)($statsRaw['avg_score'] ?? 0), 1),
            'max_score_seen' => (int)($statsRaw['max_score'] ?? 0),
            'high_risk' => (int)($statsRaw['high_risk'] ?? 0),
        ];
    } catch (\Throwable $e) { 
        $stats = ['total_conversions' => 0, 'pending_check' => 0, 'avg_fraud_score' => 0, 'max_score_seen' => 0, 'high_risk' => 0]; 
    }

    try {
        $affiliates = Database::fetchAll("SELECT af.id, CONCAT(u.first_name,' ',u.last_name) as name FROM affiliates af JOIN users u ON u.id = af.user_id WHERE u.status = 'active' ORDER BY u.first_name");
    } catch (\Throwable $e) { $affiliates = []; }

    try {
        $offers = Database::fetchAll("SELECT id, name FROM offers WHERE status='active' ORDER BY name");
    } catch (\Throwable $e) { $offers = []; }

    Helpers::jsonResponse([
        'status' => 'success',
        'stats' => $stats,
        'conversions' => $conversions,
        'affiliates' => $affiliates,
        'offers' => $offers
    ]);
}

Helpers::jsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
