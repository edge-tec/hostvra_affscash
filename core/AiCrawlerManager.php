<?php
/**
 * AiCrawlerManager — AI Crawler & Search Engine Bot Management Engine
 *
 * Provides granular Allow / Block controls for major AI LLM bots and search engine crawlers:
 * GPTBot, Google-Extended, ClaudeBot, PerplexityBot, Bingbot, Applebot, Amazonbot,
 * FacebookBot, Meta External Agent.
 *
 * Dynamically builds robots.txt.
 */
class AiCrawlerManager
{
    /**
     * List of supported AI & Search Crawlers
     */
    public static function getCrawlers(): array
    {
        return [
            'googlebot' => [
                'name' => 'Googlebot',
                'agent' => 'Googlebot',
                'desc' => 'Main Google Search web crawler.',
                'setting_key' => 'ai_crawler_googlebot'
            ],
            'googlebot_news' => [
                'name' => 'Googlebot-News',
                'agent' => 'Googlebot-News',
                'desc' => 'Google News articles crawler.',
                'setting_key' => 'ai_crawler_googlebot_news'
            ],
            'googlebot_image' => [
                'name' => 'Googlebot-Image',
                'agent' => 'Googlebot-Image',
                'desc' => 'Google Image Search crawler.',
                'setting_key' => 'ai_crawler_googlebot_image'
            ],
            'googlebot_video' => [
                'name' => 'Googlebot-Video',
                'agent' => 'Googlebot-Video',
                'desc' => 'Google Video Search crawler.',
                'setting_key' => 'ai_crawler_googlebot_video'
            ],
            'google_extended' => [
                'name' => 'Google-Extended',
                'agent' => 'Google-Extended',
                'desc' => 'Google AI crawler used for Gemini and Google AI features.',
                'setting_key' => 'ai_crawler_google_extended'
            ],
            'gptbot' => [
                'name' => 'GPTBot',
                'agent' => 'GPTBot',
                'desc' => 'OpenAI ChatGPT web crawler used to fetch content for GPT models.',
                'setting_key' => 'ai_crawler_gptbot'
            ],
            'chatgpt_user' => [
                'name' => 'ChatGPT-User',
                'agent' => 'ChatGPT-User',
                'desc' => 'OpenAI ChatGPT real-time user browsing agent.',
                'setting_key' => 'ai_crawler_chatgpt_user'
            ],
            'oai_searchbot' => [
                'name' => 'OAI-SearchBot',
                'agent' => 'OAI-SearchBot',
                'desc' => 'OpenAI SearchBot for SearchGPT search indexing.',
                'setting_key' => 'ai_crawler_oai_searchbot'
            ],
            'claudebot' => [
                'name' => 'ClaudeBot',
                'agent' => 'ClaudeBot',
                'desc' => 'Anthropic Claude AI web crawler for training and real-time retrieval.',
                'setting_key' => 'ai_crawler_claude'
            ],
            'perplexitybot' => [
                'name' => 'PerplexityBot',
                'agent' => 'PerplexityBot',
                'desc' => 'Perplexity AI search engine crawler used for live AI answers.',
                'setting_key' => 'ai_crawler_perplexity'
            ],
            'bingbot' => [
                'name' => 'Bingbot / Copilot',
                'agent' => 'bingbot',
                'desc' => 'Microsoft Bing and Copilot search engine crawler.',
                'setting_key' => 'ai_crawler_bingbot'
            ],
            'applebot' => [
                'name' => 'Applebot',
                'agent' => 'Applebot-Extended',
                'desc' => 'Apple Intelligence & Siri search crawler.',
                'setting_key' => 'ai_crawler_applebot'
            ],
            'duckassistbot' => [
                'name' => 'DuckAssistBot',
                'agent' => 'DuckAssistBot',
                'desc' => 'DuckDuckGo AI DuckAssist summary crawler.',
                'setting_key' => 'ai_crawler_duckassist'
            ],
            'bravebot' => [
                'name' => 'Bravebot',
                'agent' => 'Bravebot',
                'desc' => 'Brave Search AI engine crawler.',
                'setting_key' => 'ai_crawler_bravebot'
            ],
            'yandexbot' => [
                'name' => 'YandexBot',
                'agent' => 'YandexBot',
                'desc' => 'Yandex search engine spider.',
                'setting_key' => 'ai_crawler_yandex'
            ],
            'baiduspider' => [
                'name' => 'BaiduSpider',
                'agent' => 'Baiduspider',
                'desc' => 'Baidu search engine crawler.',
                'setting_key' => 'ai_crawler_baidu'
            ],
            'ccbot' => [
                'name' => 'CCBot',
                'agent' => 'CCBot',
                'desc' => 'Common Crawl web repository crawler.',
                'setting_key' => 'ai_crawler_ccbot'
            ],
            'amazonbot' => [
                'name' => 'Amazonbot',
                'agent' => 'Amazonbot',
                'desc' => 'Amazon AI crawler for web index and Alexa.',
                'setting_key' => 'ai_crawler_amazonbot'
            ],
            'facebookbot' => [
                'name' => 'FacebookBot',
                'agent' => 'FacebookBot',
                'desc' => 'Meta / Facebook crawler for preview cards and AI training.',
                'setting_key' => 'ai_crawler_facebookbot'
            ],
            'meta_external' => [
                'name' => 'Meta External Agent',
                'agent' => 'Meta-ExternalAgent',
                'desc' => 'Meta AI agent for web content analysis.',
                'setting_key' => 'ai_crawler_meta'
            ],
            'similarwebbot' => [
                'name' => 'Similarweb Bot',
                'agent' => 'SimilarwebBot',
                'desc' => 'Similarweb traffic & site intelligence crawler.',
                'setting_key' => 'ai_crawler_similarweb'
            ],
            'similartechbot' => [
                'name' => 'SimilarTech Bot',
                'agent' => 'SimilarTechBot',
                'desc' => 'SimilarTech web tech stack analysis crawler.',
                'setting_key' => 'ai_crawler_similartech'
            ],
            'ahrefsbot' => [
                'name' => 'AhrefsBot',
                'agent' => 'AhrefsBot',
                'desc' => 'Ahrefs SEO & backlink crawler.',
                'setting_key' => 'ai_crawler_ahrefs'
            ],
            'semrushbot' => [
                'name' => 'SemrushBot',
                'agent' => 'SemrushBot',
                'desc' => 'Semrush SEO audit and position tracking crawler.',
                'setting_key' => 'ai_crawler_semrush'
            ],
            'dotbot' => [
                'name' => 'DotBot (Moz)',
                'agent' => 'dotbot',
                'desc' => 'Moz Open Site Explorer backlink crawler.',
                'setting_key' => 'ai_crawler_dotbot'
            ],
            'mj12bot' => [
                'name' => 'MJ12bot (Majestic)',
                'agent' => 'MJ12bot',
                'desc' => 'Majestic SEO backlink intelligence crawler.',
                'setting_key' => 'ai_crawler_mj12bot'
            ]
        ];
    }

    /**
     * Generate dynamic robots.txt content
     */
    public static function buildRobotsTxt(): string
    {
        $siteUrl = rtrim(AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net'), '/');
        $sitemapUrl = $siteUrl . '/sitemap.xml';

        $out = "# ==========================================================\n";
        $out .= "# Dynamic robots.txt — Managed by Enterprise AI SEO Engine\n";
        $out .= "# Generated on: " . date('Y-m-d H:i:s T') . "\n";
        $out .= "# ==========================================================\n\n";

        // Global User-Agent rules
        $out .= "User-agent: *\n";
        $out .= "Allow: /\n";
        $out .= "Disallow: /admin/\n";
        $out .= "Disallow: /affiliate/\n";
        $out .= "Disallow: /advertiser/\n";
        $out .= "Disallow: /affiliate_manager/\n";
        $out .= "Disallow: /api/\n";
        $out .= "Disallow: /storage/\n\n";

        // AI Crawler Specific Rules
        $crawlers = self::getCrawlers();
        $out .= "# ── AI & LLM Search Engine Crawlers ────────────────────────\n";
        foreach ($crawlers as $c) {
            $status = AiSeoEngine::getSetting($c['setting_key'], 'allow');
            $out .= "# " . $c['name'] . "\n";
            $out .= "User-agent: " . $c['agent'] . "\n";
            if ($status === 'block') {
                $out .= "Disallow: /\n\n";
            } else {
                $out .= "Allow: /\n";
                $out .= "Disallow: /admin/\n";
                $out .= "Disallow: /api/\n\n";
            }
        }

        $out .= "Sitemap: " . $sitemapUrl . "\n";

        return $out;
    }
}
