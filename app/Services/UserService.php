<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;

/** Profile management for the logged-in user (name, email, password). */
class UserService
{
    public const PASSWORD_MIN = 10;
    public const PASSWORD_MAX = 72;   // bcrypt only uses the first 72 bytes

    public function __construct(
        private UserRepository         $users    = new UserRepository(),
        private LoginAttemptRepository $attempts = new LoginAttemptRepository(),
        private AuthService            $auth     = new AuthService(),
    ) {}

    public static function passwordError(string $password): ?string
    {
        if (strlen($password) < self::PASSWORD_MIN) {
            return 'The password must be at least ' . self::PASSWORD_MIN . ' characters long.';
        }
        if (strlen($password) > self::PASSWORD_MAX) {
            return 'The password can be at most ' . self::PASSWORD_MAX . ' characters long.';
        }
        return null;
    }

    /** @return array{ok:bool, errors:array<string,string>, user?:User} */
    public function updateProfile(User $current, string $name, string $email, string $currentPassword, string $ip): array
    {
        $errors = [];
        $name   = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '');
        $email  = strtolower(trim($email));

        if ($name === '')               $errors['name'] = 'Name is required.';
        elseif (str_chars($name) > 100) $errors['name'] = 'Name is too long (100 characters max).';

        if ($email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($this->users->emailTaken($email, (int) $current->id)) {
            $errors['email'] = 'This email address is already used by another account.';
        }

        // Changing the login email needs the current password
        if (!isset($errors['email']) && $email !== strtolower($current->email)) {
            if ($msg = $this->checkCurrentPassword($current, $currentPassword, $ip)) {
                $errors['current_password'] = $msg;
            }
        }

        if ($errors) return ['ok' => false, 'errors' => $errors];

        $this->users->updateProfile((int) $current->id, $name, $email);
        return ['ok' => true, 'errors' => [], 'user' => $this->users->findById((int) $current->id)];
    }

    /** @return array{ok:bool, errors:array<string,string>, user?:User} */
    public function changePassword(User $current, string $currentPassword, string $new, string $confirm, string $ip): array
    {
        $errors = [];

        if ($msg = $this->checkCurrentPassword($current, $currentPassword, $ip)) {
            $errors['current_password'] = $msg;
        }
        if ($msg = self::passwordError($new)) {
            $errors['new_password'] = $msg;
        } elseif (password_verify($new, $current->passwordHash)) {
            $errors['new_password'] = 'The new password must be different from the current one.';
        }
        if (!isset($errors['new_password']) && $new !== $confirm) {
            $errors['confirm_password'] = 'The two passwords do not match.';
        }

        if ($errors) return ['ok' => false, 'errors' => $errors];

        $this->users->updatePassword((int) $current->id, password_hash($new, PASSWORD_DEFAULT));
        return ['ok' => true, 'errors' => [], 'user' => $this->users->findById((int) $current->id)];
    }

    /** Returns an error message, or null when the current password is right. Failures count towards the login throttle. */
    private function checkCurrentPassword(User $user, string $password, string $ip): ?string
    {
        $key = strtolower($user->email);

        if ($this->auth->isThrottled($ip, $key)) {
            return 'Too many failed attempts. Please wait a few minutes and try again.';
        }
        if ($password === '') {
            return 'Enter your current password to confirm this change.';
        }
        if (strlen($password) > 1024 || !password_verify($password, $user->passwordHash)) {
            $this->attempts->record($ip, $key);
            return 'Current password is incorrect.';
        }
        return null;
    }
}
