<?php
/**
 * Config - JSON configuration loader/writer
 */
class Config {
    private static array $cache = [];
    private static string $configDir = '';

    public static function init(string $configDir): void {
        self::$configDir = rtrim($configDir, '/');
    }

    public static function get(string $file, ?string $key = null) {
        if (!isset(self::$cache[$file])) {
            $path = self::$configDir . '/' . $file . '.json';
            if (!file_exists($path)) return null;
            $data = json_decode(file_get_contents($path), true);
            self::$cache[$file] = $data ?? [];
        }
        if ($key === null) return self::$cache[$file];
        // Support dot notation
        $parts = explode('.', $key);
        $value = self::$cache[$file];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) return null;
            $value = $value[$part];
        }
        return $value;
    }

    public static function set(string $file, string $key, $value): bool {
        $data = self::get($file) ?? [];
        $parts = explode('.', $key);
        $ref = &$data;
        foreach ($parts as $part) {
            if (!isset($ref[$part]) || !is_array($ref[$part])) $ref[$part] = [];
            $ref = &$ref[$part];
        }
        $ref = $value;
        self::$cache[$file] = $data;
        $path = self::$configDir . '/' . $file . '.json';
        return (bool) file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public static function write(string $file, array $data): bool {
        $path = self::$configDir . '/' . $file . '.json';
        // Ensure the config directory exists before writing.
        // file_put_contents() fails silently if the directory is missing.
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        self::$cache[$file] = $data;
        $result = file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        if ($result === false) {
            error_log('[Config] Failed to write config file: ' . $path . ' — check directory permissions.');
        }
        return $result !== false;
    }

    public static function clearCache(): void {
        self::$cache = [];
    }
}
