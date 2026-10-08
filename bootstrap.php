<?php

define('ROOT', __DIR__);

// URL prefix of the app when it lives in a sub-folder (e.g. "/DocEditor" under Apache/XAMPP).
// Empty string when served from the web root (php -S localhost:8000).
$__dir = PHP_SAPI === 'cli' ? '' : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $__dir === '.' ? '' : $__dir);
unset($__dir);

// ── Autoloader ────────────────────────────────────────────────
// If composer install has been run, use its autoloader (includes phpdotenv).
// Otherwise fall back to our own simple PSR-4 loader.
if (is_file(ROOT . '/vendor/autoload.php')) {
    require ROOT . '/vendor/autoload.php';

    // Load .env via vlucas/phpdotenv
    if (is_file(ROOT . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(ROOT);
        $dotenv->load();
    }

    // Bridge: make Env::get() work by reading from $_ENV (phpdotenv fills $_ENV)
    // We still register our Env class via PSR-4 autoload above, so it's available.
} else {
    // No vendor — use our minimal PSR-4 loader + built-in Env loader
    spl_autoload_register(function (string $class): void {
        // Only handle App\ namespace
        if (!str_starts_with($class, 'App\\')) return;
        $relative = str_replace(['App\\', '\\'], ['app/', '/'], $class);
        $file = ROOT . '/' . $relative . '.php';
        if (is_file($file)) require_once $file;
    });

    require_once ROOT . '/app/Core/Env.php';
    \App\Core\Env::load(ROOT . '/.env');
}

require_once ROOT . '/app/Core/helpers.php';

// ── Security headers (web requests only) ──────────────────────
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (\App\Core\Request::isHttps()) {
        header('Strict-Transport-Security: max-age=15552000');
    }
}

// ── Error handling ────────────────────────────────────────────
$debug = \App\Core\Env::get('APP_DEBUG', 'false') === 'true';
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : 0);

if (!$debug) {
    set_exception_handler(function (Throwable $e): void {
        error_log((string) $e);   // details go to the server error log, never to the browser
        $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
        if ($isApi) {
            \App\Core\Response::error('Internal server error', 500);
        } else {
            http_response_code(500);
            echo '<h1>500 — Internal Server Error</h1>';
        }
    });
}
