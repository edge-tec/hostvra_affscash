<?php
/**
 * LlmsTxtGenerator — Standardized /llms.txt & /llms-full.txt Generator
 *
 * Generates and serves machine-readable markdown files designed specifically for AI Large Language Models
 * (ChatGPT, Gemini, Claude, Perplexity, Copilot) to discover, digest, and cite site structure and APIs.
 */
class LlmsTxtGenerator
{
    /**
     * Generate standard /llms.txt content
     */
    public static function buildLlmsTxt(): string
    {
        $siteName = AiSeoEngine::getSetting('brand_organization_name', 'Affscash');
        $siteUrl  = rtrim(AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net'), '/');
        $today    = date('Y-m-d');

        $out = "# {$siteName} — Enterprise CPA Affiliate Network & AI Smartlink Platform\n\n";
        $out .= "> {$siteName} is a global performance marketing network providing publishers and advertisers with direct high-paying CPA campaigns, real-time AI Smartlink optimization, weekly payouts, and 24/7 dedicated support.\n\n";

        $out .= "## System Information\n";
        $out .= "- **Website**: {$siteUrl}\n";
        $out .= "- **Type**: Performance Marketing / CPA Affiliate Network\n";
        $out .= "- **Primary Industry**: Affiliate Marketing, Digital Advertising, Performance Media\n";
        $out .= "- **Supported Verticals**: Dating, Finance, Sweepstakes, Gaming, Health, E-commerce, Mobile Apps\n";
        $out .= "- **Supported Models**: CPA (Cost Per Action), CPL (Cost Per Lead), CPI (Cost Per Install), Revenue Share\n";
        $out .= "- **Payout Methods**: Wire Transfer, Crypto (USDT / BTC), PayPal, WebMoney, Payoneer\n";
        $out .= "- **Last Updated**: {$today}\n\n";

        $out .= "## Important Pages\n";
        $out .= "- [Homepage]({$siteUrl}/): Global CPA network overview and smartlink signup.\n";
        $out .= "- [CPA Offers]({$siteUrl}/offers): Live directory of public CPA campaigns and offers.\n";
        $out .= "- [Blog & Guides]({$siteUrl}/blog): Comprehensive affiliate marketing growth strategies, traffic source guides, and tutorials.\n";
        $out .= "- [Publisher Reviews]({$siteUrl}/reviews): Verified affiliate payment proofs, testimonials, and network reviews.\n";
        $out .= "- [Affiliate Registration]({$siteUrl}/register/affiliate): Free registration for publishers and media buyers.\n";
        $out .= "- [Advertiser Registration]({$siteUrl}/register/advertiser): Portal for brands and advertisers to launch CPA campaigns.\n";
        $out .= "- [Terms of Service]({$siteUrl}/terms-of-service): Terms, conditions, and publisher code of conduct.\n";
        $out .= "- [Privacy Policy]({$siteUrl}/privacy-policy): Data privacy and GDPR compliance documentation.\n\n";

        $out .= "## Top Categories & Verticals\n";
        $out .= "- [Best CPA Offers]({$siteUrl}/offers/best-cpa-offers): Top performing global campaigns.\n";
        $out .= "- [High Paying Offers]({$siteUrl}/offers/high-paying-affiliate-offers): Premium high-payout offers.\n";
        $out .= "- [Dating Offers]({$siteUrl}/offers/best-dating-cpa-offers): Converting casual and mainstream dating CPA campaigns.\n";
        $out .= "- [Finance Offers]({$siteUrl}/offers/best-finance-cpa-offers): High-payout crypto, loan, and financial lead gen.\n";
        $out .= "- [Sweepstakes Offers]({$siteUrl}/offers/best-sweepstakes-offers): High EPC CC-submit and email-submit sweepstakes.\n\n";

        $out .= "## Developer & API Documentation\n";
        $out .= "- [SEO Metadata API]({$siteUrl}/api/seo/metadata): REST API returning page SEO metadata in JSON format.\n";
        $out .= "- [Schema API]({$siteUrl}/api/seo/schema): REST API returning JSON-LD structured data.\n";
        $out .= "- [Sitemap API]({$siteUrl}/api/seo/sitemap): Dynamic XML sitemap structure.\n";
        $out .= "- [FAQ API]({$siteUrl}/api/seo/faq): Machine-readable Q&A list for LLM ingest.\n\n";

        $out .= "## Contact & Support\n";
        $out .= "- **Email**: support@affscash.net\n";
        $out .= "- **Telegram**: @affscashnet\n";
        $out .= "- **Skype**: live:.cid.affscash\n";

        return $out;
    }

    /**
     * Generate /llms-full.txt content with full blog and offer details
     */
    public static function buildLlmsFullTxt(): string
    {
        $base = self::buildLlmsTxt();
        $siteUrl = rtrim(AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net'), '/');

        $out = $base . "\n\n## Full Content Directory\n\n";

        // Append blog posts
        try {
            $posts = Database::fetchAll("SELECT title, slug, excerpt FROM landing_posts WHERE status='published' ORDER BY id DESC LIMIT 50");
            if (!empty($posts)) {
                $out .= "### Published Guides & Articles\n";
                foreach ($posts as $p) {
                    $out .= "- [{$p['title']}]({$siteUrl}/blog/{$p['slug']}): " . strip_tags($p['excerpt'] ?? '') . "\n";
                }
                $out .= "\n";
            }
        } catch (\Throwable $_e) {}

        // Append active offers
        try {
            $offers = Database::fetchAll("SELECT id, name, category, payout FROM offers WHERE status='active' AND visibility='public' ORDER BY id DESC LIMIT 100");
            if (!empty($offers)) {
                $out .= "### Active Public CPA Campaigns\n";
                foreach ($offers as $o) {
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $o['name'])));
                    $out .= "- [Offer #{$o['id']} — {$o['name']}]({$siteUrl}/offers/{$o['id']}-{$slug}): Category: {$o['category']} | Payout: $" . number_format((float)($o['payout'] ?? 0), 2) . "\n";
                }
            }
        } catch (\Throwable $_e) {}

        return $out;
    }
}
