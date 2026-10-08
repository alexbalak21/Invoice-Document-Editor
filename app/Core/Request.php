<?php

namespace App\Core;

/**
 * Request — wraps $_GET, $_POST, and php://input JSON body.
 */
class Request
{
    private array $body = [];

    public function __construct()
    {
        $raw = file_get_contents('php://input');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $this->body = $decoded;
            }
        }
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $_POST[$key] ?? $default;
    }

    /** Return entire JSON body as array */
    public function all(): array
    {
        return $this->body;
    }

    /** True when the request came in over HTTPS (also behind a trusted proxy, see TRUSTED_PROXY). */
    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') return true;
        if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;
        return Env::get('TRUSTED_PROXY', 'false') === 'true'
            && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    /** Client IP. Only trusts X-Forwarded-For when TRUSTED_PROXY=true (i.e. you sit behind a proxy/CDN). */
    public static function ip(): string
    {
        if (Env::get('TRUSTED_PROXY', 'false') === 'true' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $first = trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) return $first;
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /** CSRF token sent by a form (_token) or by fetch() (X-CSRF-Token header). */
    public static function csrfToken(): ?string
    {
        $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_token'] ?? null;
        return is_string($t) ? $t : null;
    }

    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }
}
