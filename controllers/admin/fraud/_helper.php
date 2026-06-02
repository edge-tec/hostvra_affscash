<?php
/**
 * Shared helper for In-House Fraud Detection System.
 * READ-ONLY against all existing tracking tables.
 * Writes only to: fraud_cases, fraud_rules, fraud_blocklist.
 */

if (!defined('BASE_PATH')) die();

// ── Table bootstrap (run once per request) ───────────────────────────────────
function fraud_ensure_tables(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $sqls = [
        "CREATE TABLE IF NOT EXISTS `fraud_cases` (
            `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `case_ref`     VARCHAR(20)  NOT NULL,
            `type`         ENUM('click_spam','bot_traffic','fake_conversion','device_abuse','ip_abuse','geo_fraud','duplicate_conv') NOT NULL DEFAULT 'click_spam',
            `affiliate_id` INT UNSIGNED DEFAULT NULL,
            `reference_id` VARCHAR(255) DEFAULT NULL,
            `severity`     ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
            `status`       ENUM('open','investigating','resolved','dismissed') NOT NULL DEFAULT 'open',
            `fraud_score`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `signals`      JSON DEFAULT NULL,
            `notes`        TEXT DEFAULT NULL,
            `assigned_to`  INT UNSIGNED DEFAULT NULL,
            `resolved_at`  DATETIME DEFAULT NULL,
            `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_affiliate` (`affiliate_id`),
            INDEX `idx_status`    (`status`),
            INDEX `idx_severity`  (`severity`),
            INDEX `idx_ref`       (`reference_id`(100)),
            INDEX `idx_created`   (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `fraud_rules` (
            `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name`           VARCHAR(100) NOT NULL,
            `description`    VARCHAR(500) DEFAULT NULL,
            `rule_type`      ENUM('click_rate','low_cvr','geo_mismatch','bot_ua','conversion_time','ip_range','affiliate_age','high_cvr','datacenter_ip','duplicate_ip') NOT NULL,
            `condition_json` JSON NOT NULL,
            `action`         ENUM('flag','block','alert','auto_case') NOT NULL DEFAULT 'flag',
            `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
            `trigger_count`  INT UNSIGNED NOT NULL DEFAULT 0,
            `last_triggered` DATETIME DEFAULT NULL,
            `created_by`     INT UNSIGNED DEFAULT NULL,
            `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_active` (`is_active`),
            INDEX `idx_type`   (`rule_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `fraud_blocklist` (
            `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `type`       ENUM('ip','cidr','user_agent','affiliate_id','device_fp','asn') NOT NULL,
            `value`      VARCHAR(255) NOT NULL,
            `reason`     VARCHAR(255) DEFAULT NULL,
            `action`     ENUM('flag','block','throttle') NOT NULL DEFAULT 'block',
            `scope`      ENUM('clicks','conversions','all') NOT NULL DEFAULT 'all',
            `source`     ENUM('manual','auto_rule','imported') NOT NULL DEFAULT 'manual',
            `hit_count`  INT UNSIGNED NOT NULL DEFAULT 0,
            `expires_at` DATETIME DEFAULT NULL,
            `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
            `created_by` INT UNSIGNED DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_type_value` (`type`, `value`(100)),
            INDEX `idx_type_active` (`type`, `is_active`),
            INDEX `idx_active`      (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($sqls as $sql) {
        try { Database::query($sql); } catch (\Exception $e) {}
    }

    // Seed default rules if empty
    try {
        $cnt = Database::fetchOne("SELECT COUNT(*) AS c FROM fraud_rules");
        if (($cnt['c'] ?? 0) == 0) {
            $seeds = [
                ['Click Rate Burst',        'More than 20 clicks/min from same IP on same offer',    'click_rate',      '{"clicks_per_minute":20,"window_seconds":60}',   'flag'],
                ['Extremely Low CVR',       'Affiliate CVR below 0.05% over 7 days (min 200 clicks)','low_cvr',         '{"cvr_threshold":0.05,"min_clicks":200}',         'flag'],
                ['Fast Conversion (<10s)',  'Click-to-conversion under 10 seconds',                   'conversion_time', '{"max_seconds":10}',                              'block'],
                ['Duplicate Conversion IP', 'Same IP converts same offer twice in 24h',               'duplicate_ip',    '{"window_hours":24}',                             'flag'],
                ['New Affiliate Threshold', 'Affiliate age under 7 days with high activity',          'affiliate_age',   '{"max_age_days":7,"min_daily_clicks":100}',        'alert'],
                ['Datacenter IP',           'Conversion from known hosting/datacenter ASN',           'datacenter_ip',   '{}',                                              'flag'],
                ['Geo Mismatch',            'Click geo differs from conversion IP geo',               'geo_mismatch',    '{}',                                              'flag'],
            ];
            foreach ($seeds as [$name, $desc, $type, $cond, $action]) {
                Database::query(
                    "INSERT IGNORE INTO fraud_rules (name,description,rule_type,condition_json,action,is_active) VALUES (?,?,?,?,?,1)",
                    [$name, $desc, $type, $cond, $action]
                );
            }
        }
    } catch (\Exception $e) {}
}

// ── Risk score label helpers ─────────────────────────────────────────────────
function fraud_score_badge(int $score): string {
    if ($score >= 80) return '<span class="fds-badge fds-badge-critical">' . $score . '</span>';
    if ($score >= 60) return '<span class="fds-badge fds-badge-high">'     . $score . '</span>';
    if ($score >= 40) return '<span class="fds-badge fds-badge-medium">'   . $score . '</span>';
    return                    '<span class="fds-badge fds-badge-low">'      . $score . '</span>';
}

function fraud_severity_badge(string $s): string {
    $map = ['critical'=>'fds-badge-critical','high'=>'fds-badge-high','medium'=>'fds-badge-medium','low'=>'fds-badge-low'];
    $cls = $map[$s] ?? 'fds-badge-low';
    return '<span class="fds-badge '.$cls.'">'.ucfirst($s).'</span>';
}

function fraud_status_badge(string $s): string {
    $map = ['open'=>'fds-badge-critical','investigating'=>'fds-badge-high','resolved'=>'fds-badge-low','dismissed'=>'fds-badge-muted'];
    $cls = $map[$s] ?? 'fds-badge-muted';
    return '<span class="fds-badge '.$cls.'">'.ucfirst($s).'</span>';
}

// ── Case ref generator ───────────────────────────────────────────────────────
function fraud_new_case_ref(): string {
    return 'FC-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
}

// ── Date-range helper (used by every Fraud Detection page) ───────────────────
// Reads `from` / `to` from the query string and returns absolute datetimes
// suitable for SQL BETWEEN comparisons. When the caller doesn't pass dates,
// the default window is the current month (matches the rest of admin reporting).
// Pages that need a different default (e.g. Live Monitor — last 60 min) should
// pass their own $defaultFrom / $defaultTo.
function fraud_date_range(?string $defaultFrom = null, ?string $defaultTo = null): array {
    $from = trim((string)(Helpers::get('from') ?? ''));
    $to   = trim((string)(Helpers::get('to')   ?? ''));
    if ($from === '') $from = $defaultFrom ?? date('Y-m-01');
    if ($to   === '') $to   = $defaultTo   ?? date('Y-m-d');
    return [
        'from'     => $from,
        'to'       => $to,
        'date_from'=> date('Y-m-d 00:00:00', strtotime($from)),
        'date_to'  => date('Y-m-d 23:59:59', strtotime($to)),
    ];
}

// ── Pagination helper ────────────────────────────────────────────────────────
function fraud_paginate(int $total, int $perPage, int $page): array {
    $pages = max(1, (int)ceil($total / $perPage));
    $page  = max(1, min($page, $pages));
    return ['total' => $total, 'per_page' => $perPage, 'page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage];
}

function fds_pagination(array $pag, string $baseUrl, array $extra = []): string {
    if ($pag['pages'] <= 1) return '';
    $qs = $extra;
    $html = '<div class="fds-pagination" style="display:flex; gap:4px; flex-wrap:wrap; align-items:center; font-size:13px;">';
    
    $cur = (int)$pag['page'];
    $max = (int)$pag['pages'];
    
    $btn = function($p, $label, $active=false, $disabled=false) use ($baseUrl, $qs) {
        if ($disabled) {
            return '<span class="fds-page-btn" style="opacity:0.5; cursor:not-allowed; padding:4px 8px; border:1px solid transparent; color:var(--text-muted);">'.$label.'</span>';
        }
        $qs['page'] = $p;
        $url = $baseUrl . '?' . http_build_query($qs);
        $cls = 'fds-page-btn' . ($active ? ' active' : '');
        $style = 'padding:4px 8px; border:1px solid ' . ($active ? 'var(--primary)' : 'var(--border)') . '; border-radius:4px; text-decoration:none; color:' . ($active ? '#fff' : 'var(--text)') . '; background:' . ($active ? 'var(--primary)' : '#fff') . ';';
        return '<a href="'.htmlspecialchars($url, ENT_QUOTES).'" class="'.$cls.'" style="'.$style.'">'.$label.'</a>';
    };

    $html .= $btn(1, 'First', false, $cur <= 1);
    $html .= $btn($cur - 1, 'Prev', false, $cur <= 1);

    $start = max(1, $cur - 2);
    $end = min($max, $cur + 2);

    if ($start > 1) {
        $html .= '<span style="color:var(--text-muted); padding:0 4px;">...</span>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $html .= $btn($i, (string)$i, $i === $cur);
    }

    if ($end < $max) {
        $html .= '<span style="color:var(--text-muted); padding:0 4px;">...</span>';
    }

    $html .= $btn($cur + 1, 'Next', false, $cur >= $max);
    $html .= $btn($max, 'Last', false, $cur >= $max);

    $html .= '</div>';
    return $html;
}
