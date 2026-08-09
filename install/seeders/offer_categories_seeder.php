<?php
/**
 * Offer Categories & Types Seeder
 *
 * Idempotent — safe to run multiple times without creating duplicates.
 * Can be called from CLI: php install/seeders/offer_categories_seeder.php
 * Or from admin panel via OfferCategoryController::runSeeder()
 */

// When called from CLI, bootstrap the app
if (php_sapi_name() === 'cli' && !class_exists('Database')) {
    define('BASE_PATH', dirname(__DIR__, 2));
    require BASE_PATH . '/core/Config.php';
    require BASE_PATH . '/core/Database.php';
    Config::init(BASE_PATH . '/config');
}

class OfferCategorySeeder
{
    /**
     * Taxonomy definition.
     * Key = Category name, Value = array of Offer Type names (empty = no sub-types).
     */
    private const TAXONOMY = [
        'Sweepstakes'       => [],
        'Finance'           => ['Credit Cards', 'Loans', 'Insurance', 'Banking', 'Crypto'],
        'Free Trials'       => [],
        'Subscriptions'     => [],
        'Mobile Apps'       => ['App Installs'],
        'Health & Wellness' => ['Beauty', 'Personal Care', 'Weight Loss'],
    ];

    /**
     * Run the seeder. Returns summary of what was created.
     */
    public static function run(): array
    {
        $created = ['categories' => 0, 'types' => 0, 'skipped' => 0];
        $sortOrder = 0;

        foreach (self::TAXONOMY as $categoryName => $offerTypes) {
            $sortOrder++;
            $slug = self::slugify($categoryName);

            // Check if category already exists
            $existing = Database::fetchOne(
                "SELECT id FROM offer_categories WHERE name = ?",
                [$categoryName]
            );

            if ($existing) {
                $categoryId = (int)$existing['id'];
                $created['skipped']++;
            } else {
                $categoryId = Database::insert('offer_categories', [
                    'name'       => $categoryName,
                    'slug'       => $slug,
                    'status'     => 'active',
                    'sort_order' => $sortOrder,
                ]);
                $created['categories']++;
            }

            // Seed offer types for this category
            $typeSortOrder = 0;
            foreach ($offerTypes as $typeName) {
                $typeSortOrder++;
                $typeSlug = self::slugify($typeName);

                $existingType = Database::fetchOne(
                    "SELECT id FROM offer_types WHERE category_id = ? AND name = ?",
                    [$categoryId, $typeName]
                );

                if ($existingType) {
                    $created['skipped']++;
                    continue;
                }

                // Ensure slug is unique across all types
                $slugBase = $typeSlug;
                $slugSuffix = 0;
                while (Database::fetchOne("SELECT id FROM offer_types WHERE slug = ?", [$typeSlug])) {
                    $slugSuffix++;
                    $typeSlug = $slugBase . '-' . $slugSuffix;
                }

                Database::insert('offer_types', [
                    'category_id' => $categoryId,
                    'name'        => $typeName,
                    'slug'        => $typeSlug,
                    'status'      => 'active',
                    'sort_order'  => $typeSortOrder,
                ]);
                $created['types']++;
            }
        }

        return $created;
    }

    /**
     * Generate URL-friendly slug from a name.
     */
    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    echo "Running Offer Category & Type Seeder...\n";
    $result = OfferCategorySeeder::run();
    echo "Done! Created {$result['categories']} categories, {$result['types']} types. Skipped {$result['skipped']} existing.\n";
}
