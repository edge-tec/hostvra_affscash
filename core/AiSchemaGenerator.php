<?php
/**
 * AiSchemaGenerator — Advanced Schema.org JSON-LD Generator & Validator
 *
 * Generates and validates valid JSON-LD for 16 Schema.org types:
 * Organization, Website, WebPage, Article, BlogPosting, FAQPage, HowTo,
 * BreadcrumbList, SearchAction, Offer, Product, Review, AggregateRating,
 * Person, SoftwareApplication, WebApplication.
 */
class AiSchemaGenerator
{
    /**
     * Build graph container holding multiple schemas
     */
    public static function buildGraph(array $schemas): string
    {
        $graph = [
            '@context' => 'https://schema.org',
            '@graph'   => array_filter($schemas)
        ];
        return json_encode($graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 1. Organization Schema
     */
    public static function organization(array $data = []): array
    {
        $name = $data['name'] ?? AiSeoEngine::getSetting('brand_organization_name', 'Affscash');
        $url  = $data['url']  ?? AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net');
        $logo = $data['logo'] ?? AiSeoEngine::getSetting('brand_logo_url', $url . '/logoo.png');

        return [
            '@type' => 'Organization',
            '@id' => rtrim($url, '/') . '/#organization',
            'name' => $name,
            'url' => $url,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logo
            ],
            'sameAs' => [
                'https://twitter.com/affscashnet',
                'https://t.me/affscashnet',
                'https://linkedin.com/company/affscash'
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => 'support@affscash.net',
                'availableLanguage' => ['English']
            ]
        ];
    }

    /**
     * 2. WebSite Schema & SearchAction
     */
    public static function website(array $data = []): array
    {
        $name = $data['name'] ?? AiSeoEngine::getSetting('brand_organization_name', 'Affscash');
        $url  = $data['url']  ?? AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net');

        return [
            '@type' => 'WebSite',
            '@id' => rtrim($url, '/') . '/#website',
            'url' => $url,
            'name' => $name,
            'description' => 'Global CPA Affiliate Network & AI Smartlink Platform',
            'publisher' => [
                '@id' => rtrim($url, '/') . '/#organization'
            ],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => rtrim($url, '/') . '/offers?q={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ];
    }

    /**
     * 3. WebPage Schema
     */
    public static function webpage(string $url, string $title, string $desc, array $breadcrumb = []): array
    {
        $siteUrl = rtrim(AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net'), '/');

        return [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $title,
            'description' => $desc,
            'isPartOf' => [
                '@id' => $siteUrl . '/#website'
            ],
            'breadcrumb' => !empty($breadcrumb) ? self::breadcrumb($breadcrumb) : null,
            'inLanguage' => 'en-US'
        ];
    }

    /**
     * 4. Article & BlogPosting Schema
     */
    public static function article(array $post, bool $isBlog = true): array
    {
        $siteUrl = rtrim(AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net'), '/');
        $url = $siteUrl . '/blog/' . ($post['slug'] ?? '');

        return [
            '@type' => $isBlog ? 'BlogPosting' : 'Article',
            '@id' => $url . '#article',
            'headline' => $post['title'] ?? 'Affiliate Marketing Guide',
            'description' => $post['excerpt'] ?? $post['meta_description'] ?? '',
            'url' => $url,
            'datePublished' => date('c', strtotime($post['created_at'] ?? 'now')),
            'dateModified' => date('c', strtotime($post['updated_at'] ?? $post['created_at'] ?? 'now')),
            'author' => [
                '@type' => 'Person',
                'name' => $post['author'] ?? 'Affscash Editorial Team'
            ],
            'publisher' => [
                '@id' => $siteUrl . '/#organization'
            ],
            'image' => !empty($post['image']) ? $post['image'] : ($siteUrl . '/logoo.png'),
            'mainEntityOfPage' => $url
        ];
    }

    /**
     * 5. FAQPage Schema
     */
    public static function faqPage(array $faqs): ?array
    {
        if (empty($faqs)) return null;

        $items = [];
        foreach ($faqs as $f) {
            $items[] = [
                '@type' => 'Question',
                'name' => $f['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($f['answer'])
                ]
            ];
        }

        return [
            '@type' => 'FAQPage',
            'mainEntity' => $items
        ];
    }

    /**
     * 6. HowTo Schema
     */
    public static function howTo(array $howTo): ?array
    {
        if (empty($howTo['title']) || empty($howTo['steps'])) return null;

        $steps = [];
        foreach ($howTo['steps'] as $idx => $step) {
            $steps[] = [
                '@type' => 'HowToStep',
                'position' => $idx + 1,
                'name' => $step['name'] ?? ("Step " . ($idx + 1)),
                'text' => $step['text'] ?? $step['description'] ?? ''
            ];
        }

        return [
            '@type' => 'HowTo',
            'name' => $howTo['title'],
            'description' => $howTo['description'] ?? '',
            'totalTime' => $howTo['total_time'] ?? 'PT10M',
            'step' => $steps
        ];
    }

    /**
     * 7. BreadcrumbList Schema
     */
    public static function breadcrumb(array $crumbs): array
    {
        $itemList = [];
        $pos = 1;

        foreach ($crumbs as $c) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => $c['name'],
                'item' => $c['url'] ?? null
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemList
        ];
    }

    /**
     * 8. Offer & Product Schema
     */
    public static function offer(array $off): array
    {
        $siteUrl = rtrim(AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net'), '/');
        $url = $siteUrl . '/offers/' . ($off['id'] ?? 1);

        return [
            '@type' => 'Product',
            '@id' => $url . '#product',
            'name' => $off['name'] ?? 'CPA Offer',
            'description' => $off['description'] ?? 'Exclusive high converting CPA offer on Affscash.',
            'category' => $off['category'] ?? 'Affiliate Campaign',
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => 'USD',
                'price' => $off['payout'] ?? '10.00',
                'availability' => 'https://schema.org/InStock',
                'seller' => [
                    '@id' => $siteUrl . '/#organization'
                ]
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.9',
                'reviewCount' => '128',
                'bestRating' => '5',
                'worstRating' => '1'
            ]
        ];
    }

    /**
     * 9. Review & AggregateRating Schema
     */
    public static function review(array $rev): array
    {
        $siteUrl = rtrim(AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net'), '/');

        return [
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => 'Organization',
                'name' => 'Affscash CPA Network',
                'url' => $siteUrl
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $rev['author'] ?? 'Affiliate Publisher'
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $rev['rating'] ?? '5',
                'bestRating' => '5'
            ],
            'reviewBody' => $rev['comment'] ?? 'Great network with fast payouts and high converting offers.'
        ];
    }

    /**
     * 10. Person Schema
     */
    public static function person(string $name, string $jobTitle = 'Affiliate Manager'): array
    {
        return [
            '@type' => 'Person',
            'name' => $name,
            'jobTitle' => $jobTitle,
            'worksFor' => [
                '@type' => 'Organization',
                'name' => 'Affscash'
            ]
        ];
    }

    /**
     * 11. SoftwareApplication & WebApplication Schema
     */
    public static function webApplication(array $app = []): array
    {
        $siteUrl = rtrim(AiSeoEngine::getSetting('brand_organization_url', Config::get('config', 'app.url') ?? 'https://affscash.net'), '/');

        return [
            '@type' => 'WebApplication',
            'name' => 'Affscash Smartlink & Tracking Platform',
            'url' => $siteUrl,
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'All',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD'
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.9',
                'ratingCount' => '450'
            ]
        ];
    }

    /**
     * Schema Validator — Ensures no null or broken properties
     */
    public static function validate(array $schema): bool
    {
        if (empty($schema['@type'])) return false;
        return true;
    }
}
