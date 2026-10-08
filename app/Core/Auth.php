<?php

namespace App\Core;

use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Auth — who is logged in for this request.
 * The session only stores the user id + an "auth version"; the user row is loaded on every
 * request, so a deleted user or a changed password (auth_version bump) ends other sessions.
 */
class Auth
{
    private static ?User $user = null;
    private static bool $resolved = false;

    public static function user(): ?User
    {
        if (self::$resolved) return self::$user;
        self::$resolved = true;

        Session::start();
        $id = $_SESSION['uid'] ?? null;
        if (!$id) return null;

        $user = (new UserRepository())->findById((int) $id);
        if (!$user || $user->authVersion !== (int) ($_SESSION['auth_v'] ?? 0)) {
            unset($_SESSION['uid'], $_SESSION['auth_v']);
            return null;
        }
        return self::$user = $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(User $user): void
    {
        Session::start();
        session_regenerate_id(true);      // prevents session fixation
        Csrf::rotate();
        $_SESSION['uid']    = $user->id;
        $_SESSION['auth_v'] = $user->authVersion;
        self::$user     = $user;
        self::$resolved = true;
    }

    /** Keep the current session valid after the user's auth_version changed (password change). */
    public static function refresh(User $user): void
    {
        Session::start();
        session_regenerate_id(true);
        $_SESSION['uid']    = $user->id;
        $_SESSION['auth_v'] = $user->authVersion;
        self::$user     = $user;
        self::$resolved = true;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user     = null;
        self::$resolved = true;
    }
}
