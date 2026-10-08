<?php

namespace App\Models;

class User
{
    public function __construct(
        public readonly ?int    $id,
        public readonly string  $name,
        public readonly string  $email,
        public readonly string  $passwordHash,
        public readonly int     $authVersion = 1,
        public readonly ?string $lastLoginAt = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id:           isset($row['id']) ? (int) $row['id'] : null,
            name:         $row['name'] ?? '',
            email:        $row['email'] ?? '',
            passwordHash: $row['password_hash'] ?? '',
            authVersion:  (int) ($row['auth_version'] ?? 1),
            lastLoginAt:  $row['last_login_at'] ?? null,
            createdAt:    $row['created_at'] ?? null,
            updatedAt:    $row['updated_at'] ?? null,
        );
    }
}
