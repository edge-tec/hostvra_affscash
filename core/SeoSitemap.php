<?php
/**
 * SeoSitemap — single source of truth for the public sitemap URL list.
 *
 * Used by:
 *   • /sitemap.xml router fallback (index.php) when the static file is missing
 *   • /admin/search-console "Generate Sitemap" action (writes the static file)
 *   • The cron-driven auto-regen
 *
 * Pulls the base URL from seo_settings.site_url (falls back to config.app.url),
 * enumerates all public-facing pages plus published landing_posts (the real
 * blog table), and appends any admin-supplied custom URLs.
 */
class SeoSitemap
{
    /** Returns the URL entries for the public sitemap.xml. */
    public static function buildUrls(): array
    {
        $base = self::baseUrl();
        $today = date('Y-m-d');

        // Static public pages — kept in priority order so the homepage outranks
        // auth screens. Anything behind /admin or /affiliate is intentionally
        // omitted (it's noindex'd by robots and not useful to search engines).
        $urls = [
            ['loc' => $base . '/',                  'changefreq' => 'daily',   'priority' => '1.0', 'lastmod' => $today],
            ['loc' => $base . '/blog',              'changefreq' => 'daily',   'priority' => '0.8', 'lastmod' => $today],
            ['loc' => $base . '/reviews',           'changefreq' => 'weekly',  'priority' => '0.8', 'lastmod' => $today],
            ['loc' => $base . '/TRC.html',          'changefreq' => 'yearly',  'priority' => '0.4', 'lastmod' => $today],
            ['loc' => $base . '/login',             'changefreq' => 'monthly', 'priority' => '0.3', 'lastmod' => $today],
            ['loc' => $base . '/register/affiliate','changefreq' => 'monthly', 'priority' => '0.5', 'lastmod' => $today],
            ['loc' => $base . '/register/advertiser','changefreq'=> 'monthly', 'priority' => '0.5', 'lastmod' => $today],
            ['loc' => $base . '/forgot-password',   'changefreq' => 'yearly',  'priority' => '0.2', 'lastmod' => $today],
        ];

        // Published blog posts (real table is landing_posts).
        try {
            $posts = Database::fetchAll(
                "SELECT slug, updated_at, created_at FROM landing_posts
                  WHERE status='published' AND slug IS NOT NULL AND slug!=''
               ORDER BY COALESCE(updated_at, created_at) DESC LIMIT 1000"
            );
            foreach ($posts as $p) {
                $stamp = $p['updated_at'] ?: $p['created_at'];
                $urls[] = [
                    'loc'        => $base . '/blog/' . $p['slug'],
                    'changefreq' => 'monthly',
                    'priority'   => '0.7',
                    'lastmod'    => $stamp ? date('Y-m-d', strtotime($stamp)) : $today,
                ];
            }
        } catch (\Throwable $_e) { /* table may not exist on fresh installs */ }

        // Admin-supplied custom URLs (one per line in seo_settings.sitemap_custom_urls).
        try {
            $row = Database::fetchOne(
                "SELECT setting_value FROM seo_settings WHERE setting_key='sitemap_custom_urls'"
            );
            if ($row && $row['setting_value']) {
                foreach (array_filter(array_map('trim', explode("\n", $row['setting_value']))) as $line) {
                    if (filter_var($line, FILTER_VALIDATE_URL)) {
                        $urls[] = ['loc' => $line, 'changefreq' => 'monthly', 'priority' => '0.6', 'lastmod' => $today];
                    }
                }
            }
        } catch (\Throwable $_e) {}

        return $urls;
    }

    /** Writes sitemap.xml to disk. Returns the URL count or false on failure. */
    public static function writeFile(): int|false
    {
        require_once BASE_PATH . '/core/GoogleSearchConsole.php';
        $urls = self::buildUrls();
        $xml  = GoogleSearchConsole::buildSitemapXml($urls);
        if (file_put_contents(BASE_PATH . '/sitemap.xml', $xml) === false) return false;
        try {
            Database::query(
                "INSERT INTO seo_settings (setting_key, setting_value) VALUES
                    ('sitemap_generated_at', ?), ('sitemap_url_count', ?)
                 ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()",
                [date('Y-m-d H:i:s'), (string)count($urls)]
            );
        } catch (\Throwable $_e) {}
        return count($urls);
    }

    private static function baseUrl(): string
    {
        try {
            $row = Database::fetchOne("SELECT setting_value FROM seo_settings WHERE setting_key='site_url'");
            if ($row && $row['setting_value']) return rtrim($row['setting_value'], '/');
        } catch (\Throwable $_e) {}
        return rtrim(Config::get('config', 'app.url') ?? '', '/');
    }
}
