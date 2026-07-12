<?php
/**
 * Landing Page Data API
 * Serves slider and offers data for the public landing page.
 * Works standalone (bypasses index.php router since it's a real file).
 */
header('Content-Type: application/json');
// Restrict CORS to configured app domain instead of wildcard
$_apiOrigin = '';
if (file_exists(CONFIG_PATH . '/config.json')) {
    $_cfgRaw = json_decode(file_get_contents(CONFIG_PATH . '/config.json'), true);
    $_apiOrigin = rtrim($_cfgRaw['app']['url'] ?? '', '/');
}
if ($_apiOrigin) {
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($requestOrigin && stripos($requestOrigin, parse_url($_apiOrigin, PHP_URL_HOST)) !== false) {
        header('Access-Control-Allow-Origin: ' . $requestOrigin);
    } else {
        header('Access-Control-Allow-Origin: ' . $_apiOrigin);
    }
} else {
    header('Access-Control-Allow-Origin: *');
}
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
                    'sub'          => $o['sub'] ?? ($o['vertical'] ?? ''),
                    'geos'         => array_values($geosArr),
                    'payout'       => (string)($o['payout'] ?? 0),
                    'payoutDisplay'=> $o['payoutDisplay'] ?? ('$' . $o['payout']),
                    'payStyle'     => $o['payStyle'] ?? '--grad-gold',
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
        ['id'=>301, 'name'=>'CharmDate Pro', 'cat'=>'soi', 'sub'=>'Dating · Global · SOI', 'geos'=>['WW'], 'payout'=>'4.99', 'payoutDisplay'=>'$4.99', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70'],
        ['id'=>302, 'name'=>'Maturedates Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['IE', 'NL', 'SE'], 'payout'=>'8.00', 'payoutDisplay'=>'$8.00', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>303, 'name'=>'Super Smartlink Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'76', 'payoutDisplay'=>'76% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>304, 'name'=>'Ckwin Casino Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['AU', 'FR', 'IE', 'NZ', 'ZA'], 'payout'=>'99.31', 'payoutDisplay'=>'$99.31', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>305, 'name'=>'VideoChat FTD Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['AU', 'GB', 'NZ', 'SE', 'SG'], 'payout'=>'13.85', 'payoutDisplay'=>'$13.85', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>306, 'name'=>'uMobix.org Global', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['ES', 'FI', 'JP', 'NZ', 'US'], 'payout'=>'35.83', 'payoutDisplay'=>'$35.83', 'payStyle'=>'--grad-cool', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>307, 'name'=>'PersonalLoan24 Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['AU', 'DE', 'IE', 'NL', 'SG'], 'payout'=>'17.53', 'payoutDisplay'=>'$17.53', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>308, 'name'=>'Maturedates CPS Hot', 'cat'=>'cps', 'sub'=>'Dating · Global · CPS', 'geos'=>['WW'], 'payout'=>'63.79', 'payoutDisplay'=>'$63.79', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>309, 'name'=>'UkrainianGirls Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['FR', 'IE', 'JP', 'SG'], 'payout'=>'2.16', 'payoutDisplay'=>'$2.16', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1554151228-14d9def656e4?w=400&q=70'],
        ['id'=>310, 'name'=>'FlirtChat Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['DK', 'FI', 'IT', 'NZ'], 'payout'=>'5.15', 'payoutDisplay'=>'$5.15', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>311, 'name'=>'GlobalMatch Smartlink Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'83', 'payoutDisplay'=>'83% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>312, 'name'=>'SpinCrown Casino Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['NL'], 'payout'=>'77.03', 'payoutDisplay'=>'$77.03', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>313, 'name'=>'LiveGirls Cam Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['AU', 'ES', 'SE'], 'payout'=>'35.55', 'payoutDisplay'=>'$35.55', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>314, 'name'=>'SecureVPN Pro Hot', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['CA', 'ES', 'JP'], 'payout'=>'17.14', 'payoutDisplay'=>'$17.14', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>315, 'name'=>'CryptoTrading Lead Global', 'cat'=>'financial', 'sub'=>'Financial · Global · SOI', 'geos'=>['WW'], 'payout'=>'17.78', 'payoutDisplay'=>'$17.78', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>316, 'name'=>'LoveArrow CPS Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['ZA'], 'payout'=>'25.13', 'payoutDisplay'=>'$25.13', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>317, 'name'=>'MatchMe Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['IT', 'NL', 'NO', 'SG'], 'payout'=>'4.76', 'payoutDisplay'=>'$4.76', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>318, 'name'=>'SeniorMatch Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['IE'], 'payout'=>'11.56', 'payoutDisplay'=>'$11.56', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>319, 'name'=>'Dating Smartlink V4 Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'75', 'payoutDisplay'=>'75% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>320, 'name'=>'VegasSlots Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['CA', 'NL'], 'payout'=>'119.44', 'payoutDisplay'=>'$119.44', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>321, 'name'=>'CamFantasy Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['GB', 'NO'], 'payout'=>'37.31', 'payoutDisplay'=>'$37.31', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>322, 'name'=>'CleanerTool Mobile Pro', 'cat'=>'software', 'sub'=>'Software · Global · CPI', 'geos'=>['WW'], 'payout'=>'10.00', 'payoutDisplay'=>'$10.00', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>323, 'name'=>'DebtRelief Express Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['AU', 'GB', 'NL', 'SE'], 'payout'=>'5.94', 'payoutDisplay'=>'$5.94', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>324, 'name'=>'AdultShop Purchase Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['AU', 'DE', 'IT', 'NL'], 'payout'=>'73.13', 'payoutDisplay'=>'$73.13', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>325, 'name'=>'LoveFinder Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['DK', 'ES', 'JP', 'SG'], 'payout'=>'6.85', 'payoutDisplay'=>'$6.85', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=400&q=70'],
        ['id'=>326, 'name'=>'MilfFinder Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['DK', 'ZA'], 'payout'=>'7.98', 'payoutDisplay'=>'$7.98', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>327, 'name'=>'AdultSmartlink Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'78', 'payoutDisplay'=>'78% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>328, 'name'=>'CryptoCasino Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['NZ', 'SG', 'US'], 'payout'=>'113.32', 'payoutDisplay'=>'$113.32', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>329, 'name'=>'AdultStream Live Hot', 'cat'=>'cam', 'sub'=>'Cam · Global · FTD', 'geos'=>['WW'], 'payout'=>'21.90', 'payoutDisplay'=>'$21.90', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>330, 'name'=>'DeviceMonitor Global', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['CH', 'NL', 'NZ'], 'payout'=>'16.23', 'payoutDisplay'=>'$16.23', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>331, 'name'=>'BinanceLeads Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['CA', 'FI', 'SG'], 'payout'=>'23.62', 'payoutDisplay'=>'$23.62', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>332, 'name'=>'Hookup Club Premium Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['DE', 'ES', 'NZ', 'SG'], 'payout'=>'85.51', 'payoutDisplay'=>'$85.51', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>333, 'name'=>'FlirtZone Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['FR', 'SE', 'US'], 'payout'=>'1.71', 'payoutDisplay'=>'$1.71', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=400&q=70'],
        ['id'=>334, 'name'=>'CasualHookups Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['CA', 'DK', 'GB', 'NL', 'SG'], 'payout'=>'6.69', 'payoutDisplay'=>'$6.69', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>335, 'name'=>'Mainstream Link Pro Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'77', 'payoutDisplay'=>'77% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>336, 'name'=>'LuckyBet FTD Global', 'cat'=>'casino', 'sub'=>'Casino · Global · FTD', 'geos'=>['WW'], 'payout'=>'157.80', 'payoutDisplay'=>'$157.80', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>337, 'name'=>'CamShow Token Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['CA', 'ES', 'IE', 'NL', 'US'], 'payout'=>'13.90', 'payoutDisplay'=>'$13.90', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>338, 'name'=>'SpyApp Pro Hot', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['DE', 'DK', 'ES', 'JP', 'NO'], 'payout'=>'22.23', 'payoutDisplay'=>'$22.23', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>339, 'name'=>'CreditScore Pro Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['CH', 'FI', 'FR', 'ZA'], 'payout'=>'30.79', 'payoutDisplay'=>'$30.79', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>340, 'name'=>'FlirtZone Subscription Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['CH', 'DE', 'ES', 'NL'], 'payout'=>'86.61', 'payoutDisplay'=>'$86.61', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>341, 'name'=>'SweetHearts Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['AU', 'CA'], 'payout'=>'6.04', 'payoutDisplay'=>'$6.04', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&q=70'],
        ['id'=>342, 'name'=>'DirectDates Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['DK', 'NL', 'SE'], 'payout'=>'8.28', 'payoutDisplay'=>'$8.28', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>343, 'name'=>'SmartRotation Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'80', 'payoutDisplay'=>'80% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>344, 'name'=>'JackpotCrown Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['FR', 'US', 'ZA'], 'payout'=>'165.77', 'payoutDisplay'=>'$165.77', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>345, 'name'=>'PrivateCams Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['DK', 'GB', 'NL', 'SE'], 'payout'=>'15.43', 'payoutDisplay'=>'$15.43', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>346, 'name'=>'AdBlocker Ultimate Pro', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['FI', 'NZ', 'SG'], 'payout'=>'36.69', 'payoutDisplay'=>'$36.69', 'payStyle'=>'--grad-cool', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>347, 'name'=>'InstantCash Loan Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['DE', 'ES', 'FI', 'IE', 'NZ'], 'payout'=>'72.72', 'payoutDisplay'=>'$72.72', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>348, 'name'=>'SecretAffair VIP Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['DE', 'GB', 'NZ', 'SG', 'ZA'], 'payout'=>'92.27', 'payoutDisplay'=>'$92.27', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>349, 'name'=>'SecretFlirts Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['IT'], 'payout'=>'4.94', 'payoutDisplay'=>'$4.94', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&q=70'],
        ['id'=>350, 'name'=>'SecretAffair Hot', 'cat'=>'doi', 'sub'=>'Dating · Global · DOI', 'geos'=>['WW'], 'payout'=>'6.09', 'payoutDisplay'=>'$6.09', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>351, 'name'=>'AutoGeo Link Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'80', 'payoutDisplay'=>'80% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>352, 'name'=>'MegaSpins Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['FI', 'SG'], 'payout'=>'179.54', 'payoutDisplay'=>'$179.54', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>353, 'name'=>'HookupCams Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['FI', 'FR', 'NZ', 'SE'], 'payout'=>'25.23', 'payoutDisplay'=>'$25.23', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>354, 'name'=>'NetShield VPN Global', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['CH', 'DE', 'FR', 'GB', 'SG'], 'payout'=>'13.38', 'payoutDisplay'=>'$13.38', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>355, 'name'=>'FastApproval Loans Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['CH', 'IT', 'JP', 'SG'], 'payout'=>'81.14', 'payoutDisplay'=>'$81.14', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>356, 'name'=>'CamShow Premium Pack Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['NO', 'SE'], 'payout'=>'75.98', 'payoutDisplay'=>'$75.98', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>357, 'name'=>'QuickHookup Global', 'cat'=>'soi', 'sub'=>'Dating · Global · SOI', 'geos'=>['WW'], 'payout'=>'5.68', 'payoutDisplay'=>'$5.68', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70'],
        ['id'=>358, 'name'=>'NaughtyFlirts Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['CA', 'CH', 'DK'], 'payout'=>'5.70', 'payoutDisplay'=>'$5.70', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>359, 'name'=>'EPC Booster Link Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'82', 'payoutDisplay'=>'82% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>360, 'name'=>'GoldReels Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['ES', 'FR', 'GB', 'JP', 'SE'], 'payout'=>'58.94', 'payoutDisplay'=>'$58.94', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>361, 'name'=>'ChatRub Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['CA', 'DE', 'NO'], 'payout'=>'30.54', 'payoutDisplay'=>'$30.54', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>362, 'name'=>'StorageCleaner Hot', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['CH', 'ES', 'FR', 'JP', 'NO'], 'payout'=>'47.81', 'payoutDisplay'=>'$47.81', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>363, 'name'=>'CryptoProfit System Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['FR', 'GB', 'NO', 'US'], 'payout'=>'107.24', 'payoutDisplay'=>'$107.24', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>364, 'name'=>'CougarLife Membership Pro', 'cat'=>'cps', 'sub'=>'Dating · Global · CPS', 'geos'=>['WW'], 'payout'=>'62.71', 'payoutDisplay'=>'$62.71', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>365, 'name'=>'CupidArrow Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['NO'], 'payout'=>'1.78', 'payoutDisplay'=>'$1.78', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1554151228-14d9def656e4?w=400&q=70'],
        ['id'=>366, 'name'=>'TrueLove Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['CH', 'ES', 'FI', 'IE', 'SE'], 'payout'=>'8.75', 'payoutDisplay'=>'$8.75', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>367, 'name'=>'GlobalTraffic Smartlink Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'77', 'payoutDisplay'=>'77% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>368, 'name'=>'CasinoKing Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['FR', 'GB', 'IE', 'NL'], 'payout'=>'152.59', 'payoutDisplay'=>'$152.59', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>369, 'name'=>'LiveModels FTD Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['FR', 'IE'], 'payout'=>'23.73', 'payoutDisplay'=>'$23.73', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>370, 'name'=>'BatterySaver Plus Pro', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['DK', 'IE', 'IT', 'SG'], 'payout'=>'7.23', 'payoutDisplay'=>'$7.23', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>371, 'name'=>'TradingSignals Lead Hot', 'cat'=>'financial', 'sub'=>'Financial · Global · SOI', 'geos'=>['WW'], 'payout'=>'10.30', 'payoutDisplay'=>'$10.30', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>372, 'name'=>'NaughtyMatch VIP Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['CH', 'IE', 'NO', 'SE', 'US'], 'payout'=>'61.46', 'payoutDisplay'=>'$61.46', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>373, 'name'=>'DateMatch Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['AU', 'GB', 'SE', 'ZA'], 'payout'=>'2.34', 'payoutDisplay'=>'$2.34', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>374, 'name'=>'MilfZone Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['DK', 'SE', 'ZA'], 'payout'=>'8.07', 'payoutDisplay'=>'$8.07', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>375, 'name'=>'MaxEPC Link Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'82', 'payoutDisplay'=>'82% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>376, 'name'=>'BetEmpire Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['NO', 'SE'], 'payout'=>'25.46', 'payoutDisplay'=>'$25.46', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>377, 'name'=>'CamFlirts Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['AU', 'DE', 'US'], 'payout'=>'31.95', 'payoutDisplay'=>'$31.95', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>378, 'name'=>'Antivirus Mobile Global', 'cat'=>'software', 'sub'=>'Software · Global · CPI', 'geos'=>['WW'], 'payout'=>'26.78', 'payoutDisplay'=>'$26.78', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>379, 'name'=>'PaydayLoan USA Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['FI', 'SG'], 'payout'=>'36.22', 'payoutDisplay'=>'$36.22', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>380, 'name'=>'AdultDating Direct Sale Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['CA', 'JP', 'NZ', 'SE'], 'payout'=>'60.99', 'payoutDisplay'=>'$60.99', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>381, 'name'=>'AdultFriend Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['GB', 'JP', 'US', 'ZA'], 'payout'=>'2.06', 'payoutDisplay'=>'$2.06', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=400&q=70'],
        ['id'=>382, 'name'=>'CougarLife Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['NL'], 'payout'=>'10.71', 'payoutDisplay'=>'$10.71', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>383, 'name'=>'Super Smartlink Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'80', 'payoutDisplay'=>'80% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>384, 'name'=>'RoyalSpins Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['CA', 'IE', 'ZA'], 'payout'=>'20.48', 'payoutDisplay'=>'$20.48', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>385, 'name'=>'StreamModels Pro', 'cat'=>'cam', 'sub'=>'Cam · Global · FTD', 'geos'=>['WW'], 'payout'=>'42.63', 'payoutDisplay'=>'$42.63', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>386, 'name'=>'SystemDoctor Hot', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['CA', 'CH', 'DE', 'IE', 'IT'], 'payout'=>'23.68', 'payoutDisplay'=>'$23.68', 'payStyle'=>'--grad-cool', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>387, 'name'=>'AutoInsurance Quotes Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['CH'], 'payout'=>'81.64', 'payoutDisplay'=>'$81.64', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>388, 'name'=>'Maturedates CPS Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['AU', 'JP'], 'payout'=>'73.38', 'payoutDisplay'=>'$73.38', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>389, 'name'=>'LocalSingles Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['CA', 'FI', 'NO'], 'payout'=>'5.02', 'payoutDisplay'=>'$5.02', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=400&q=70'],
        ['id'=>390, 'name'=>'AffairDating Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['DK', 'NZ'], 'payout'=>'6.54', 'payoutDisplay'=>'$6.54', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>391, 'name'=>'GlobalMatch Smartlink Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'75', 'payoutDisplay'=>'75% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>392, 'name'=>'SlotVegas Hot', 'cat'=>'casino', 'sub'=>'Casino · Global · FTD', 'geos'=>['WW'], 'payout'=>'37.80', 'payoutDisplay'=>'$37.80', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>393, 'name'=>'HotCams Live Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['AU', 'CA', 'ES', 'US', 'ZA'], 'payout'=>'29.13', 'payoutDisplay'=>'$29.13', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>394, 'name'=>'WifiBooster Pro Pro', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['AU', 'CA', 'DK'], 'payout'=>'40.40', 'payoutDisplay'=>'$40.40', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>395, 'name'=>'HealthInsurance Direct Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['CA', 'IT', 'US'], 'payout'=>'41.21', 'payoutDisplay'=>'$41.21', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>396, 'name'=>'LoveArrow CPS Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['AU'], 'payout'=>'63.08', 'payoutDisplay'=>'$63.08', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>397, 'name'=>'PassionMatch Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['CH'], 'payout'=>'1.92', 'payoutDisplay'=>'$1.92', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&q=70'],
        ['id'=>398, 'name'=>'PrivateDates Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['CA', 'DK', 'IT', 'NL', 'NO'], 'payout'=>'3.74', 'payoutDisplay'=>'$3.74', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>399, 'name'=>'Dating Smartlink V4 Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'82', 'payoutDisplay'=>'82% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>400, 'name'=>'FortuneWheel Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['CA', 'GB', 'ZA'], 'payout'=>'110.05', 'payoutDisplay'=>'$110.05', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>401, 'name'=>'CamDirect Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['DK', 'ES', 'FI', 'NO'], 'payout'=>'24.08', 'payoutDisplay'=>'$24.08', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>402, 'name'=>'DataGuard Mobile Global', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['ES', 'NO'], 'payout'=>'33.22', 'payoutDisplay'=>'$33.22', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>403, 'name'=>'TaxRelief Helper Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['FI', 'NO', 'US'], 'payout'=>'41.97', 'payoutDisplay'=>'$41.97', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>404, 'name'=>'AdultShop Purchase Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['AU', 'JP'], 'payout'=>'53.43', 'payoutDisplay'=>'$53.43', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>405, 'name'=>'PureDating Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['JP', 'NL'], 'payout'=>'7.49', 'payoutDisplay'=>'$7.49', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&q=70'],
        ['id'=>406, 'name'=>'NaughtyMatch Pro', 'cat'=>'doi', 'sub'=>'Dating · Global · DOI', 'geos'=>['WW'], 'payout'=>'7.65', 'payoutDisplay'=>'$7.65', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>407, 'name'=>'AdultSmartlink Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'77', 'payoutDisplay'=>'77% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>408, 'name'=>'BetWinner Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['US'], 'payout'=>'151.60', 'payoutDisplay'=>'$151.60', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>409, 'name'=>'CamClub Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['IE'], 'payout'=>'37.92', 'payoutDisplay'=>'$37.92', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>410, 'name'=>'AppLock Pro Hot', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['DE', 'ES', 'IT', 'NZ'], 'payout'=>'14.21', 'payoutDisplay'=>'$14.21', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>411, 'name'=>'ForexSignal Pro Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['CH', 'IE', 'IT'], 'payout'=>'50.56', 'payoutDisplay'=>'$50.56', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>412, 'name'=>'Hookup Club Premium Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['IT', 'US'], 'payout'=>'28.15', 'payoutDisplay'=>'$28.15', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>413, 'name'=>'SpeedDate Hot', 'cat'=>'soi', 'sub'=>'Dating · Global · SOI', 'geos'=>['WW'], 'payout'=>'4.37', 'payoutDisplay'=>'$4.37', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70'],
        ['id'=>414, 'name'=>'AdultHookup Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['AU', 'FI', 'NZ', 'SE', 'ZA'], 'payout'=>'3.74', 'payoutDisplay'=>'$3.74', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>415, 'name'=>'Mainstream Link Pro Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'79', 'payoutDisplay'=>'79% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>416, 'name'=>'SlotStar Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['DK'], 'payout'=>'61.42', 'payoutDisplay'=>'$61.42', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>417, 'name'=>'AdultCamPro Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['DE', 'GB', 'IE', 'SE', 'SG'], 'payout'=>'14.89', 'payoutDisplay'=>'$14.89', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>418, 'name'=>'FileSpeed Pro Pro', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['NO'], 'payout'=>'16.15', 'payoutDisplay'=>'$16.15', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>419, 'name'=>'MortgageQuotes Live Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['ES', 'IE', 'JP', 'US'], 'payout'=>'39.12', 'payoutDisplay'=>'$39.12', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>420, 'name'=>'FlirtZone Subscription Global', 'cat'=>'cps', 'sub'=>'Dating · Global · CPS', 'geos'=>['WW'], 'payout'=>'43.66', 'payoutDisplay'=>'$43.66', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>421, 'name'=>'CharmDate Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['CA', 'GB', 'NZ', 'ZA'], 'payout'=>'1.51', 'payoutDisplay'=>'$1.51', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1554151228-14d9def656e4?w=400&q=70'],
        ['id'=>422, 'name'=>'Maturedates Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['CA', 'CH', 'DE', 'GB', 'SE'], 'payout'=>'3.99', 'payoutDisplay'=>'$3.99', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>423, 'name'=>'SmartRotation Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'83', 'payoutDisplay'=>'83% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>424, 'name'=>'Ckwin Casino Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['CH', 'FR', 'US', 'ZA'], 'payout'=>'113.89', 'payoutDisplay'=>'$113.89', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>425, 'name'=>'VideoChat FTD Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['DE', 'DK', 'ES', 'NZ'], 'payout'=>'30.52', 'payoutDisplay'=>'$30.52', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>426, 'name'=>'uMobix.org Global', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['CH', 'DE', 'IT', 'SG', 'ZA'], 'payout'=>'1.28', 'payoutDisplay'=>'$1.28', 'payStyle'=>'--grad-cool', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>427, 'name'=>'PersonalLoan24 Pro', 'cat'=>'financial', 'sub'=>'Financial · Global · SOI', 'geos'=>['WW'], 'payout'=>'119.85', 'payoutDisplay'=>'$119.85', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>428, 'name'=>'SecretAffair VIP Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['CH', 'GB', 'IT', 'NL'], 'payout'=>'36.73', 'payoutDisplay'=>'$36.73', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>429, 'name'=>'UkrainianGirls Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['CH', 'ES', 'FI', 'JP'], 'payout'=>'6.03', 'payoutDisplay'=>'$6.03', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>430, 'name'=>'FlirtChat Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['DE', 'DK', 'JP', 'NL'], 'payout'=>'3.65', 'payoutDisplay'=>'$3.65', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>431, 'name'=>'AutoGeo Link Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'81', 'payoutDisplay'=>'81% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>432, 'name'=>'SpinCrown Casino Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['AU', 'IE', 'NO', 'ZA'], 'payout'=>'64.90', 'payoutDisplay'=>'$64.90', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>433, 'name'=>'LiveGirls Cam Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['AU', 'CA', 'DE', 'FI', 'SG'], 'payout'=>'27.72', 'payoutDisplay'=>'$27.72', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>434, 'name'=>'SecureVPN Pro Hot', 'cat'=>'software', 'sub'=>'Software · Global · CPI', 'geos'=>['WW'], 'payout'=>'20.37', 'payoutDisplay'=>'$20.37', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>435, 'name'=>'CryptoTrading Lead Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['IT'], 'payout'=>'107.52', 'payoutDisplay'=>'$107.52', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>436, 'name'=>'CamShow Premium Pack Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['US'], 'payout'=>'59.88', 'payoutDisplay'=>'$59.88', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>437, 'name'=>'MatchMe Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['AU', 'DK', 'FR', 'SE'], 'payout'=>'7.28', 'payoutDisplay'=>'$7.28', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=400&q=70'],
        ['id'=>438, 'name'=>'SeniorMatch Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['FI', 'FR', 'NO', 'ZA'], 'payout'=>'11.38', 'payoutDisplay'=>'$11.38', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>439, 'name'=>'EPC Booster Link Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'83', 'payoutDisplay'=>'83% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>440, 'name'=>'VegasSlots Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['CA', 'FR', 'GB'], 'payout'=>'170.32', 'payoutDisplay'=>'$170.32', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>441, 'name'=>'CamFantasy Global', 'cat'=>'cam', 'sub'=>'Cam · Global · FTD', 'geos'=>['WW'], 'payout'=>'31.98', 'payoutDisplay'=>'$31.98', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>442, 'name'=>'CleanerTool Mobile Pro', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['IT'], 'payout'=>'28.86', 'payoutDisplay'=>'$28.86', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>443, 'name'=>'DebtRelief Express Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['DK', 'FI', 'SE', 'US'], 'payout'=>'9.31', 'payoutDisplay'=>'$9.31', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>444, 'name'=>'CougarLife Membership Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['AU', 'GB', 'US'], 'payout'=>'56.47', 'payoutDisplay'=>'$56.47', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>445, 'name'=>'LoveFinder Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['IE'], 'payout'=>'5.11', 'payoutDisplay'=>'$5.11', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=400&q=70'],
        ['id'=>446, 'name'=>'MilfFinder Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['SG'], 'payout'=>'11.70', 'payoutDisplay'=>'$11.70', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>447, 'name'=>'GlobalTraffic Smartlink Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'77', 'payoutDisplay'=>'77% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>448, 'name'=>'CryptoCasino Pro', 'cat'=>'casino', 'sub'=>'Casino · Global · FTD', 'geos'=>['WW'], 'payout'=>'59.62', 'payoutDisplay'=>'$59.62', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>449, 'name'=>'AdultStream Live Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['CH', 'NL', 'NO', 'NZ', 'ZA'], 'payout'=>'42.10', 'payoutDisplay'=>'$42.10', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>450, 'name'=>'DeviceMonitor Global', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['SE'], 'payout'=>'20.50', 'payoutDisplay'=>'$20.50', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>451, 'name'=>'BinanceLeads Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['CH', 'IT', 'SG'], 'payout'=>'52.54', 'payoutDisplay'=>'$52.54', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>452, 'name'=>'NaughtyMatch VIP Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['ES', 'JP', 'ZA'], 'payout'=>'59.76', 'payoutDisplay'=>'$59.76', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>453, 'name'=>'FlirtZone Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['AU', 'CH', 'ES', 'NO', 'SG'], 'payout'=>'6.67', 'payoutDisplay'=>'$6.67', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&q=70'],
        ['id'=>454, 'name'=>'CasualHookups Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['CH', 'FI', 'GB', 'NL', 'SG'], 'payout'=>'6.37', 'payoutDisplay'=>'$6.37', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>455, 'name'=>'MaxEPC Link Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'83', 'payoutDisplay'=>'83% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>456, 'name'=>'LuckyBet FTD Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['ES', 'NL', 'NZ'], 'payout'=>'70.70', 'payoutDisplay'=>'$70.70', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>457, 'name'=>'CamShow Token Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['CA'], 'payout'=>'11.84', 'payoutDisplay'=>'$11.84', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>458, 'name'=>'SpyApp Pro Hot', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['FR', 'NZ', 'SE'], 'payout'=>'25.03', 'payoutDisplay'=>'$25.03', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>459, 'name'=>'CreditScore Pro Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['DK', 'FR'], 'payout'=>'108.70', 'payoutDisplay'=>'$108.70', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>460, 'name'=>'AdultDating Direct Sale Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['CH', 'DE', 'FI', 'US'], 'payout'=>'84.70', 'payoutDisplay'=>'$84.70', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>461, 'name'=>'SweetHearts Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['IE'], 'payout'=>'4.56', 'payoutDisplay'=>'$4.56', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&q=70'],
        ['id'=>462, 'name'=>'DirectDates Global', 'cat'=>'doi', 'sub'=>'Dating · Global · DOI', 'geos'=>['WW'], 'payout'=>'8.35', 'payoutDisplay'=>'$8.35', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>463, 'name'=>'Super Smartlink Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'77', 'payoutDisplay'=>'77% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>464, 'name'=>'JackpotCrown Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['AU', 'GB', 'JP', 'NO', 'SG'], 'payout'=>'88.36', 'payoutDisplay'=>'$88.36', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>465, 'name'=>'PrivateCams Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['FR', 'GB', 'NL', 'US'], 'payout'=>'31.22', 'payoutDisplay'=>'$31.22', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>466, 'name'=>'AdBlocker Ultimate Pro', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['AU', 'DE', 'DK', 'NZ', 'US'], 'payout'=>'49.00', 'payoutDisplay'=>'$49.00', 'payStyle'=>'--grad-cool', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>467, 'name'=>'InstantCash Loan Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['CA', 'NZ'], 'payout'=>'25.26', 'payoutDisplay'=>'$25.26', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>468, 'name'=>'Maturedates CPS Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['ES'], 'payout'=>'41.68', 'payoutDisplay'=>'$41.68', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>469, 'name'=>'SecretFlirts Pro', 'cat'=>'soi', 'sub'=>'Dating · Global · SOI', 'geos'=>['WW'], 'payout'=>'2.29', 'payoutDisplay'=>'$2.29', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70'],
        ['id'=>470, 'name'=>'SecretAffair Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['DE', 'NL'], 'payout'=>'9.37', 'payoutDisplay'=>'$9.37', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>471, 'name'=>'GlobalMatch Smartlink Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'82', 'payoutDisplay'=>'82% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>472, 'name'=>'MegaSpins Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['AU', 'DE', 'ES', 'FI', 'FR'], 'payout'=>'121.04', 'payoutDisplay'=>'$121.04', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>473, 'name'=>'HookupCams Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['FI', 'IT', 'JP', 'SE', 'ZA'], 'payout'=>'41.80', 'payoutDisplay'=>'$41.80', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>474, 'name'=>'NetShield VPN Global', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['AU', 'CH'], 'payout'=>'49.45', 'payoutDisplay'=>'$49.45', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>475, 'name'=>'FastApproval Loans Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['FI', 'IT', 'JP', 'NO', 'SE'], 'payout'=>'16.92', 'payoutDisplay'=>'$16.92', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>476, 'name'=>'LoveArrow CPS Hot', 'cat'=>'cps', 'sub'=>'Dating · Global · CPS', 'geos'=>['WW'], 'payout'=>'71.51', 'payoutDisplay'=>'$71.51', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>477, 'name'=>'QuickHookup Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['CH', 'FR', 'IT', 'NL', 'US'], 'payout'=>'2.38', 'payoutDisplay'=>'$2.38', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1554151228-14d9def656e4?w=400&q=70'],
        ['id'=>478, 'name'=>'NaughtyFlirts Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['CH', 'IT', 'NO', 'US'], 'payout'=>'7.04', 'payoutDisplay'=>'$7.04', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>479, 'name'=>'Dating Smartlink V4 Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'80', 'payoutDisplay'=>'80% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>480, 'name'=>'GoldReels Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['DE', 'IE', 'NZ', 'ZA'], 'payout'=>'123.63', 'payoutDisplay'=>'$123.63', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>481, 'name'=>'ChatRub Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['SE', 'ZA'], 'payout'=>'41.38', 'payoutDisplay'=>'$41.38', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>482, 'name'=>'StorageCleaner Hot', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['DE', 'DK', 'GB'], 'payout'=>'4.54', 'payoutDisplay'=>'$4.54', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>483, 'name'=>'CryptoProfit System Global', 'cat'=>'financial', 'sub'=>'Financial · Global · SOI', 'geos'=>['WW'], 'payout'=>'6.92', 'payoutDisplay'=>'$6.92', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>484, 'name'=>'AdultShop Purchase Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['AU', 'DE', 'GB', 'NL', 'SE'], 'payout'=>'60.23', 'payoutDisplay'=>'$60.23', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>485, 'name'=>'CupidArrow Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['DK', 'IT', 'SE', 'ZA'], 'payout'=>'2.86', 'payoutDisplay'=>'$2.86', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>486, 'name'=>'TrueLove Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['CA'], 'payout'=>'7.65', 'payoutDisplay'=>'$7.65', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>487, 'name'=>'AdultSmartlink Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'82', 'payoutDisplay'=>'82% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>488, 'name'=>'CasinoKing Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['DE', 'DK', 'US'], 'payout'=>'90.24', 'payoutDisplay'=>'$90.24', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>489, 'name'=>'LiveModels FTD Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['AU', 'GB', 'NZ', 'SG', 'US'], 'payout'=>'21.70', 'payoutDisplay'=>'$21.70', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>490, 'name'=>'BatterySaver Plus Pro', 'cat'=>'software', 'sub'=>'Software · Global · CPI', 'geos'=>['WW'], 'payout'=>'3.17', 'payoutDisplay'=>'$3.17', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>491, 'name'=>'TradingSignals Lead Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['FR', 'GB', 'NL'], 'payout'=>'14.75', 'payoutDisplay'=>'$14.75', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>492, 'name'=>'Hookup Club Premium Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['FI', 'NZ'], 'payout'=>'53.41', 'payoutDisplay'=>'$53.41', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>493, 'name'=>'DateMatch Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['CA', 'DK', 'SG'], 'payout'=>'6.49', 'payoutDisplay'=>'$6.49', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=400&q=70'],
        ['id'=>494, 'name'=>'MilfZone Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['ES'], 'payout'=>'4.19', 'payoutDisplay'=>'$4.19', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>495, 'name'=>'Mainstream Link Pro Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'75', 'payoutDisplay'=>'75% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>496, 'name'=>'BetEmpire Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['DK', 'FR', 'ZA'], 'payout'=>'67.21', 'payoutDisplay'=>'$67.21', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>497, 'name'=>'CamFlirts Hot', 'cat'=>'cam', 'sub'=>'Cam · Global · FTD', 'geos'=>['WW'], 'payout'=>'15.15', 'payoutDisplay'=>'$15.15', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>498, 'name'=>'Antivirus Mobile Global', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['GB', 'IT', 'NZ', 'SG'], 'payout'=>'43.83', 'payoutDisplay'=>'$43.83', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>499, 'name'=>'PaydayLoan USA Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['DE', 'JP'], 'payout'=>'58.03', 'payoutDisplay'=>'$58.03', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>500, 'name'=>'FlirtZone Subscription Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['IT'], 'payout'=>'26.01', 'payoutDisplay'=>'$26.01', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>501, 'name'=>'AdultFriend Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['CH', 'FR', 'IE'], 'payout'=>'3.08', 'payoutDisplay'=>'$3.08', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=400&q=70'],
        ['id'=>502, 'name'=>'CougarLife Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['JP'], 'payout'=>'8.65', 'payoutDisplay'=>'$8.65', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>503, 'name'=>'SmartRotation Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'82', 'payoutDisplay'=>'82% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>504, 'name'=>'RoyalSpins Global', 'cat'=>'casino', 'sub'=>'Casino · Global · FTD', 'geos'=>['WW'], 'payout'=>'87.32', 'payoutDisplay'=>'$87.32', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>505, 'name'=>'StreamModels Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['NZ', 'SG', 'ZA'], 'payout'=>'16.53', 'payoutDisplay'=>'$16.53', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>506, 'name'=>'SystemDoctor Hot', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['CH', 'ES', 'IE'], 'payout'=>'47.49', 'payoutDisplay'=>'$47.49', 'payStyle'=>'--grad-cool', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>507, 'name'=>'AutoInsurance Quotes Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['DE', 'FI', 'IT', 'NL'], 'payout'=>'84.09', 'payoutDisplay'=>'$84.09', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>508, 'name'=>'SecretAffair VIP Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['AU', 'IT', 'NL'], 'payout'=>'55.64', 'payoutDisplay'=>'$55.64', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>509, 'name'=>'LocalSingles Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['AU', 'ZA'], 'payout'=>'4.09', 'payoutDisplay'=>'$4.09', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&q=70'],
        ['id'=>510, 'name'=>'AffairDating Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['SG'], 'payout'=>'7.89', 'payoutDisplay'=>'$7.89', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>511, 'name'=>'AutoGeo Link Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'77', 'payoutDisplay'=>'77% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>512, 'name'=>'SlotVegas Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['NL'], 'payout'=>'34.99', 'payoutDisplay'=>'$34.99', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>513, 'name'=>'HotCams Live Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['NL'], 'payout'=>'11.91', 'payoutDisplay'=>'$11.91', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>514, 'name'=>'WifiBooster Pro Pro', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['CA', 'DK', 'FI'], 'payout'=>'18.04', 'payoutDisplay'=>'$18.04', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>515, 'name'=>'HealthInsurance Direct Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['DE', 'FR', 'NL'], 'payout'=>'22.70', 'payoutDisplay'=>'$22.70', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>516, 'name'=>'CamShow Premium Pack Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['SG'], 'payout'=>'33.20', 'payoutDisplay'=>'$33.20', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>517, 'name'=>'PassionMatch Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['DK', 'ES', 'SG'], 'payout'=>'4.81', 'payoutDisplay'=>'$4.81', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&q=70'],
        ['id'=>518, 'name'=>'PrivateDates Hot', 'cat'=>'doi', 'sub'=>'Dating · Global · DOI', 'geos'=>['WW'], 'payout'=>'3.71', 'payoutDisplay'=>'$3.71', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>519, 'name'=>'EPC Booster Link Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'80', 'payoutDisplay'=>'80% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>520, 'name'=>'FortuneWheel Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['DK', 'GB', 'IE', 'NL', 'US'], 'payout'=>'178.88', 'payoutDisplay'=>'$178.88', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>521, 'name'=>'CamDirect Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['FI', 'SE'], 'payout'=>'35.46', 'payoutDisplay'=>'$35.46', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>522, 'name'=>'DataGuard Mobile Global', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['DE', 'GB', 'US'], 'payout'=>'11.30', 'payoutDisplay'=>'$11.30', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>523, 'name'=>'TaxRelief Helper Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['FI', 'GB', 'IE', 'JP', 'NZ'], 'payout'=>'59.65', 'payoutDisplay'=>'$59.65', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>524, 'name'=>'CougarLife Membership Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['IE', 'JP', 'SE'], 'payout'=>'36.44', 'payoutDisplay'=>'$36.44', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>525, 'name'=>'PureDating Global', 'cat'=>'soi', 'sub'=>'Dating · Global · SOI', 'geos'=>['WW'], 'payout'=>'2.93', 'payoutDisplay'=>'$2.93', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70'],
        ['id'=>526, 'name'=>'NaughtyMatch Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['NL'], 'payout'=>'10.12', 'payoutDisplay'=>'$10.12', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>527, 'name'=>'GlobalTraffic Smartlink Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'76', 'payoutDisplay'=>'76% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>528, 'name'=>'BetWinner Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['AU', 'DE', 'FR', 'JP'], 'payout'=>'166.45', 'payoutDisplay'=>'$166.45', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>529, 'name'=>'CamClub Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['AU', 'FR', 'NL'], 'payout'=>'44.39', 'payoutDisplay'=>'$44.39', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>530, 'name'=>'AppLock Pro Hot', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['IE'], 'payout'=>'47.76', 'payoutDisplay'=>'$47.76', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>531, 'name'=>'ForexSignal Pro Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['AU', 'CA', 'CH', 'ZA'], 'payout'=>'83.57', 'payoutDisplay'=>'$83.57', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>532, 'name'=>'NaughtyMatch VIP Pro', 'cat'=>'cps', 'sub'=>'Dating · Global · CPS', 'geos'=>['WW'], 'payout'=>'50.00', 'payoutDisplay'=>'$50.00', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>533, 'name'=>'SpeedDate Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['CA', 'NL'], 'payout'=>'4.15', 'payoutDisplay'=>'$4.15', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1554151228-14d9def656e4?w=400&q=70'],
        ['id'=>534, 'name'=>'AdultHookup Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['GB', 'IE'], 'payout'=>'11.97', 'payoutDisplay'=>'$11.97', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>535, 'name'=>'MaxEPC Link Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'77', 'payoutDisplay'=>'77% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>536, 'name'=>'SlotStar Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['GB', 'SE'], 'payout'=>'52.73', 'payoutDisplay'=>'$52.73', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>537, 'name'=>'AdultCamPro Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['US'], 'payout'=>'44.33', 'payoutDisplay'=>'$44.33', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>538, 'name'=>'FileSpeed Pro Pro', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['ES'], 'payout'=>'15.90', 'payoutDisplay'=>'$15.90', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>539, 'name'=>'MortgageQuotes Live Hot', 'cat'=>'financial', 'sub'=>'Financial · Global · SOI', 'geos'=>['WW'], 'payout'=>'15.26', 'payoutDisplay'=>'$15.26', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>540, 'name'=>'AdultDating Direct Sale Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['AU', 'FR', 'IE'], 'payout'=>'35.34', 'payoutDisplay'=>'$35.34', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>541, 'name'=>'CharmDate Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['IE', 'JP', 'ZA'], 'payout'=>'5.35', 'payoutDisplay'=>'$5.35', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>542, 'name'=>'Maturedates Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['DK', 'FI', 'JP'], 'payout'=>'11.55', 'payoutDisplay'=>'$11.55', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>543, 'name'=>'Super Smartlink Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'80', 'payoutDisplay'=>'80% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>544, 'name'=>'Ckwin Casino Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['NZ'], 'payout'=>'151.33', 'payoutDisplay'=>'$151.33', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>545, 'name'=>'VideoChat FTD Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['IE', 'JP', 'NO'], 'payout'=>'37.15', 'payoutDisplay'=>'$37.15', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>546, 'name'=>'uMobix.org Global', 'cat'=>'software', 'sub'=>'Software · Global · CPI', 'geos'=>['WW'], 'payout'=>'36.16', 'payoutDisplay'=>'$36.16', 'payStyle'=>'--grad-cool', 'hot'=>true, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>547, 'name'=>'PersonalLoan24 Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['CH', 'DK', 'FR', 'IT', 'ZA'], 'payout'=>'90.51', 'payoutDisplay'=>'$90.51', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>548, 'name'=>'Maturedates CPS Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['FI', 'NL', 'SE'], 'payout'=>'40.68', 'payoutDisplay'=>'$40.68', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>549, 'name'=>'UkrainianGirls Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['CA', 'NL', 'NZ'], 'payout'=>'3.41', 'payoutDisplay'=>'$3.41', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=400&q=70'],
        ['id'=>550, 'name'=>'FlirtChat Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['CH', 'IT', 'NZ', 'SE'], 'payout'=>'6.73', 'payoutDisplay'=>'$6.73', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>551, 'name'=>'GlobalMatch Smartlink Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'75', 'payoutDisplay'=>'75% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>552, 'name'=>'SpinCrown Casino Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['FI', 'FR', 'GB'], 'payout'=>'161.22', 'payoutDisplay'=>'$161.22', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>553, 'name'=>'LiveGirls Cam Pro', 'cat'=>'cam', 'sub'=>'Cam · Global · FTD', 'geos'=>['WW'], 'payout'=>'31.09', 'payoutDisplay'=>'$31.09', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>554, 'name'=>'SecureVPN Pro Hot', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['CH', 'DK', 'FR', 'IT', 'NO'], 'payout'=>'18.60', 'payoutDisplay'=>'$18.60', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>555, 'name'=>'CryptoTrading Lead Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['CA', 'CH', 'FI', 'FR', 'GB'], 'payout'=>'103.63', 'payoutDisplay'=>'$103.63', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>556, 'name'=>'LoveArrow CPS Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['CA', 'DE', 'SE', 'SG'], 'payout'=>'50.05', 'payoutDisplay'=>'$50.05', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>557, 'name'=>'MatchMe Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['IT', 'SG'], 'payout'=>'7.35', 'payoutDisplay'=>'$7.35', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=400&q=70'],
        ['id'=>558, 'name'=>'SeniorMatch Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['DK', 'IE'], 'payout'=>'6.51', 'payoutDisplay'=>'$6.51', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>559, 'name'=>'Dating Smartlink V4 Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'81', 'payoutDisplay'=>'81% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>560, 'name'=>'VegasSlots Hot', 'cat'=>'casino', 'sub'=>'Casino · Global · FTD', 'geos'=>['WW'], 'payout'=>'139.11', 'payoutDisplay'=>'$139.11', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>561, 'name'=>'CamFantasy Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['CH'], 'payout'=>'44.27', 'payoutDisplay'=>'$44.27', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>562, 'name'=>'CleanerTool Mobile Pro', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['DK', 'ES', 'IT', 'ZA'], 'payout'=>'1.62', 'payoutDisplay'=>'$1.62', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>563, 'name'=>'DebtRelief Express Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['CH', 'DE', 'FR', 'NL'], 'payout'=>'28.38', 'payoutDisplay'=>'$28.38', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>564, 'name'=>'AdultShop Purchase Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['DK', 'IE', 'NO', 'SE'], 'payout'=>'82.54', 'payoutDisplay'=>'$82.54', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>565, 'name'=>'LoveFinder Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['AU', 'ES'], 'payout'=>'5.96', 'payoutDisplay'=>'$5.96', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&q=70'],
        ['id'=>566, 'name'=>'MilfFinder Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['FI', 'FR', 'IE', 'NO'], 'payout'=>'6.73', 'payoutDisplay'=>'$6.73', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>567, 'name'=>'AdultSmartlink Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'82', 'payoutDisplay'=>'82% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>568, 'name'=>'CryptoCasino Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['SG'], 'payout'=>'67.03', 'payoutDisplay'=>'$67.03', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>569, 'name'=>'AdultStream Live Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['AU', 'DE', 'FI', 'IE', 'US'], 'payout'=>'28.24', 'payoutDisplay'=>'$28.24', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>570, 'name'=>'DeviceMonitor Global', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['FR', 'IE', 'SE', 'SG', 'US'], 'payout'=>'34.16', 'payoutDisplay'=>'$34.16', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>571, 'name'=>'BinanceLeads Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['DE', 'NL', 'SG'], 'payout'=>'77.54', 'payoutDisplay'=>'$77.54', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>572, 'name'=>'Hookup Club Premium Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['FI', 'NL', 'NO'], 'payout'=>'44.37', 'payoutDisplay'=>'$44.37', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>573, 'name'=>'FlirtZone Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-1 · SOI', 'geos'=>['GB', 'NO', 'NZ', 'US'], 'payout'=>'2.51', 'payoutDisplay'=>'$2.51', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&q=70'],
        ['id'=>574, 'name'=>'CasualHookups Pro', 'cat'=>'doi', 'sub'=>'Dating · Global · DOI', 'geos'=>['WW'], 'payout'=>'11.96', 'payoutDisplay'=>'$11.96', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>575, 'name'=>'Mainstream Link Pro Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'84', 'payoutDisplay'=>'84% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>576, 'name'=>'LuckyBet FTD Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['ES', 'FI'], 'payout'=>'119.98', 'payoutDisplay'=>'$119.98', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>577, 'name'=>'CamShow Token Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['IE'], 'payout'=>'39.20', 'payoutDisplay'=>'$39.20', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>578, 'name'=>'SpyApp Pro Hot', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['FR', 'SE', 'SG'], 'payout'=>'41.26', 'payoutDisplay'=>'$41.26', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>579, 'name'=>'CreditScore Pro Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['CH', 'IT', 'JP', 'US', 'ZA'], 'payout'=>'85.60', 'payoutDisplay'=>'$85.60', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>580, 'name'=>'FlirtZone Subscription Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['DK', 'FI'], 'payout'=>'78.66', 'payoutDisplay'=>'$78.66', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>581, 'name'=>'SweetHearts Hot', 'cat'=>'soi', 'sub'=>'Dating · Global · SOI', 'geos'=>['WW'], 'payout'=>'6.92', 'payoutDisplay'=>'$6.92', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=400&q=70'],
        ['id'=>582, 'name'=>'DirectDates Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['FR', 'NZ', 'SE', 'ZA'], 'payout'=>'4.25', 'payoutDisplay'=>'$4.25', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>583, 'name'=>'SmartRotation Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'78', 'payoutDisplay'=>'78% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>584, 'name'=>'JackpotCrown Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['CH', 'ES', 'US'], 'payout'=>'106.29', 'payoutDisplay'=>'$106.29', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>585, 'name'=>'PrivateCams Global', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['DE', 'FR', 'GB', 'IT', 'NO'], 'payout'=>'23.37', 'payoutDisplay'=>'$23.37', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>586, 'name'=>'AdBlocker Ultimate Pro', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['CH', 'SE', 'US', 'ZA'], 'payout'=>'48.92', 'payoutDisplay'=>'$48.92', 'payStyle'=>'--grad-cool', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>587, 'name'=>'InstantCash Loan Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['NZ'], 'payout'=>'97.69', 'payoutDisplay'=>'$97.69', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>588, 'name'=>'SecretAffair VIP Global', 'cat'=>'cps', 'sub'=>'Dating · Global · CPS', 'geos'=>['WW'], 'payout'=>'93.15', 'payoutDisplay'=>'$93.15', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>589, 'name'=>'SecretFlirts Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['AU', 'GB'], 'payout'=>'3.00', 'payoutDisplay'=>'$3.00', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1554151228-14d9def656e4?w=400&q=70'],
        ['id'=>590, 'name'=>'SecretAffair Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['NL'], 'payout'=>'11.97', 'payoutDisplay'=>'$11.97', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>591, 'name'=>'AutoGeo Link Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'79', 'payoutDisplay'=>'79% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>592, 'name'=>'MegaSpins Pro', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['AU', 'GB', 'NZ', 'SE'], 'payout'=>'104.09', 'payoutDisplay'=>'$104.09', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>593, 'name'=>'HookupCams Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-2 · FTD', 'geos'=>['CH', 'IT', 'SE', 'SG', 'US'], 'payout'=>'16.85', 'payoutDisplay'=>'$16.85', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>594, 'name'=>'NetShield VPN Global', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['CH', 'ES', 'IT', 'SE', 'SG'], 'payout'=>'25.40', 'payoutDisplay'=>'$25.40', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>595, 'name'=>'FastApproval Loans Pro', 'cat'=>'financial', 'sub'=>'Financial · Global · SOI', 'geos'=>['WW'], 'payout'=>'61.84', 'payoutDisplay'=>'$61.84', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>596, 'name'=>'CamShow Premium Pack Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['AU', 'DE', 'ES', 'FI', 'JP'], 'payout'=>'64.22', 'payoutDisplay'=>'$64.22', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>597, 'name'=>'QuickHookup Global', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['AU', 'DK', 'SE', 'US'], 'payout'=>'4.60', 'payoutDisplay'=>'$4.60', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>598, 'name'=>'NaughtyFlirts Pro', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['CA', 'SE', 'US'], 'payout'=>'6.95', 'payoutDisplay'=>'$6.95', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=70'],
        ['id'=>599, 'name'=>'EPC Booster Link Hot', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'75', 'payoutDisplay'=>'75% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>600, 'name'=>'GoldReels Global', 'cat'=>'casino', 'sub'=>'Casino · Tier-2 · FTD', 'geos'=>['AU', 'DK', 'NL', 'SE', 'SG'], 'payout'=>'27.57', 'payoutDisplay'=>'$27.57', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=400&q=70'],
        ['id'=>601, 'name'=>'ChatRub Pro', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['DE'], 'payout'=>'26.16', 'payoutDisplay'=>'$26.16', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>602, 'name'=>'StorageCleaner Hot', 'cat'=>'software', 'sub'=>'Software · Global · CPI', 'geos'=>['WW'], 'payout'=>'11.06', 'payoutDisplay'=>'$11.06', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&q=70'],
        ['id'=>603, 'name'=>'CryptoProfit System Global', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['AU', 'NZ'], 'payout'=>'97.46', 'payoutDisplay'=>'$97.46', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1589758438368-0ad531db3366?w=400&q=70'],
        ['id'=>604, 'name'=>'CougarLife Membership Pro', 'cat'=>'cps', 'sub'=>'Dating · Tier-2 · CPS', 'geos'=>['DE', 'FR', 'JP', 'US'], 'payout'=>'51.59', 'payoutDisplay'=>'$51.59', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>605, 'name'=>'CupidArrow Hot', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['DE', 'ES', 'NO', 'NZ'], 'payout'=>'5.88', 'payoutDisplay'=>'$5.88', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=400&q=70'],
        ['id'=>606, 'name'=>'TrueLove Global', 'cat'=>'doi', 'sub'=>'Dating · Tier-2 · DOI', 'geos'=>['US'], 'payout'=>'3.25', 'payoutDisplay'=>'$3.25', 'payStyle'=>'--grad-warm', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400&q=70'],
        ['id'=>607, 'name'=>'GlobalTraffic Smartlink Pro', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'77', 'payoutDisplay'=>'77% Rev', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>608, 'name'=>'CasinoKing Hot', 'cat'=>'casino', 'sub'=>'Casino · Tier-1 · FTD', 'geos'=>['DE'], 'payout'=>'94.07', 'payoutDisplay'=>'$94.07', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=400&q=70'],
        ['id'=>609, 'name'=>'LiveModels FTD Global', 'cat'=>'cam', 'sub'=>'Cam · Global · FTD', 'geos'=>['WW'], 'payout'=>'36.18', 'payoutDisplay'=>'$36.18', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>false, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>610, 'name'=>'BatterySaver Plus Pro', 'cat'=>'software', 'sub'=>'Software · Tier-2 · CPI', 'geos'=>['DE', 'DK', 'IE', 'NL', 'SG'], 'payout'=>'15.51', 'payoutDisplay'=>'$15.51', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&q=70'],
        ['id'=>611, 'name'=>'TradingSignals Lead Hot', 'cat'=>'financial', 'sub'=>'Financial · Tier-2 · SOI', 'geos'=>['GB'], 'payout'=>'115.48', 'payoutDisplay'=>'$115.48', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=400&q=70'],
        ['id'=>612, 'name'=>'NaughtyMatch VIP Global', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['IE', 'JP', 'NL', 'ZA'], 'payout'=>'73.44', 'payoutDisplay'=>'$73.44', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
        ['id'=>613, 'name'=>'DateMatch Pro', 'cat'=>'soi', 'sub'=>'Dating · Tier-2 · SOI', 'geos'=>['AU', 'CA', 'ES', 'NZ'], 'payout'=>'4.78', 'payoutDisplay'=>'$4.78', 'payStyle'=>'--grad-gold', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=400&q=70'],
        ['id'=>614, 'name'=>'MilfZone Hot', 'cat'=>'doi', 'sub'=>'Dating · Tier-1 · DOI', 'geos'=>['FI', 'IE', 'JP', 'NO'], 'payout'=>'11.00', 'payoutDisplay'=>'$11.00', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=70'],
        ['id'=>615, 'name'=>'MaxEPC Link Global', 'cat'=>'smartlink', 'sub'=>'Dating · Global · RevShare', 'geos'=>['WW'], 'payout'=>'82', 'payoutDisplay'=>'82% Rev', 'payStyle'=>'--grad-green', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=400&q=70'],
        ['id'=>616, 'name'=>'BetEmpire Pro', 'cat'=>'casino', 'sub'=>'Casino · Global · FTD', 'geos'=>['WW'], 'payout'=>'133.86', 'payoutDisplay'=>'$133.86', 'payStyle'=>'--grad-gold', 'hot'=>true, 'top'=>true, 'img'=>'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=400&q=70'],
        ['id'=>617, 'name'=>'CamFlirts Hot', 'cat'=>'cam', 'sub'=>'Cam · Tier-1 · FTD', 'geos'=>['CA', 'NL'], 'payout'=>'11.32', 'payoutDisplay'=>'$11.32', 'payStyle'=>'linear-gradient(135deg,#a855f7,#ec4899)', 'hot'=>true, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=400&q=70'],
        ['id'=>618, 'name'=>'Antivirus Mobile Global', 'cat'=>'software', 'sub'=>'Software · Tier-1 · CPI', 'geos'=>['CH'], 'payout'=>'30.25', 'payoutDisplay'=>'$30.25', 'payStyle'=>'--grad-cool', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=400&q=70'],
        ['id'=>619, 'name'=>'PaydayLoan USA Pro', 'cat'=>'financial', 'sub'=>'Financial · Tier-1 · SOI', 'geos'=>['ES', 'SE'], 'payout'=>'72.48', 'payoutDisplay'=>'$72.48', 'payStyle'=>'--grad-green', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&q=70'],
        ['id'=>620, 'name'=>'AdultDating Direct Sale Hot', 'cat'=>'cps', 'sub'=>'Dating · Tier-1 · CPS', 'geos'=>['AU', 'FR'], 'payout'=>'92.31', 'payoutDisplay'=>'$92.31', 'payStyle'=>'--grad-warm', 'hot'=>false, 'top'=>false, 'img'=>'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=70'],
    ];
}

echo json_encode(['success' => true, 'data' => $offers, 'count' => count($offers)]);
