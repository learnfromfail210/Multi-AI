<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/User.php';

Auth::startSession();

try {
    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON request.'
        ]);

        exit;
    }

    $action = $input['action'] ?? '';

    $db = Database::connect();
    $user = new User($db);

    if ($action === 'register') {
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $displayName = trim((string) ($input['display_name'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'error' => 'Invalid email address.'
            ]);

            exit;
        }

        if (strlen($password) < 8) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'error' => 'Password must be at least 8 characters.'
            ]);

            exit;
        }

        if ($user->findByEmail($email) !== null) {
            http_response_code(409);

            echo json_encode([
                'success' => false,
                'error' => 'An account with this email already exists.'
            ]);

            exit;
        }

        $userId = $user->create(
            $email,
            $password,
            $displayName !== '' ? $displayName : null
        );

        Auth::login($userId);

        echo json_encode([
            'success' => true,
            'user_id' => $userId
        ]);

        exit;
    }

    if ($action === 'login') {
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        $account = $user->findByEmail($email);

        if (
            $account === null ||
            !$user->verifyPassword(
                $password,
                $account['password_hash']
            )
        ) {
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'error' => 'Invalid email or password.'
            ]);

            exit;
        }

        Auth::login((int) $account['id']);

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => (int) $account['id'],
                'email' => $account['email'],
                'display_name' => $account['display_name']
            ]
        ]);

        exit;
    }

    if ($action === 'logout') {
        Auth::logout();

        echo json_encode([
            'success' => true
        ]);

        exit;
    }

    if ($action === 'me') {
        $userId = Auth::userId();

        echo json_encode([
            'success' => true,
            'authenticated' => $userId !== null,
            'user_id' => $userId
        ]);

        exit;
    }

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'Unknown authentication action.'
    ]);

} catch (Throwable $exception) {
    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Internal server error.'
    ]);
}
