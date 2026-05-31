<?php
/**
 * Landing Page Data API
 * Serves slider and offers data for the public landing page.
 * Works standalone (bypasses index.php router since it's a real file).
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

// Fallback if not installed
if (!file_exists(CONFIG_PATH . '/config.json')) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';

Config::init(CONFIG_PATH);

$resource = $_GET['resource'] ?? 'offers';

// ── SLIDER DATA ───────────────────────────────────────────────────────────────
if ($resource === 'slider') {
    $slides = [];
    try {
        // Ensure table exists
        Database::query("CREATE TABLE IF NOT EXISTS `landing_sliders` (
            `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title`      VARCHAR(255) NOT NULL,
            `subtitle`   TEXT,
            `image`      VARCHAR(512) DEFAULT NULL,
            `link`       VARCHAR(512) DEFAULT '/tracker/register.php',
            `badge1`     VARCHAR(50) DEFAULT NULL,
            `badge2`     VARCHAR(50) DEFAULT NULL,
            `sort_order` INT DEFAULT 0,
            `status`     ENUM('active','inactive') DEFAULT 'active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_status` (`status`),
            INDEX `idx_sort`   (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Primary: landing_sliders managed by admin
        $rows = Database::fetchAll(
            "SELECT id, title, subtitle, image, link, badge1, badge2
             FROM landing_sliders
             WHERE status = 'active'
             ORDER BY sort_order ASC, id DESC
             LIMIT 8"
        );
        if ($rows) {
            foreach ($rows as $r) {
                $badges = [];
                if ($r['badge1']) $badges[] = $r['badge1'];
                if ($r['badge2']) $badges[] = $r['badge2'];
                $slides[] = [
                    'id'     => (int)$r['id'],
                    'name'   => $r['title'],
                    'desc'   => $r['subtitle'] ?? '',
                    'image'  => $r['image'] ?? '',
                    'link'   => $r['link'] ?: '/tracker/register.php',
                    'badges' => $badges,
                ];
            }
        }

        // Fallback: pull from active offers with images
        if (empty($slides)) {
            $rows = Database::fetchAll(
                "SELECT id, name, description, thumbnail AS image_url FROM offers
                 WHERE status = 'active' AND thumbnail != '' AND thumbnail IS NOT NULL
                 ORDER BY RAND() LIMIT 6"
            );
            if ($rows) {
                foreach ($rows as $r) {
                    $slides[] = [
                        'id'     => (int)$r['id'],
                        'name'   => $r['name'],
                        'desc'   => $r['description'] ?? '',
                        'image'  => $r['image_url'],
                        'link'   => '/tracker/register.php',
                        'badges' => [],
                    ];
                }
            }
        }
    } catch (\Throwable $e) { /* static fallback below */ }

    // Static fallback when no DB data
    if (empty($slides)) {
        $slides = [
            ['name'=>'High-Converting CPA Offers',    'desc'=>'300+ premium offers across all verticals',                'image'=>'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&q=80','link'=>'/tracker/register.php','badges'=>['hot','top']],
            ['name'=>'Real-Time Analytics Dashboard', 'desc'=>'Track every click, conversion and earning live',           'image'=>'https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?w=800&q=80','link'=>'/tracker/register.php','badges'=>['top']],
            ['name'=>'Global Smartlink Technology',   'desc'=>'Auto GEO optimization — zero traffic loss, maximum EPC',  'image'=>'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&q=80','link'=>'/tracker/register.php','badges'=>[]],
            ['name'=>'Fast & Reliable Payouts',       'desc'=>'Net-7, Net-15, Net-30 — Wire, PayPal, Crypto and more',   'image'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=800&q=80','link'=>'/tracker/register.php','badges'=>['hot']],
        ];
    }

    echo json_encode(['success' => true, 'data' => $slides]);
    exit;
}

// ── OFFERS DATA ───────────────────────────────────────────────────────────────
$offers = [];

// Shared: category + offer_type → landing page tab
$CATEGORY_MAP = [
    // category field values
    'dating'    => 'soi',
    'soi'       => 'soi',
    'doi'       => 'doi',
    'casino'    => 'casino',
    'cam'       => 'cam',
    'software'  => 'software',
    'financial' => 'financial',
    'finance'   => 'financial',
    'cps'       => 'cps',
    'smartlink' => 'smartlink',
    // offer_type field values (in-house offer types)
    'SOI'       => 'soi',
    'DOI'       => 'doi',
    'CPL'       => 'soi',
    'CPS'       => 'cps',
    'CPI'       => 'software',
    'CPA'       => 'soi',
    'COD'       => 'financial',
    'FINANCE'   => 'financial',
    'CPM'       => 'soi',
    'CPC'       => 'soi',
    'REVSHARE'  => 'smartlink',
    'TRIAL'     => 'soi',
];

// payout_type → CSS gradient variable
$PAY_STYLE_MAP = [
    'RevShare' => '--grad-green',
    'CPS'      => '--grad-gold',
    'DOI'      => '--grad-warm',
    'SOI'      => '--grad-gold',
    'REVSHARE' => '--grad-green',
];

// Ensure columns exist (schema guards — safe to run on every request)
try { Database::query("ALTER TABLE offers ADD COLUMN is_inhouse TINYINT(1) NOT NULL DEFAULT 0"); } catch (\Throwable $_e) {}
try { Database::query("ALTER TABLE offers ADD COLUMN offer_type VARCHAR(50) NOT NULL DEFAULT ''"); } catch (\Throwable $_e) {}

try {
    // ── 1. Regular + In-House offers ────────────────────────────────────────
    $rows = Database::fetchAll(
        "SELECT o.id, o.name, o.description, o.thumbnail AS image_url,
                o.payout_amount, o.payout_type, o.category,
                o.offer_type, o.is_inhouse,
                o.geo_targeting AS allowed_countries
         FROM offers o
         WHERE o.status = 'active'
         ORDER BY o.is_inhouse DESC, o.id DESC
         LIMIT 120"
    );

    if ($rows) {
        $seenOfferIds = [];
        foreach ($rows as $r) {
            $oid = (int)$r['id'];
            if (isset($seenOfferIds[$oid])) continue;
            $seenOfferIds[$oid] = true;

            $catRaw    = strtolower($r['category'] ?? '');
            $offerType = trim($r['offer_type'] ?? '');
            // Determine landing tab:
            // offer_type DOI/SOI takes strict priority so DOI offers never land in the SOI section.
            // For all other offer types fall back to category → offer_type → default.
            if ($offerType === 'DOI') {
                $cat = 'doi';
            } elseif ($offerType === 'SOI') {
                $cat = 'soi';
            } else {
                $cat = $CATEGORY_MAP[$catRaw]
                    ?? $CATEGORY_MAP[$offerType]
                    ?? 'soi';
            }

            $payType = $r['payout_type'] ?? 'CPA';
            $payout  = (float)($r['payout_amount'] ?? 0);
            $payoutDisplay = $payout > 0 ? '$' . number_format($payout, 2) : 'Ask AM';
            $payStyle = $PAY_STYLE_MAP[$offerType] ?? $PAY_STYLE_MAP[$payType] ?? '--grad-gold';

            // Sub-label: prefer offer_type, then category, then payout_type
            $subLabel = $offerType ?: ($catRaw ? ucfirst($catRaw) : $payType);
            $sub      = $subLabel . ($r['is_inhouse'] ? ' · In-House' : (' · ' . $payType));

            $geoRaw  = $r['allowed_countries'] ?? null;
            $geosArr = [];
            if ($geoRaw) {
                $decoded = json_decode($geoRaw, true);
                if (is_array($decoded)) {
                    $geosArr = array_filter($decoded);
                } else {
                    $geosArr = array_filter(array_map('trim', preg_split('/[\s,]+/', $geoRaw)));
                }
            }
            if (empty($geosArr)) $geosArr = ['WW'];

            $offers[] = [
                'id'           => $oid,
                'name'         => $r['name'],
                'cat'          => $cat,
                'sub'          => $sub,
                'geos'         => array_values($geosArr),
                'payout'       => (string)$payout,
                'payoutDisplay'=> $payoutDisplay,
                'payStyle'     => $payStyle,
                'hot'          => !empty($r['is_inhouse']),
                'top'          => false,
                'isNew'        => false,
                'img'          => $r['image_url'] ?: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&q=70',
                'source'       => $r['is_inhouse'] ? 'inhouse' : 'regular',
            ];
        }
    }

    // ── 2. Smartlinks ────────────────────────────────────────────────────────
    $slRows = Database::fetchAll(
        "SELECT sl.id, sl.name, sl.description, sl.slug, sl.rotation_type,
                COUNT(so.id) AS offer_count,
                MAX(o.payout_amount) AS max_payout
         FROM smartlinks sl
         LEFT JOIN smartlink_offers so ON so.smartlink_id = sl.id
         LEFT JOIN offers o ON o.id = so.offer_id AND o.status = 'active'
         WHERE sl.status = 'active'
         GROUP BY sl.id
         ORDER BY sl.id DESC
         LIMIT 30"
    );

    if ($slRows) {
        // Use a large offset for smartlink IDs to avoid collision with offer IDs in the JS
        foreach ($slRows as $r) {
            $maxPayout = (float)($r['max_payout'] ?? 0);
            $payoutDisplay = $maxPayout > 0 ? 'Up to $' . number_format($maxPayout, 2) : 'Multi-GEO';
            $offerCount = (int)($r['offer_count'] ?? 0);

            $offers[] = [
                'id'           => 'SL' . $r['id'],
                'name'         => $r['name'],
                'cat'          => 'smartlink',
                'sub'          => 'Smartlink · ' . ucfirst($r['rotation_type'] ?? 'weighted') . ($offerCount > 0 ? ' · ' . $offerCount . ' offers' : ''),
                'geos'         => ['WW'],
                'payout'       => (string)$maxPayout,
                'payoutDisplay'=> $payoutDisplay,
                'payStyle'     => '--grad-green',
                'hot'          => true,
                'top'          => false,
                'isNew'        => false,
                'img'          => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70',
                'source'       => 'smartlink',
                'slug'         => $r['slug'],
            ];
        }
    }
} catch (\Throwable $e) { /* fallback below */ }

// Fallback: sample-offers.json
if (empty($offers)) {
    $sampleFile = BASE_PATH . '/sample-offers.json';
    if (file_exists($sampleFile)) {
        $json = json_decode(file_get_contents($sampleFile), true);
        if (is_array($json)) {
            foreach ($json as $o) {
                $geos    = trim($o['countries'] ?? 'WW');
                $geosArr = ($geos === 'Worldwide' || empty($geos))
                    ? ['WW']
                    : array_filter(array_map('trim', explode(' ', $geos)));
                $badges  = array_filter(array_map('trim', explode(',', $o['badges'] ?? '')));
                $offers[] = [
                    'id'           => (int)($o['id'] ?? 0),
                    'name'         => $o['name'] ?? '',
                    'cat'          => strtolower($o['cat'] ?? 'soi'),
                    'sub'          => $o['vertical'] ?? '',
                    'geos'         => array_values($geosArr),
                    'payout'       => (string)($o['payout'] ?? 0),
                    'payoutDisplay'=> $o['payoutDisplay'] ?? ('$' . $o['payout']),
                    'payStyle'     => '--grad-gold',
                    'hot'          => in_array('hot', $badges),
                    'top'          => in_array('top', $badges),
                    'isNew'        => in_array('new', $badges),
                    'img'          => $o['image'] ?: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&q=70',
                ];
            }
        }
    }
}

// Final hardcoded fallback
if (empty($offers)) {
    $offers = [
        ['id'=>286,'name'=>'CharmDate Hot',      'cat'=>'soi',      'sub'=>'Dating · Tier-1',       'geos'=>['US','GB','CH','DK','FI'],'payout'=>'9.00', 'payoutDisplay'=>'$9.00', 'payStyle'=>'--grad-gold', 'hot'=>true,'top'=>true,'img'=>'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70'],
        ['id'=>62, 'name'=>'Super Smartlink',    'cat'=>'smartlink','sub'=>'Dating · Global',       'geos'=>['WW'],                   'payout'=>'2.00', 'payoutDisplay'=>'80% Rev','payStyle'=>'--grad-green','hot'=>true,'top'=>true,'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>289,'name'=>'uMobix.org',         'cat'=>'software', 'sub'=>'Monitoring App · CPS',  'geos'=>['AU','CA','GB','US'],     'payout'=>'42.00','payoutDisplay'=>'$42.00','payStyle'=>'--grad-cool', 'hot'=>true,'top'=>true,'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>296,'name'=>'Ckwin Casino',       'cat'=>'casino',   'sub'=>'Casino · Per Sale',     'geos'=>['WW'],                   'payout'=>'25.00','payoutDisplay'=>'$25.00','payStyle'=>'--grad-gold', 'hot'=>true,'top'=>true,'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>281,'name'=>'VideoChat FTD',      'cat'=>'cam',      'sub'=>'Cam · FTD',             'geos'=>['AU','CA','GB','US'],     'payout'=>'15.00','payoutDisplay'=>'$15.00','payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)','hot'=>true,'top'=>true,'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>285,'name'=>'PersonalLoan24',     'cat'=>'financial','sub'=>'Loan Offers · US',      'geos'=>['US'],                   'payout'=>'80',   'payoutDisplay'=>'80% Rev','payStyle'=>'--grad-green','hot'=>true,'top'=>true,'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>255,'name'=>'Maturedates UK',     'cat'=>'doi',      'sub'=>'Mature Dating · UK',    'geos'=>['GB'],                   'payout'=>'5.00', 'payoutDisplay'=>'$5.00', 'payStyle'=>'--grad-warm', 'hot'=>true,'top'=>true,'img'=>'https://images.unsplash.com/photo-1478720568477-152d9b164e26?w=400&q=70'],
        ['id'=>285,'name'=>'UkrainianGirlsDate', 'cat'=>'soi',      'sub'=>'Dating · Tier-1',       'geos'=>['AU','CA','GB','US'],     'payout'=>'5.00', 'payoutDisplay'=>'$5.00', 'payStyle'=>'--grad-gold', 'hot'=>true,'top'=>true,'img'=>'https://images.unsplash.com/photo-1554151228-14d9def656e4?w=400&q=70'],
    ];
}

echo json_encode(['success' => true, 'data' => $offers, 'count' => count($offers)]);
