<?php

namespace App\Core;

/**
 * Session — hardened PHP session wrapper (lazy start).
 *
 *  - HttpOnly + SameSite=Lax cookie, Secure flag automatically on HTTPS
 *  - cookie scoped to the app's folder (BASE_URL) and given its own name
 *  - sessions stored in storage/sessions (so shared-host cleanup crons with a short
 *    gc_maxlifetime can't kill them early); falls back to PHP's default path
 *  - idle timeout: SESSION_LIFETIME minutes (default 120) without a request
 */
class Session
{
    private static bool $started = false;

    public static function lifetimeSeconds(): int
    {
        return max(1, (int) Env::get('SESSION_LIFETIME', 120)) * 60;
    }

    public static function start(): void
    {
        if (self::$started || PHP_SAPI === 'cli') return;

        $lifetime = self::lifetimeSeconds();

        $dir = ROOT . '/storage/sessions';
        if (!is_dir($dir)) @mkdir($dir, 0700, true);
        if (is_dir($dir) && is_writable($dir)) session_save_path($dir);

        ini_set('session.gc_maxlifetime', (string) $lifetime);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_name('doceditor_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => BASE_URL !== '' ? BASE_URL : '/',
            'secure'   => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;

        // Idle timeout
        $now = time();
        if (isset($_SESSION['_last']) && ($now - (int) $_SESSION['_last']) > $lifetime) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        $_SESSION['_last'] = $now;
    }

    /** Fully destroy the session and its cookie (logout). */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?: 'Lax',
            ]);
        }
        session_destroy();
        self::$started = false;
    }

    // ── One-request flash messages ────────────────────────────
    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}
