<?php

if (!function_exists('e')) {
    /** Escape a value for safe output inside HTML. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('str_chars')) {
    /** Length in characters (UTF-8). Works even when the mbstring extension is not installed. */
    function str_chars(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : (int) preg_match_all('/./us', $value);
    }
}
