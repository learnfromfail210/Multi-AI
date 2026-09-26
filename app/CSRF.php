<?php

declare(strict_types=1);

final class CSRF
{
    public static function token(): string
    {
        Auth::startSession();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION['csrf_token'];
    }

    public static function validate(
        ?string $token
    ): bool {
        Auth::startSession();

        if (
            !is_string($token) ||
            $token === '' ||
            empty($_SESSION['csrf_token'])
        ) {
            return false;
        }

        return hash_equals(
            $_SESSION['csrf_token'],
            $token
        );
    }
}
