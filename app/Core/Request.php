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
