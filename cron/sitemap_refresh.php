<?php
/**
 * cron/sitemap_refresh.php — rebuilds /sitemap.xml on a schedule.
 *
 * Run from cron (e.g. once a day at 03:00):
 *   0 3 * * * /usr/bin/php /path/to/project/cron/sitemap_refresh.php
 *
 * Idempotent and safe to run more often. Picks up new blog posts, lastmod
 * changes, and updated admin custom URLs without manual intervention.
 */
define('BASE_PATH',   dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');

// Bootstrap (same pattern as other cron scripts in this directory).
require BASE_PATH . '/core/Config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/SeoSitemap.php';
Config::init(CONFIG_PATH);
date_default_timezone_set(Config::get('config', 'app.timezone') ?? 'UTC');

$count = SeoSitemap::writeFile();
if ($count === false) {
    fwrite(STDERR, "[sitemap_refresh] Failed to write sitemap.xml — check write permissions on " . BASE_PATH . PHP_EOL);
    exit(1);
}

echo "[sitemap_refresh] sitemap.xml refreshed with {$count} URLs at " . date('c') . PHP_EOL;
