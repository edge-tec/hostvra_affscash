<?php
/**
 * Cache - In-Memory Caching Layer (Redis with Transparent Fallback)
 *
 * Provides high-throughput caching for offers, caps, and tracking rules.
 * If Redis is unavailable or unconfigured, all calls gracefully pass through
 * without errors or disruption.
 */
class Cache {
    private static ?\Redis $redis = null;
    private static bool $triedConnect = false;
    private static bool $redisAvailable = false;
    private static array $memoryCache = []; // In-process request cache

    /**
     * Get the Redis instance if connected, or null.
     */
    public static function getRedis(): ?\Redis {
        if (self::$triedConnect) {
            return self::$redis;
        }
        self::$triedConnect = true;

        // Check if Redis extension is loaded and enabled in config
        if (!class_exists('Redis')) {
            self::$redisAvailable = false;
            return null;
        }

        try {
            $cfg = Config::get('config', 'redis') ?? [];
            if (empty($cfg['enabled'])) {
                self::$redisAvailable = false;
                return null;
            }

            $host = $cfg['host'] ?? '127.0.0.1';
            $port = (int)($cfg['port'] ?? 6379);
            $timeout = (float)($cfg['timeout'] ?? 0.2); // Fast 200ms timeout
            $db = (int)($cfg['database'] ?? 0);

            $r = new \Redis();
            $connected = @$r->connect($host, $port, $timeout);
            if ($connected) {
                if (!empty($cfg['password'])) {
                    @$r->auth($cfg['password']);
                }
                if ($db > 0) {
                    @$r->select($db);
                }
                self::$redis = $r;
                self::$redisAvailable = true;
            } else {
                self::$redisAvailable = false;
            }
        } catch (\Throwable $e) {
            self::$redis = null;
            self::$redisAvailable = false;
        }

        return self::$redis;
    }

    /**
     * Check if Redis is actively connected and usable.
     */
    public static function isRedisAvailable(): bool {
        self::getRedis();
        return self::$redisAvailable;
    }

    /**
     * Retrieve an item from cache.
     */
    public static function get(string $key, $default = null) {
        if (isset(self::$memoryCache[$key])) {
            return self::$memoryCache[$key];
        }

        $r = self::getRedis();
        if ($r) {
            try {
                $val = $r->get($key);
                if ($val !== false && $val !== null) {
                    $decoded = json_decode($val, true);
                    $result = ($decoded !== null || $val === 'null') ? $decoded : $val;
                    self::$memoryCache[$key] = $result;
                    return $result;
                }
            } catch (\Throwable $e) {}
        }

        return $default;
    }

    /**
     * Store an item in cache with a TTL (seconds).
     */
    public static function set(string $key, $value, int $ttl = 300): bool {
        self::$memoryCache[$key] = $value;

        $r = self::getRedis();
        if ($r) {
            try {
                $payload = is_scalar($value) ? (string)$value : json_encode($value);
                return (bool)$r->setex($key, max(1, $ttl), $payload);
            } catch (\Throwable $e) {}
        }

        return true;
    }

    /**
     * Delete one or more keys from cache.
     */
    public static function del(string ...$keys): int {
        foreach ($keys as $k) {
            unset(self::$memoryCache[$k]);
        }

        $r = self::getRedis();
        if ($r && !empty($keys)) {
            try {
                return (int)$r->del(...$keys);
            } catch (\Throwable $e) {}
        }

        return count($keys);
    }

    /**
     * Atomic increment of a counter in Redis (useful for click/cap counters).
     */
    public static function incr(string $key, int $by = 1, int $ttl = 86400): int {
        $r = self::getRedis();
        if ($r) {
            try {
                $val = $r->incrBy($key, $by);
                if ($val === $by && $ttl > 0) {
                    $r->expire($key, $ttl);
                }
                return (int)$val;
            } catch (\Throwable $e) {}
        }

        return 0;
    }

    /**
     * Remember an item in cache, executing the callback if not found.
     */
    public static function remember(string $key, int $ttl, callable $callback) {
        $val = self::get($key);
        if ($val !== null) {
            return $val;
        }

        $val = $callback();
        if ($val !== null) {
            self::set($key, $val, $ttl);
        }

        return $val;
    }

    /**
     * Cached offer lookup: loads offer row with 60-second TTL.
     */
    public static function getOffer(int $offerId): ?array {
        if ($offerId <= 0) return null;
        $cacheKey = "offer:meta:{$offerId}";

        return self::remember($cacheKey, 60, function() use ($offerId) {
            return Database::fetchOne(
                "SELECT * FROM `offers` WHERE `id` = ? LIMIT 1",
                [$offerId]
            );
        });
    }

    /**
     * Invalidate cached offer when an offer is edited.
     */
    public static function invalidateOffer(int $offerId): void {
        self::del("offer:meta:{$offerId}", "offer:links:{$offerId}");
    }
}
