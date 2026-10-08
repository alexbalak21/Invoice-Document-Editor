<?php

namespace App\Core;

/**
 * Router — maps GET/POST requests to controller actions.
 *
 * Usage:
 *   $router->get('/documents',        [DocumentController::class, 'index']);
 *   $router->post('/api/documents',   [DocumentController::class, 'store']);
 *   $router->dispatch();
 */
class Router
{
    private array $routes = [];

    /**
     * Options:  ['public' => true]  no login required (e.g. the login page)
     *           ['csrf'   => false] skip the CSRF check on a POST route
     */
    public function get(string $path, array $handler, array $options = []): void
    {
        $this->routes[] = ['GET', $path, $handler, $options];
    }

    public function post(string $path, array $handler, array $options = []): void
    {
        $this->routes[] = ['POST', $path, $handler, $options];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip base path if app is in a sub-folder
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = '/' . ltrim($uri, '/');

        foreach ($this->routes as [$routeMethod, $routePath, $handler, $options]) {
            if ($routeMethod !== $method) continue;

            // Build regex from path — :param captures named segments
            $pattern = preg_replace('#:([a-zA-Z_]+)#', '(?P<$1>[^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                // Named capture groups become route params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->guard($method, $uri, $options);
                [$class, $action] = $handler;
                (new $class())->$action($params);
                return;
            }
        }

        // 404
        http_response_code(404);
        if (str_starts_with($uri, '/api/')) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Route not found']);
        } else {
            echo '<h1>404 — Page not found</h1>';
        }
    }

    /**
     * Every route needs a logged-in user unless it is marked public,
     * and every POST needs a valid CSRF token unless the route opts out.
     */
    private function guard(string $method, string $uri, array $options): void
    {
        $isApi = str_starts_with($uri, '/api/');

        if (empty($options['public']) && !Auth::check()) {
            if ($isApi) Response::error('Not authenticated', 401);
            $next = $method === 'GET' ? '?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '') : '';
            Response::redirect('/login' . $next);
        }

        if ($method === 'POST' && ($options['csrf'] ?? true) && !Csrf::valid(Request::csrfToken())) {
            if ($isApi) Response::error('Session expired. Please reload the page.', 419);
            http_response_code(419);
            echo '<!DOCTYPE html><meta charset="utf-8"><title>Page expired</title>'
               . '<body style="font-family:system-ui,sans-serif;max-width:480px;margin:80px auto;padding:0 20px">'
               . '<h1 style="font-size:20px">Page expired</h1>'
               . '<p>Your session expired, or cookies are disabled in your browser.</p>'
               . '<p><a href="' . e(BASE_URL) . '/login">Back to the sign-in page</a></p></body>';
            exit;
        }
    }
}
