<?php
/**
 * PopupService — Post-login reward pop-up for affiliates.
 *
 * Promises (per spec):
 *   ✔ Does NOT modify the login system — the affiliate dashboard layout
 *     simply includes a partial that calls PopupService::currentFor().
 *   ✔ Only shown to role=affiliate when popup_enabled = 1.
 *   ✔ Per-affiliate dismissal stored in popup_dismissals so once dismissed,
 *     the same pop-up doesn't reappear on subsequent dashboard hits.
 */
class PopupService
{
    private static bool $schemaEnsured = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `popup_config` (
                `id`         INT UNSIGNED NOT NULL DEFAULT 1 PRIMARY KEY,
                `enabled`    TINYINT(1) NOT NULL DEFAULT 0,
                `title`      VARCHAR(255) NOT NULL DEFAULT '',
                `body`       MEDIUMTEXT DEFAULT NULL,
                `image_path` VARCHAR(512) DEFAULT NULL,
                `cta_label`  VARCHAR(100) DEFAULT NULL,
                `cta_url`    VARCHAR(500) DEFAULT NULL,
                `version`    INT UNSIGNED NOT NULL DEFAULT 1,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            Database::query("INSERT IGNORE INTO popup_config (id, enabled, title) VALUES (1, 0, '')");
        } catch (\Throwable $_) {}

        try {
            Database::query("CREATE TABLE IF NOT EXISTS `popup_dismissals` (
                `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `affiliate_id`  INT UNSIGNED NOT NULL,
                `version`       INT UNSIGNED NOT NULL,
                `dismissed_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uq_aff_ver` (`affiliate_id`,`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $_) {}
    }

    public static function get(): array
    {
        self::ensureSchema();
        $row = Database::fetchOne("SELECT * FROM popup_config WHERE id=1");
        return $row ?: ['enabled' => 0, 'title' => '', 'body' => '', 'image_path' => null, 'cta_label' => null, 'cta_url' => null, 'version' => 1];
    }

    public static function save(array $data): void
    {
        self::ensureSchema();
        $existing = self::get();
        $title = trim((string)($data['title'] ?? $existing['title']));
        $body  = trim((string)($data['body']  ?? $existing['body']));
        $img   = $data['image_path'] ?? $existing['image_path'];
        $cta_label = trim((string)($data['cta_label'] ?? '')) ?: null;
        $cta_url   = trim((string)($data['cta_url']   ?? '')) ?: null;
        $enabled   = empty($data['enabled']) ? 0 : 1;

        // Bump version whenever the message itself changes — this resets all
        // existing dismissals so admins can re-broadcast the same admins
        // re-announce by tweaking the body.
        $contentChanged = ($title !== $existing['title']) || ($body !== ($existing['body'] ?? ''))
                       || ($cta_label !== ($existing['cta_label'] ?? null)) || ($cta_url !== ($existing['cta_url'] ?? null));
        $version = (int)($existing['version'] ?? 1);
        if ($contentChanged) $version++;

        Database::query(
            "INSERT INTO popup_config (id, enabled, title, body, image_path, cta_label, cta_url, version)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE enabled=VALUES(enabled), title=VALUES(title), body=VALUES(body),
                                     image_path=VALUES(image_path), cta_label=VALUES(cta_label),
                                     cta_url=VALUES(cta_url), version=VALUES(version)",
            [$enabled, $title, $body, $img, $cta_label, $cta_url, $version]
        );
    }

    /** Returns the popup the given affiliate should see right now, or null. */
    public static function currentFor(int $affiliateId): ?array
    {
        self::ensureSchema();
        if ($affiliateId <= 0) return null;
        $cfg = self::get();
        if ((int)($cfg['enabled'] ?? 0) !== 1) return null;
        if (trim((string)($cfg['title'] ?? '')) === '') return null;
        $dismissed = Database::fetchOne(
            "SELECT id FROM popup_dismissals WHERE affiliate_id = ? AND version = ? LIMIT 1",
            [$affiliateId, (int)$cfg['version']]
        );
        return $dismissed ? null : $cfg;
    }

    public static function dismiss(int $affiliateId, int $version): void
    {
        if ($affiliateId <= 0 || $version <= 0) return;
        self::ensureSchema();
        try {
            Database::query(
                "INSERT IGNORE INTO popup_dismissals (affiliate_id, version) VALUES (?, ?)",
                [$affiliateId, $version]
            );
        } catch (\Throwable $_) {}
    }
}
