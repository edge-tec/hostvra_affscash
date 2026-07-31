<?php
/**
 * SeoKeywordModule — Isolated Extension Module for Post SEO Keywords
 *
 * Provides table schema initialization, keyword validation, tag parsing,
 * AJAX persistence, automatic SEO metadata generation (without overwriting),
 * search engine & AI crawler optimization, and XML sitemap extensions.
 */
class SeoKeywordModule
{
    private static bool $schemaChecked = false;

    /**
     * Idempotent table initialization
     */
    public static function initSchema(): void
    {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `post_seo_keywords` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `post_id`      INT UNSIGNED NOT NULL,
                `keyword`      VARCHAR(255) NOT NULL,
                `keyword_type` ENUM('primary', 'secondary', 'long_tail', 'related', 'focus') NOT NULL,
                `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_post_id` (`post_id`),
                INDEX `idx_keyword_type` (`keyword_type`),
                UNIQUE KEY `uq_post_type_kw` (`post_id`, `keyword_type`, `keyword`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $_e) {
            error_log('[SeoKeywordModule] Schema init warning: ' . $_e->getMessage());
        }
    }

    /**
     * Supported Keyword Types
     */
    public static function getSupportedTypes(): array
    {
        return [
            'primary'   => 'Primary Keyword',
            'secondary' => 'Secondary Keywords',
            'long_tail' => 'Long-tail Keywords',
            'related'   => 'Related Keywords',
            'focus'     => 'Focus Keyword'
        ];
    }

    /**
     * Clean, trim, and deduplicate keywords.
     * Preserves original display casing while preventing duplicate matches.
     */
    public static function cleanKeywords(array|string $raw): array
    {
        if (is_string($raw)) {
            // Handle comma or newline separated string input
            $raw = preg_split('/[\r\n,]+/', $raw);
        }

        $cleaned   = [];
        $seenLower = [];

        foreach ((array)$raw as $item) {
            if (!is_string($item) && !is_numeric($item)) continue;
            $trimmed = trim((string)$item);
            if ($trimmed === '') continue;

            $lower = mb_strtolower($trimmed, 'UTF-8');
            if (isset($seenLower[$lower])) continue; // Skip duplicates

            $seenLower[$lower] = true;
            $cleaned[] = $trimmed; // Keep original display case
        }

        return $cleaned;
    }

    /**
     * Fetch all keywords for a given post ID.
     */
    public static function getKeywords(int $postId): array
    {
        self::initSchema();
        $result = [
            'primary'   => [],
            'secondary' => [],
            'long_tail' => [],
            'related'   => [],
            'focus'     => []
        ];

        if ($postId <= 0) return $result;

        try {
            $rows = Database::fetchAll(
                "SELECT keyword, keyword_type FROM post_seo_keywords WHERE post_id=? ORDER BY id ASC",
                [$postId]
            );
            foreach ($rows as $r) {
                $type = $r['keyword_type'];
                if (isset($result[$type])) {
                    $result[$type][] = $r['keyword'];
                }
            }
        } catch (\Throwable $_e) {}

        return $result;
    }

    /**
     * Save keywords for a post.
     * Expects an array keyed by keyword_type (primary, secondary, etc.)
     */
    public static function saveKeywords(int $postId, array $keywordsData): array
    {
        self::initSchema();

        if ($postId <= 0) {
            return ['success' => false, 'error' => 'Invalid post ID'];
        }

        $validTypes = array_keys(self::getSupportedTypes());
        $savedCount = 0;
        $byType = [];

        try {
            // Remove previous keywords for this post
            Database::query("DELETE FROM post_seo_keywords WHERE post_id=?", [$postId]);

            foreach ($validTypes as $type) {
                $rawList = $keywordsData[$type] ?? [];
                $clean   = self::cleanKeywords($rawList);
                $byType[$type] = $clean;

                foreach ($clean as $kw) {
                    Database::query(
                        "INSERT IGNORE INTO post_seo_keywords (post_id, keyword, keyword_type, created_at) VALUES (?, ?, ?, NOW())",
                        [$postId, $kw, $type]
                    );
                    $savedCount++;
                }
            }

            // Fetch post info to trigger automatic SEO generation
            $post = Database::fetchOne("SELECT * FROM landing_posts WHERE id=?", [$postId]);
            if ($post) {
                self::autoGenerateSeoData($post, $byType);
            }

            // Sync with global SEO keyword tracking table (ai_seo_keywords)
            self::syncInternalKeywordMapping($postId, $byType, $post['slug'] ?? null);

            return [
                'success'    => true,
                'postId'     => $postId,
                'savedCount' => $savedCount,
                'keywords'   => $byType
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Automatic SEO Generator
     * Generates Meta Title, Meta Description, OG tags, Twitter Card tags, JSON-LD Schema,
     * ONLY auto-filling empty fields without overwriting manually entered SEO data.
     */
    public static function autoGenerateSeoData(array $post, array $keywordsByType): array
    {
        $primaryKws   = $keywordsByType['primary']   ?? [];
        $secondaryKws = $keywordsByType['secondary'] ?? [];
        $longTailKws  = $keywordsByType['long_tail'] ?? [];
        $relatedKws   = $keywordsByType['related']   ?? [];
        $focusKws     = $keywordsByType['focus']     ?? [];

        $allKws = array_merge($primaryKws, $focusKws, $secondaryKws, $longTailKws, $relatedKws);
        $primaryKw = $primaryKws[0] ?? $focusKws[0] ?? $allKws[0] ?? '';
        $focusKw   = $focusKws[0]   ?? $primaryKw;

        $postTitle = trim($post['title'] ?? '');
        $postExcerpt = trim($post['excerpt'] ?? '');
        $postSlug  = trim($post['slug'] ?? '');
        $siteName  = Config::get('config', 'app.name') ?? 'Affscash';
        $siteUrl   = rtrim(Config::get('config', 'app.url') ?? 'https://affscash.net', '/');
        $postUrl   = $siteUrl . '/blog/' . $postSlug;

        // 1. Meta Title Suggestion
        $suggestedTitle = $postTitle;
        if ($primaryKw && stripos($postTitle, $primaryKw) === false) {
            $suggestedTitle = $postTitle . ' — ' . $primaryKw;
        }

        // 2. Meta Description Suggestion
        $suggestedDesc = $postExcerpt;
        if (!$suggestedDesc) {
            $kwListStr = implode(', ', array_slice(array_merge($primaryKws, $secondaryKws), 0, 4));
            $suggestedDesc = mb_substr(strip_tags($post['body'] ?? ''), 0, 140, 'UTF-8');
            if ($kwListStr) {
                $suggestedDesc = ($suggestedDesc ? $suggestedDesc . '. ' : '') . 'Topics covered: ' . $kwListStr . '.';
            }
        }

        // 3. Open Graph Tags
        $ogTags = [
            'og:type'        => 'article',
            'og:title'       => $suggestedTitle,
            'og:description' => $suggestedDesc,
            'og:url'         => $postUrl,
            'og:image'       => !empty($post['image']) ? ($siteUrl . $post['image']) : ($siteUrl . '/logoo.png'),
            'og:site_name'   => $siteName
        ];

        // 4. Twitter Card Tags
        $twitterTags = [
            'twitter:card'        => 'summary_large_image',
            'twitter:title'       => $suggestedTitle,
            'twitter:description' => $suggestedDesc,
            'twitter:image'       => $ogTags['og:image']
        ];

        // 5. JSON-LD Schema
        $jsonLdSchema = [
            '@context' => 'https://schema.org',
            '@type'    => 'BlogPosting',
            'headline' => $postTitle,
            'description' => $suggestedDesc,
            'url'      => $postUrl,
            'keywords' => implode(', ', $allKws),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $postUrl
            ],
            'author' => [
                '@type' => 'Organization',
                'name'  => $siteName,
                'url'   => $siteUrl
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name'  => $siteName,
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => $siteUrl . '/logoo.png'
                ]
            ]
        ];
        if (!empty($post['published_at'])) {
            $jsonLdSchema['datePublished'] = date('c', strtotime($post['published_at']));
        }
        if (!empty($post['created_at'])) {
            $jsonLdSchema['dateCreated'] = date('c', strtotime($post['created_at']));
        }
        if (!empty($post['updated_at'])) {
            $jsonLdSchema['dateModified'] = date('c', strtotime($post['updated_at']));
        }

        // 6. Update ai_seo_pages table ONLY for empty fields (Never overwrite existing data)
        try {
            $existingMeta = Database::fetchOne("SELECT * FROM ai_seo_pages WHERE page_url=?", ['/blog/' . $postSlug]);
            
            $kwStr = implode(', ', $allKws);
            $primaryEnt = $primaryKw ?: ($post['category'] ?? 'Affiliate Marketing');

            if ($existingMeta) {
                // Update only empty columns
                $updates = [];
                $params  = [];

                if (empty($existingMeta['title'])) {
                    $updates[] = "`title`=?"; $params[] = $suggestedTitle;
                }
                if (empty($existingMeta['meta_description'])) {
                    $updates[] = "`meta_description`=?"; $params[] = $suggestedDesc;
                }
                if (empty($existingMeta['meta_keywords'])) {
                    $updates[] = "`meta_keywords`=?"; $params[] = $kwStr;
                }
                if (empty($existingMeta['primary_entity'])) {
                    $updates[] = "`primary_entity`=?"; $params[] = $primaryEnt;
                }
                if (empty($existingMeta['og_title'])) {
                    $updates[] = "`og_title`=?"; $params[] = $ogTags['og:title'];
                }
                if (empty($existingMeta['og_description'])) {
                    $updates[] = "`og_description`=?"; $params[] = $ogTags['og:description'];
                }
                if (empty($existingMeta['twitter_title'])) {
                    $updates[] = "`twitter_title`=?"; $params[] = $twitterTags['twitter:title'];
                }
                if (empty($existingMeta['twitter_description'])) {
                    $updates[] = "`twitter_description`=?"; $params[] = $twitterTags['twitter:description'];
                }

                if (!empty($updates)) {
                    $params[] = '/blog/' . $postSlug;
                    Database::query("UPDATE ai_seo_pages SET " . implode(', ', $updates) . " WHERE page_url=?", $params);
                }
            } else {
                // Insert new page SEO metadata
                Database::query("INSERT IGNORE INTO ai_seo_pages 
                    (page_url, title, meta_description, meta_keywords, canonical_url, og_title, og_description, og_image, twitter_title, twitter_description, twitter_image, primary_entity, ai_summary)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        '/blog/' . $postSlug,
                        $suggestedTitle,
                        $suggestedDesc,
                        $kwStr,
                        $postUrl,
                        $ogTags['og:title'],
                        $ogTags['og:description'],
                        $ogTags['og:image'],
                        $twitterTags['twitter:title'],
                        $twitterTags['twitter:description'],
                        $twitterTags['twitter:image'],
                        $primaryEnt,
                        $suggestedDesc
                    ]
                );
            }
        } catch (\Throwable $_e) {}

        return [
            'meta_title'       => $suggestedTitle,
            'meta_description' => $suggestedDesc,
            'open_graph'       => $ogTags,
            'twitter_card'     => $twitterTags,
            'json_ld'          => $jsonLdSchema,
            'keywords_string'  => implode(', ', $allKws)
        ];
    }

    /**
     * Internal Keyword Mapping
     * Populates ai_seo_keywords for cross-referencing and search discovery
     */
    private static function syncInternalKeywordMapping(int $postId, array $byType, ?string $slug): void
    {
        if (!$slug) return;
        $targetUrl = '/blog/' . $slug;

        foreach ($byType as $type => $kwList) {
            foreach ($kwList as $kw) {
                try {
                    Database::query("INSERT INTO ai_seo_keywords (keyword, target_url, entity_name, is_tracked, created_at)
                        VALUES (?, ?, ?, 1, NOW())
                        ON DUPLICATE KEY UPDATE target_url=?, entity_name=?",
                        [$kw, $targetUrl, $type, $targetUrl, $type]
                    );
                } catch (\Throwable $_e) {}
            }
        }
    }

    /**
     * Autocomplete suggestions for keywords as user types
     */
    public static function getSuggestions(string $q): array
    {
        self::initSchema();
        $q = trim($q);
        if (mb_strlen($q) < 1) return [];

        $results   = [];
        $seenLower = [];

        try {
            // Search in post_seo_keywords
            $rows1 = Database::fetchAll(
                "SELECT DISTINCT keyword FROM post_seo_keywords WHERE keyword LIKE ? LIMIT 15",
                ['%' . $q . '%']
            );
            foreach ($rows1 as $r) {
                $kw = $r['keyword'];
                $lower = mb_strtolower($kw);
                if (!isset($seenLower[$lower])) {
                    $seenLower[$lower] = true;
                    $results[] = $kw;
                }
            }

            // Search in ai_seo_keywords
            $rows2 = Database::fetchAll(
                "SELECT DISTINCT keyword FROM ai_seo_keywords WHERE keyword LIKE ? LIMIT 15",
                ['%' . $q . '%']
            );
            foreach ($rows2 as $r) {
                $kw = $r['keyword'];
                $lower = mb_strtolower($kw);
                if (!isset($seenLower[$lower])) {
                    $seenLower[$lower] = true;
                    $results[] = $kw;
                }
            }
        } catch (\Throwable $_e) {}

        return array_slice($results, 0, 15);
    }

    /**
     * Post Save Hook (called when blog post form is saved)
     */
    public static function handlePostSave(int $postId, array $postInput): void
    {
        if ($postId <= 0 || empty($postInput['seo_keywords'])) return;

        $kwData = $postInput['seo_keywords'];
        if (is_array($kwData)) {
            self::saveKeywords($postId, $kwData);
        }
    }

    /**
     * Render enhanced SEO head metadata for public blog post view
     */
    public static function renderPublicPostHead(array $post): string
    {
        $postId = (int)($post['id'] ?? 0);
        if ($postId <= 0) return '';

        $kws = self::getKeywords($postId);
        $allKws = array_merge(
            $kws['primary'],
            $kws['focus'],
            $kws['secondary'],
            $kws['long_tail'],
            $kws['related']
        );

        if (empty($allKws)) return '';

        $kwStr     = Helpers::e(implode(', ', $allKws));
        $primaryKw = Helpers::e($kws['primary'][0] ?? $kws['focus'][0] ?? '');
        $focusKw   = Helpers::e($kws['focus'][0]   ?? '');

        $html = "\n<!-- SEO Keywords System & AI Discoverability Tags -->\n";
        $html .= '<meta name="keywords" content="' . $kwStr . '">' . "\n";
        if ($primaryKw) {
            $html .= '<meta name="primary-keyword" content="' . $primaryKw . '">' . "\n";
        }
        if ($focusKw) {
            $html .= '<meta name="focus-keyword" content="' . $focusKw . '">' . "\n";
        }
        $html .= '<meta name="ai-keywords" content="' . $kwStr . '">' . "\n";

        return $html;
    }
}
