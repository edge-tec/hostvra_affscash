<?php
/**
 * Theme — central helper for the platform light/dark theme.
 *
 * Resolution order for the active theme on a request:
 *   1. users.theme_preference  (per-account override, set when the user toggles)
 *   2. app.default_theme       (admin global default in config.json)
 *   3. 'light'                 (built-in fallback)
 *
 * A theme of 'system' is treated as a hint: the layout still renders with the
 * admin default, but theme.js may switch on first paint based on the visitor's
 * OS preference. Anything other than 'light'/'dark'/'system' falls back to
 * the admin default.
 *
 * The `users.theme_preference` column is auto-created on first read so the
 * feature works without a manual schema migration.
 */
class Theme {
    private static bool $columnReady = false;
    private static ?string $cached   = null;

    public const VALID = ['light', 'dark', 'system'];

    /** Active theme for the current request — never returns an invalid value. */
    public static function current(): string {
        if (self::$cached !== null) return self::$cached;

        $userPref = self::userPreference();
        if (in_array($userPref, ['light', 'dark'], true)) {
            return self::$cached = $userPref;
        }
        // 'system' is resolved client-side via theme.js — render the admin
        // default server-side so the first paint is still consistent.
        return self::$cached = self::defaultTheme();
    }

    /**
     * Returns whatever is stored on the user record — may be 'light', 'dark',
     * 'system', or null (no explicit preference). Callers MUST treat null and
     * unexpected values as "fall back to admin default".
     */
    public static function userPreference(): ?string {
        $uid = $_SESSION['user_id'] ?? null;
        if (!$uid) return null;
        self::ensureColumn();
        try {
            $r = Database::fetchOne("SELECT `theme_preference` FROM `users` WHERE id=?", [$uid]);
            $v = $r['theme_preference'] ?? null;
            if ($v !== null && in_array($v, self::VALID, true)) return $v;
        } catch (\Throwable $_) {}
        return null;
    }

    /** Admin-controlled platform default. */
    public static function defaultTheme(): string {
        $v = Config::get('config', 'app.default_theme');
        return $v === 'dark' ? 'dark' : 'light';
    }

    /** Persist a user preference. Returns true on success. */
    public static function setUserPreference(int $userId, string $theme): bool {
        if ($userId <= 0) return false;
        if (!in_array($theme, self::VALID, true)) return false;
        self::ensureColumn();
        try {
            Database::query("UPDATE `users` SET `theme_preference`=? WHERE id=?", [$theme, $userId]);
            self::$cached = null;
            return true;
        } catch (\Throwable $_) {
            return false;
        }
    }

    /**
     * Idempotent: create the theme_preference column on first read.
     * Wrapped in try/catch so the page never breaks if the column
     * already exists or DDL is restricted on this host.
     *
     * Note: installs that pre-date this removal may still have
     * 'glass' inside the ENUM and rows storing 'glass'. Both are
     * harmless — userPreference() filters against self::VALID, so
     * any stored 'glass' value is ignored and the admin default
     * (light / dark) takes over. Leaving the ENUM intact also
     * preserves rollback safety if you ever re-introduce Glass.
     */
    public static function ensureColumn(): void {
        if (self::$columnReady) return;
        self::$columnReady = true;
        try {
            Database::query(
                "ALTER TABLE `users` ADD COLUMN `theme_preference`
                 ENUM('light','dark','system') DEFAULT NULL"
            );
        } catch (\Throwable $_) { /* column exists — no-op */ }
    }
}
