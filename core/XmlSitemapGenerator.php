<?php
/**
 * XmlSitemapGenerator — Multi-Sitemap XML Generator & Manager
 *
 * Generates and automatically maintains valid XML sitemaps:
 * sitemap.xml (index), pages.xml, offers.xml, categories.xml, blogs.xml, images.xml, videos.xml.
 */
class XmlSitemapGenerator
{
    private static function getBaseUrl(): string
    {
        return rtrim(AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net'), '/');
    }

    /**
     * Generate Master Sitemap Index (sitemap.xml)
     */
    public static function buildSitemapIndex(): string
    {
        $base = self::getBaseUrl();
        $today = date('c');

        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $subSitemaps = ['pages.xml', 'offers.xml', 'categories.xml', 'blogs.xml', 'images.xml', 'videos.xml'];
        foreach ($subSitemaps as $s) {
            $out .= '  <sitemap>' . "\n";
            $out .= '    <loc>' . $base . '/sitemap/' . $s . '</loc>' . "\n";
            $out .= '    <lastmod>' . $today . '</lastmod>' . "\n";
            $out .= '  </sitemap>' . "\n";
        }

        $out .= '</sitemapindex>';
        return $out;
    }

    /**
     * Generate pages.xml
     */
    public static function buildPagesSitemap(): string
    {
        $base = self::getBaseUrl();
        $today = date('Y-m-d');

        $urls = [
            ['loc' => $base . '/', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => $base . '/offers', 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => $base . '/blog', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => $base . '/reviews', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => $base . '/terms-of-service', 'changefreq' => 'yearly', 'priority' => '0.4'],
            ['loc' => $base . '/privacy-policy', 'changefreq' => 'yearly', 'priority' => '0.4'],
            ['loc' => $base . '/login', 'changefreq' => 'monthly', 'priority' => '0.3'],
            ['loc' => $base . '/register/affiliate', 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => $base . '/register/advertiser', 'changefreq' => 'monthly', 'priority' => '0.5'],
        ];

        return self::renderUrlSet($urls, $today);
    }

    /**
     * Generate offers.xml
     */
    public static function buildOffersSitemap(): string
    {
        $base = self::getBaseUrl();
        $today = date('Y-m-d');
        $urls = [];

        try {
            $offers = Database::fetchAll("SELECT id, name, created_at FROM offers WHERE status='active' AND visibility='public' ORDER BY id DESC LIMIT 5000");
            foreach ($offers as $o) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $o['name'])));
                $slug = rtrim($slug, '-');
                $urls[] = [
                    'loc' => $base . '/offers/' . $o['id'] . '-' . $slug,
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                    'lastmod' => $o['created_at'] ? date('Y-m-d', strtotime($o['created_at'])) : $today
                ];
            }
        } catch (\Throwable $_e) {}

        return self::renderUrlSet($urls, $today);
    }

    /**
     * Generate categories.xml
     */
    public static function buildCategoriesSitemap(): string
    {
        $base = self::getBaseUrl();
        $today = date('Y-m-d');

        $cats = [
            '/offers/best-cpa-offers',
            '/offers/high-paying-affiliate-offers',
            '/offers/best-dating-cpa-offers',
            '/offers/best-finance-cpa-offers',
            '/offers/best-sweepstakes-offers',
            '/offers/best-gift-card-offers',
            '/offers/best-health-affiliate-offers',
            '/category/dating-offers',
            '/category/finance-offers',
            '/category/health-offers',
            '/category/sweepstakes-offers',
            '/category/gift-card-offers',
            '/category/digital-marketing'
        ];

        $urls = [];
        foreach ($cats as $c) {
            $urls[] = ['loc' => $base . $c, 'changefreq' => 'weekly', 'priority' => '0.9', 'lastmod' => $today];
        }

        return self::renderUrlSet($urls, $today);
    }

    /**
     * Generate blogs.xml
     */
    public static function buildBlogsSitemap(): string
    {
        $base = self::getBaseUrl();
        $today = date('Y-m-d');
        $urls = [];

        try {
            $posts = Database::fetchAll("SELECT slug, updated_at, created_at FROM landing_posts WHERE status='published' AND slug IS NOT NULL AND slug!='' ORDER BY id DESC LIMIT 1000");
            foreach ($posts as $p) {
                $stamp = $p['updated_at'] ?: $p['created_at'];
                $urls[] = [
                    'loc' => $base . '/blog/' . $p['slug'],
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                    'lastmod' => $stamp ? date('Y-m-d', strtotime($stamp)) : $today
                ];
            }
        } catch (\Throwable $_e) {}

        return self::renderUrlSet($urls, $today);
    }

    /**
     * Generate images.xml
     */
    public static function buildImagesSitemap(): string
    {
        $base = self::getBaseUrl();
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        $images = [
            ['page' => $base . '/', 'image' => $base . '/logoo.png', 'title' => 'Affscash Logo'],
            ['page' => $base . '/', 'image' => $base . '/background.jpg', 'title' => 'Affscash Background Banner'],
        ];

        foreach ($images as $img) {
            $out .= '  <url>' . "\n";
            $out .= '    <loc>' . htmlspecialchars($img['page']) . '</loc>' . "\n";
            $out .= '    <image:image>' . "\n";
            $out .= '      <image:loc>' . htmlspecialchars($img['image']) . '</image:loc>' . "\n";
            $out .= '      <image:title>' . htmlspecialchars($img['title']) . '</image:title>' . "\n";
            $out .= '    </image:image>' . "\n";
            $out .= '  </url>' . "\n";
        }

        $out .= '</urlset>';
        return $out;
    }

    /**
     * Generate videos.xml
     */
    public static function buildVideosSitemap(): string
    {
        $base = self::getBaseUrl();
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";
        $out .= '</urlset>';
        return $out;
    }

    private static function renderUrlSet(array $urls, string $defaultMod): string
    {
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $u) {
            $out .= '  <url>' . "\n";
            $out .= '    <loc>' . htmlspecialchars($u['loc']) . '</loc>' . "\n";
            $out .= '    <lastmod>' . ($u['lastmod'] ?? $defaultMod) . '</lastmod>' . "\n";
            $out .= '    <changefreq>' . ($u['changefreq'] ?? 'weekly') . '</changefreq>' . "\n";
            $out .= '    <priority>' . ($u['priority'] ?? '0.8') . '</priority>' . "\n";
            $out .= '  </url>' . "\n";
        }

        $out .= '</urlset>';
        return $out;
    }
}
