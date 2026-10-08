<?php

namespace App\Core;

class Response
{
    /**
     * Send a JSON response and exit.
     */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function ok(array $data = []): never
    {
        self::json(array_merge(['ok' => true], $data));
    }

    /** Redirect to a path inside the app (BASE_URL is added), e.g. Response::redirect('/login'). */
    public static function redirect(string $path, int $status = 302): never
    {
        self::redirectTo(BASE_URL . $path, $status);
    }

    /** Redirect to a full local path that already includes BASE_URL. */
    public static function redirectTo(string $location, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $location);
        exit;
    }

    public static function error(string $message, int $status = 400): never
    {
        self::json(['ok' => false, 'error' => $message], $status);
    }

    /**
     * Render a PHP view file with extracted variables.
     *
     * @param string $view   Dot-notation path: 'documents.index' → resources/views/documents/index.php
     * @param array  $data   Variables to extract into the view scope
     * @param string $layout Layout file name (resources/views/layouts/<layout>.php), or '' for none
     */
    public static function view(string $view, array $data = [], string $layout = 'app'): void
    {
        $viewFile = ROOT . '/resources/views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException("View not found: $viewFile");
        }

        // Render view into $content buffer
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === '') {
            echo $content;
            return;
        }

        $layoutFile = ROOT . '/resources/views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Layout not found: $layoutFile");
        }

        // Layout receives $content
        require $layoutFile;
    }
}
