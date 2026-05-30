-- ============================================================
-- Migration 0014: Manager Invoice Requests
--
-- Affiliate Managers can now submit a payout invoice request
-- to the admin. The admin can approve (which auto-generates the
-- invoice) or reject the request with a note.
-- ============================================================

CREATE TABLE IF NOT EXISTS `manager_invoice_requests` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `manager_id`     INT UNSIGNED NOT NULL,
    `amount`         DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    `period_start`   DATE NOT NULL,
    `period_end`     DATE NOT NULL,
    `notes`          TEXT NULL,
    `status`         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_note`     TEXT NULL,
    `reviewed_by`    INT UNSIGNED NULL,
    `reviewed_at`    DATETIME NULL,
    `invoice_id`     INT UNSIGNED NULL COMMENT 'Set when approved and invoice is generated',
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_manager_id` (`manager_id`),
    INDEX `idx_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
