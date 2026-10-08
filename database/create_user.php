#!/usr/bin/env php
<?php
/**
 * Create a user — or reset a password. There is no public registration page.
 *
 *   php database/create_user.php                          interactive
 *   php database/create_user.php "Jane Doe" jane@site.com asks only for the password
 *   php database/create_user.php --sql                    prints an INSERT you can paste into phpMyAdmin
 *                                                         (no database connection needed)
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Repositories\UserRepository;
use App\Services\UserService;

// ── helpers ───────────────────────────────────────────────────
function ask(string $label): string
{
    fwrite(STDERR, $label . ': ');            // prompts go to stderr so `--sql > file.sql` stays clean
    return trim((string) fgets(STDIN));
}

function askHidden(string $label): string
{
    fwrite(STDERR, $label . ': ');
    $interactive = function_exists('stream_isatty') && stream_isatty(STDIN);

    if ($interactive && PHP_OS_FAMILY === 'Windows') {
        $ps = 'powershell -NoProfile -Command "$p = Read-Host -AsSecureString; '
            . '[Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($p))"';
        $value = trim((string) shell_exec($ps));
        return $value;                         // PowerShell already printed the newline
    }
    if ($interactive) {
        shell_exec('stty -echo');
        $value = trim((string) fgets(STDIN));
        shell_exec('stty echo');
        fwrite(STDERR, "\n");
        return $value;
    }
    return trim((string) fgets(STDIN));        // piped input (scripts)
}

function fail(string $message): never
{
    fwrite(STDERR, "✗ $message\n");
    exit(1);
}

function sqlQuote(string $s): string
{
    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $s) . "'";
}

// ── input ─────────────────────────────────────────────────────
$args    = array_slice($argv, 1);
$sqlMode = in_array('--sql', $args, true);
$args    = array_values(array_filter($args, fn($a) => $a !== '--sql'));

$name  = $args[0] ?? ask('Name');
$email = strtolower($args[1] ?? ask('Email'));

if ($name === '' || str_chars($name) > 100)                                  fail('Name is required (100 characters max).');
if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL))      fail('That is not a valid email address.');

$password = askHidden('Password (' . UserService::PASSWORD_MIN . '-' . UserService::PASSWORD_MAX . ' characters)');
if ($error = UserService::passwordError($password))                          fail($error);
if (askHidden('Repeat password') !== $password)                              fail('The two passwords do not match.');

$hash = password_hash($password, PASSWORD_DEFAULT);

// ── --sql: no DB connection, just print the statement ─────────
if ($sqlMode) {
    echo "\n-- Paste this into phpMyAdmin (SQL tab). Safe to run again: it resets the password of an existing email.\n";
    echo 'INSERT INTO users (name, email, password_hash) VALUES (' . sqlQuote($name) . ', ' . sqlQuote($email) . ', ' . sqlQuote($hash) . ")\n"
       . "ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), auth_version = auth_version + 1;\n";
    exit(0);
}

// ── create or reset directly in the database ──────────────────
try {
    $users    = new UserRepository();
    $existing = $users->findByEmail($email);

    if ($existing) {
        $answer = strtolower(ask("A user with this email already exists. Reset their password? [y/N]"));
        if ($answer !== 'y' && $answer !== 'yes') { echo "Nothing changed.\n"; exit(0); }
        $users->updatePassword((int) $existing->id, $hash);
        echo "✓ Password reset for {$existing->email} (other sessions were signed out).\n";
    } else {
        $users->create($name, $email, $hash);
        echo "✓ User created: $name <$email>\n";
    }
} catch (Throwable $e) {
    fail('Database error: ' . $e->getMessage() . "\n  Did you run  php database/migrate.php  ?");
}
