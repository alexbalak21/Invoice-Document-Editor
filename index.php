<?php
/**
 * Root entry point — allows running the app from the project root
 * without pointing the web server at public/.
 *
 * Works with:
 *   php -S localhost:8000          (built-in server from project root)
 *   http://localhost/doceditor/    (Apache/Nginx with DocumentRoot at project root)
 */

// Serve real static files (css, js, images, fonts) directly
$uri = $_SERVER['REQUEST_URI'];
$path = __DIR__ . '/public' . parse_url($uri, PHP_URL_PATH);

if (is_file($path)) {
    return false; // Let the built-in server serve it
}

// Everything else goes through the front controller
require __DIR__ . '/public/index.php';
    