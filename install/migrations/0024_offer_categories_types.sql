-- ============================================================================
-- Migration 0024: Offer Categories & Offer Types
-- Adds a two-level taxonomy: Category → Offer Type → Offer
-- ============================================================================

-- ── offer_categories ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `offer_categories` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_cat_name` (`name`),
    UNIQUE KEY `uq_cat_slug` (`slug`),
    INDEX `idx_cat_status` (`status`),
    INDEX `idx_cat_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── offer_types ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `offer_types` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_type_cat_name` (`category_id`, `name`),
    UNIQUE KEY `uq_type_slug` (`slug`),
    INDEX `idx_type_category` (`category_id`),
    INDEX `idx_type_status` (`status`),
    INDEX `idx_type_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Add nullable FK columns to offers ───────────────────────────────────────
ALTER TABLE `offers` ADD COLUMN IF NOT EXISTS `category_id`   INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `offers` ADD COLUMN IF NOT EXISTS `offer_type_id` INT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `offers` ADD INDEX IF NOT EXISTS `idx_offer_category_id`   (`category_id`);
ALTER TABLE `offers` ADD INDEX IF NOT EXISTS `idx_offer_type_id`       (`offer_type_id`);

-- ── Seed initial categories & types ─────────────────────────────────────────
-- Idempotent: INSERT IGNORE on UNIQUE name / (category_id, name)

INSERT IGNORE INTO `offer_categories` (`name`, `slug`, `status`, `sort_order`) VALUES
    ('Sweepstakes',       'sweepstakes',        'active', 1),
    ('Finance',           'finance',            'active', 2),
    ('Free Trials',       'free-trials',        'active', 3),
    ('Subscriptions',     'subscriptions',      'active', 4),
    ('Mobile Apps',       'mobile-apps',        'active', 5),
    ('Health & Wellness', 'health-wellness',    'active', 6),
    ('Dating',            'dating',             'active', 7);

-- Finance sub-types
INSERT IGNORE INTO `offer_types` (`category_id`, `name`, `slug`, `status`, `sort_order`)
SELECT c.id, t.name, t.slug, 'active', t.sort_order
FROM (SELECT 'Credit Cards' AS name, 'credit-cards' AS slug, 1 AS sort_order
      UNION ALL SELECT 'Loans',       'loans',        2
      UNION ALL SELECT 'Insurance',   'insurance',    3
      UNION ALL SELECT 'Banking',     'banking',      4
      UNION ALL SELECT 'Crypto',      'crypto',       5
) t
CROSS JOIN `offer_categories` c WHERE c.slug = 'finance'
AND NOT EXISTS (SELECT 1 FROM `offer_types` ot WHERE ot.category_id = c.id AND ot.name = t.name);

-- Mobile Apps sub-types
INSERT IGNORE INTO `offer_types` (`category_id`, `name`, `slug`, `status`, `sort_order`)
SELECT c.id, t.name, t.slug, 'active', t.sort_order
FROM (SELECT 'App Installs' AS name, 'app-installs' AS slug, 1 AS sort_order
) t
CROSS JOIN `offer_categories` c WHERE c.slug = 'mobile-apps'
AND NOT EXISTS (SELECT 1 FROM `offer_types` ot WHERE ot.category_id = c.id AND ot.name = t.name);

-- Health & Wellness sub-types
INSERT IGNORE INTO `offer_types` (`category_id`, `name`, `slug`, `status`, `sort_order`)
SELECT c.id, t.name, t.slug, 'active', t.sort_order
FROM (SELECT 'Beauty'        AS name, 'beauty'        AS slug, 1 AS sort_order
      UNION ALL SELECT 'Personal Care', 'personal-care', 2
      UNION ALL SELECT 'Weight Loss',   'weight-loss',   3
) t
CROSS JOIN `offer_categories` c WHERE c.slug = 'health-wellness'
AND NOT EXISTS (SELECT 1 FROM `offer_types` ot WHERE ot.category_id = c.id AND ot.name = t.name);

-- Dating sub-types
INSERT IGNORE INTO `offer_types` (`category_id`, `name`, `slug`, `status`, `sort_order`)
SELECT c.id, t.name, t.slug, 'active', t.sort_order
FROM (SELECT 'Mainstream Dating' AS name, 'mainstream-dating' AS slug, 1 AS sort_order
      UNION ALL SELECT 'Adult Dating',     'adult-dating',      2
      UNION ALL SELECT 'Casual Dating',    'casual-dating',     3
) t
CROSS JOIN `offer_categories` c WHERE c.slug = 'dating'
AND NOT EXISTS (SELECT 1 FROM `offer_types` ot WHERE ot.category_id = c.id AND ot.name = t.name);

-- Backfill: Assign previous offers without a category or with Dating to the Dating category
UPDATE `offers`
SET `category_id` = (SELECT `id` FROM `offer_categories` WHERE `slug` = 'dating' LIMIT 1),
    `category` = 'Dating'
WHERE `category_id` IS NULL OR `category` = 'Dating' OR `category` IS NULL OR `category` = '';

