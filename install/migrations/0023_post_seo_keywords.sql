-- Migration 0023: Create post_seo_keywords table for SEO Keyword System
-- Isolated extension table - preserves existing schema and backward compatibility.

CREATE TABLE IF NOT EXISTS `post_seo_keywords` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `post_id`      INT UNSIGNED NOT NULL,
  `keyword`      VARCHAR(255) NOT NULL,
  `keyword_type` ENUM('primary', 'secondary', 'long_tail', 'related', 'focus') NOT NULL,
  `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_post_id` (`post_id`),
  INDEX `idx_keyword_type` (`keyword_type`),
  UNIQUE KEY `uq_post_type_kw` (`post_id`, `keyword_type`, `keyword`),
  CONSTRAINT `fk_post_seo_keywords_post` FOREIGN KEY (`post_id`) REFERENCES `landing_posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
