<?php

declare(strict_types=1);

final class User
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function create(
        string $email,
        string $password,
        ?string $displayName = null
    ): int {
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $statement = $this->db->prepare(
            'INSERT INTO users (email, password_hash, display_name)
             VALUES (:email, :password_hash, :display_name)'
        );

        $statement->execute([
            'email' => strtolower(trim($email)),
            'password_hash' => $passwordHash,
            'display_name' => $displayName,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, email, password_hash, display_name, created_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $statement->execute([
            'email' => strtolower(trim($email)),
        ]);

        $user = $statement->fetch();

        return $user ?: null;
    }

    public function verifyPassword(
        string $password,
        string $passwordHash
    ): bool {
        return password_verify(
            $password,
            $passwordHash
        );
    }
}
