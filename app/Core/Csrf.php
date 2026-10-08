<?php

namespace App\Core;

/**
 * Csrf — per-session token, checked on every state-changing (POST) request.
 * Forms send it as the hidden field "_token"; fetch() calls send the X-CSRF-Token header
 * (added automatically by the script in partials/csrf_js.php).
 */
class Csrf
{
    public static function token(): string
    {
        Session::start();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function rotate(): void
    {
        Session::start();
        unset($_SESSION['_csrf']);
    }

    public static function valid(?string $given): bool
    {
        return is_string($given) && $given !== '' && hash_equals(self::token(), $given);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }
}
