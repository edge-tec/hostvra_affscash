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
        return self::write($file, $data);
    }

    public static function write(string $file, array $data): bool {
        $path = self::$configDir . '/' . $file . '.json';
        // Ensure the config directory exists before writing.
        // file_put_contents() fails silently if the directory is missing.
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        self::$cache[$file] = $data;

        // Try setting write permission if file exists but is read-only for current PHP process
        if (file_exists($path) && !is_writable($path)) {
            @chmod($path, 0666);
        }

        $result = @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

        // Fallback: If write failed, attempt unlinking existing file if parent directory is writable
        if ($result === false && file_exists($path) && is_writable($dir)) {
            @chmod($path, 0666);
            @unlink($path);
            $result = @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        }

        if ($result !== false) {
            @chmod($path, 0666);
        } else {
            $err = error_get_last();
            $errMsg = $err ? $err['message'] : 'Unknown error';
            error_log('[Config] Failed to write config file: ' . $path . ' — ' . $errMsg);
            // Also store it in a static variable so controllers can access the real error
            self::$lastError = $errMsg;
        }
        return $result !== false;
    }
    
    public static $lastError = '';

    public static function clearCache(): void {
        self::$cache = [];
    }
}
