<?php
/**
 * Router - Simple URL dispatcher
 */
class Router {
    private static array $routes = [];

    public static function add(string $method, string $pattern, callable $handler): void {
        self::$routes[] = compact('method', 'pattern', 'handler');
    }

    public static function get(string $pattern, callable $handler): void {
        self::add('GET', $pattern, $handler);
    }

    public static function post(string $pattern, callable $handler): void {
        self::add('POST', $pattern, $handler);
    }

    public static function any(string $pattern, callable $handler): void {
        self::add('ANY', $pattern, $handler);
    }

    public static function dispatch(string $uri, string $method): void {
        $uri = strtok($uri, '?');
        $uri = rtrim($uri, '/') ?: '/';

        foreach (self::$routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== strtoupper($method)) continue;

            $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $route['pattern']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                call_user_func_array($route['handler'], $matches);
                return;
            }
        }

        http_response_code(404);
        $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;
        if ($isApi) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            return;
        }
        require BASE_PATH . '/views/404.php';
    }
}
