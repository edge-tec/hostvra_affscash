<?php
/**
 * Cron: Batch fraud scan for recent unscored clicks AND pending conversions
 * Run every 5 minutes: * /5 * * * * php /path/to/cron/fraud_scan.php
 *
 * This cron handles two tasks:
 *  1. Score unscored clicks from the last 24h
 *  2. Re-score conversions where fraud_checked_at IS NULL (missed by postback.php)
 */
// Bootstrap-safe: this file can be triggered three ways —
//   1. `php cron/fraud_scan.php` from a real crontab (CLI)
//   2. `php -q ...` from cPanel "Cron Jobs" (CLI)
//   3. HTTPS GET `/cron/fraud-scan?token=…` from a browser or cPanel
//      "Cron URL" (web). In that case index.php has already defined
//      BASE_PATH / CONFIG_PATH and loaded the core classes — so the
//      bootstrap below uses defined() + require_once guards.
defined('BASE_PATH')   || define('BASE_PATH',   dirname(__DIR__));
defined('CONFIG_PATH') || define('CONFIG_PATH', BASE_PATH . '/config');
require_once BASE_PATH . '/core/Config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Helpers.php';
require_once BASE_PATH . '/core/FraudIQ.php';
try { Config::init(CONFIG_PATH); } catch (\Throwable $_) {}
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

// Ensure background execution completes even if the caller disconnects
ignore_user_abort(true);

// ── Prevent overlapping executions (Concurrency Lock) ─────────────────────
$lockFile = BASE_PATH . '/logs/fraud_scan.lock';
if (!is_dir(dirname($lockFile))) @mkdir(dirname($lockFile), 0777, true);
$lockFp = fopen($lockFile, 'w+');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "Scan already running. Exiting.\n";
    exit;
}

$cfg         = Config::get('fraud') ?? [];
$ipqsEnabled = !empty($cfg['ipqs_api_key']) && (bool)($cfg['ipqs_enabled'] ?? false);

// ── Ensure all fraud provider columns exist ───────────────────────────────
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_score`        TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraud_checked_at`   DATETIME         DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_risk_score`  TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_risk_level`  VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_vpn`         TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_proxy`       TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_tor`         TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `ipquery_datacenter`  TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `scamalytics_score`   TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `scamalytics_status`  VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `proxycheck_score`    TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `proxycheck_is_proxy` TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `proxycheck_is_vpn`   TINYINT(1)       DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `frauddefense_score`  TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `frauddefense_status` VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraudlabspro_score`      TINYINT UNSIGNED DEFAULT NULL"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE `conversions` ADD COLUMN `fraudlabspro_flp_status` VARCHAR(10)      DEFAULT NULL"); } catch (\Throwable $_e) {}

// ── Part 1: Score conversions missing ANY provider score ──────────────────
// Catches conversions where postback.php failed, AND existing conversions
// that only have IPQS but are missing the newer provider columns.
$pendingConversions = Database::fetchAll(
    "SELECT cv.conversion_id, cv.click_id, cv.ip_address, cv.affiliate_id, cv.payout
     FROM conversions cv
     WHERE (cv.fraud_checked_at IS NULL
            OR cv.ipquery_risk_score IS NULL
            OR cv.scamalytics_score IS NULL
            OR cv.proxycheck_score IS NULL
            OR cv.frauddefense_score IS NULL
            OR cv.fraudlabspro_score IS NULL)
       AND COALESCE(cv.is_hidden, 0) = 0
       AND cv.converted_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY cv.converted_at DESC
     LIMIT 50"
);

$checkedConversions = 0;
foreach ($pendingConversions as $conv) {
    $ip       = trim($conv['ip_address'] ?? '');
    $ipValid  = ($ip !== '' && $ip !== '0.0.0.0' && filter_var($ip, FILTER_VALIDATE_IP) !== false);
    $ipPublic = $ipValid && filter_var($ip, FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

    // ── IPQuery.io — FREE, no key, works on all valid IPs incl. IPv6 ─────
    if ($ipValid) {
        try {
            $_ipqR = FraudIQ::checkIPQuery($ip);
            Database::query(
                "UPDATE `conversions`
                    SET `ipquery_risk_score` = ?,
                        `ipquery_risk_level` = ?,
                        `ipquery_vpn`        = ?,
                        `ipquery_proxy`      = ?,
                        `ipquery_tor`        = ?,
                        `ipquery_datacenter` = ?
                  WHERE `conversion_id` = ?",
                [$_ipqR['risk_score'], $_ipqR['risk_level'], $_ipqR['is_vpn'],
                 $_ipqR['is_proxy'], $_ipqR['is_tor'], $_ipqR['is_datacenter'],
                 $conv['conversion_id']]
            );
        } catch (\Throwable $_e) {
            try { Database::query("UPDATE `conversions` SET `ipquery_risk_score`=0,`ipquery_risk_level`='low' WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
        }
    } else {
        try { Database::query("UPDATE `conversions` SET `ipquery_risk_score`=0,`ipquery_risk_level`='low' WHERE `conversion_id`=? AND `ipquery_risk_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
    }

    // ── IPQS ─────────────────────────────────────────────────────────────
    if ($ipqsEnabled && $ipValid) {
        try {
            $result = FraudIQ::checkIPQS($ip, $conv['click_id']);
            $score  = (int)($result['score']  ?? 0);
            $action = (string)($result['action'] ?? 'allow');
            Database::query(
                "UPDATE `conversions` SET `fraud_score`=?, `fraud_checked_at`=NOW() WHERE `conversion_id`=?",
                [$score, $conv['conversion_id']]
            );
            if ($score > 0) {
                try {
                    Database::query(
                        "UPDATE `clicks` SET `fraud_score`=? WHERE `click_id`=? AND COALESCE(`fraud_score`,0) < ?",
                        [$score, $conv['click_id'], $score]
                    );
                } catch (\Throwable $_e) {}
                if ($action !== 'allow') {
                    try { Database::update('clicks', ['is_fraud' => 1], 'click_id=? AND is_fraud=0', [$conv['click_id']]); } catch (\Throwable $_e) {}
                }
            }
        } catch (\Throwable $_e) {
            try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
        }
    } else {
        try { Database::query("UPDATE `conversions` SET `fraud_checked_at`=NOW() WHERE `conversion_id`=? AND `fraud_checked_at` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
    }

    // ── Scamalytics ───────────────────────────────────────────────────────
    if (!empty($cfg['scamalytics_api_key']) && !empty($cfg['scamalytics_user']) && $ipPublic) {
        if (!array_key_exists('scamalytics_enabled', $cfg) || (bool)$cfg['scamalytics_enabled']) {
            try {
                $_scR = FraudIQ::checkFraud($ip);
                Database::query(
                    "UPDATE `conversions` SET `scamalytics_score`=?,`scamalytics_status`=?,`scamalytics_mode`=? WHERE `conversion_id`=?",
                    [$_scR['score'], $_scR['status'], $_scR['mode'] ?? 'score_only', $conv['conversion_id']]
                );
            } catch (\Throwable $_e) {
                try { Database::query("UPDATE `conversions` SET `scamalytics_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `scamalytics_score`=0 WHERE `conversion_id`=? AND `scamalytics_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }
    } else {
        try { Database::query("UPDATE `conversions` SET `scamalytics_score`=0 WHERE `conversion_id`=? AND `scamalytics_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
    }

    // ── ProxyCheck.io ─────────────────────────────────────────────────────
    if (!empty($cfg['proxycheck_api_key']) && $ipPublic) {
        if (!array_key_exists('proxycheck_enabled', $cfg) || (bool)$cfg['proxycheck_enabled']) {
            try {
                $_pcR = FraudIQ::checkProxyCheck($ip);
                Database::query(
                    "UPDATE `conversions` SET `proxycheck_score`=?,`proxycheck_is_proxy`=?,`proxycheck_is_vpn`=?,`proxycheck_status`=? WHERE `conversion_id`=?",
                    [$_pcR['score'], $_pcR['is_proxy'], $_pcR['is_vpn'], $_pcR['status'], $conv['conversion_id']]
                );
            } catch (\Throwable $_e) {
                try { Database::query("UPDATE `conversions` SET `proxycheck_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `proxycheck_score`=0 WHERE `conversion_id`=? AND `proxycheck_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }
    } else {
        try { Database::query("UPDATE `conversions` SET `proxycheck_score`=0 WHERE `conversion_id`=? AND `proxycheck_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
    }

    // ── FraudDefense.io ───────────────────────────────────────────────────
    if (!empty($cfg['frauddefense_api_key']) && $ipPublic) {
        if (!array_key_exists('frauddefense_enabled', $cfg) || (bool)$cfg['frauddefense_enabled']) {
            try {
                $_fdR = FraudIQ::checkFraudDefense($ip);
                Database::query(
                    "UPDATE `conversions` SET `frauddefense_score`=?,`frauddefense_status`=? WHERE `conversion_id`=?",
                    [$_fdR['score'], $_fdR['status'], $conv['conversion_id']]
                );
            } catch (\Throwable $_e) {
                try { Database::query("UPDATE `conversions` SET `frauddefense_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `frauddefense_score`=0 WHERE `conversion_id`=? AND `frauddefense_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }
    } else {
        try { Database::query("UPDATE `conversions` SET `frauddefense_score`=0 WHERE `conversion_id`=? AND `frauddefense_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
    }

    // ── FraudLabs Pro ─────────────────────────────────────────────────────
    if (!empty($cfg['fraudlabspro_api_key']) && $ipPublic) {
        if (!array_key_exists('fraudlabspro_enabled', $cfg) || (bool)$cfg['fraudlabspro_enabled']) {
            try {
                $_flR = FraudIQ::checkFraudLabsPro($ip);
                Database::query(
                    "UPDATE `conversions` SET `fraudlabspro_score`=?,`fraudlabspro_status`=?,`fraudlabspro_flp_status`=? WHERE `conversion_id`=?",
                    [$_flR['score'], $_flR['status'], $_flR['flp_status'], $conv['conversion_id']]
                );
            } catch (\Throwable $_e) {
                try { Database::query("UPDATE `conversions` SET `fraudlabspro_score`=0 WHERE `conversion_id`=?", [$conv['conversion_id']]); } catch (\Throwable $_e2) {}
            }
        } else {
            try { Database::query("UPDATE `conversions` SET `fraudlabspro_score`=0 WHERE `conversion_id`=? AND `fraudlabspro_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
        }
    } else {
        try { Database::query("UPDATE `conversions` SET `fraudlabspro_score`=0 WHERE `conversion_id`=? AND `fraudlabspro_score` IS NULL", [$conv['conversion_id']]); } catch (\Throwable $_e) {}
    }

    $checkedConversions++;
    usleep(300000); // 300ms rate limiting between API calls
}

// ── Part 2: Score unscored clicks from last 24h (IPQS only) ──────────────
$flagged = 0;
if ($ipqsEnabled) {
    $clicks = Database::fetchAll(
        "SELECT click_id, ip_address FROM clicks WHERE COALESCE(fraud_score,0)=0 AND is_fraud=0 AND clicked_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND status='valid' LIMIT 50"
    );

    foreach ($clicks as $click) {
        $ip = trim($click['ip_address'] ?? '');
        if ($ip === '' || $ip === '0.0.0.0' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            usleep(50000);
            continue;
        }

        try {
            $result = FraudIQ::checkIPQS($ip, $click['click_id']);
        } catch (\Throwable $_e) {
            usleep(200000);
            continue;
        }

        $score  = (int)($result['score'] ?? 0);
        $action = $result['action'] ?? 'allow';

        if ($action !== 'allow') {
            Database::update('clicks', [
                'is_fraud'    => 1,
                'fraud_score' => $score,
                'status'      => $action === 'block' ? 'blocked' : 'valid',
            ], 'click_id=?', [$click['click_id']]);
            $flagged++;
        } elseif ($score > 0) {
            Database::update('clicks', ['fraud_score' => $score], 'click_id=? AND COALESCE(fraud_score,0)=0', [$click['click_id']]);
        }

        usleep(200000); // 200ms rate limiting
    }
} else {
    $clicks = [];
}

echo "Conversions checked: {$checkedConversions}. Clicks scanned: " . count($clicks) . ". Flagged: {$flagged}.\n";
