<?php

define('ROOT', __DIR__);

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

// ── Error handling ────────────────────────────────────────────
$debug = \App\Core\Env::get('APP_DEBUG', 'false') === 'true';
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : 0);

if (!$debug) {
    set_exception_handler(function (Throwable $e): void {
        $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
        if ($isApi) {
            \App\Core\Response::error('Internal server error', 500);
        } else {
            http_response_code(500);
            echo '<h1>500 — Internal Server Error</h1>';
        }
    });
}
