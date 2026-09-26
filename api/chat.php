<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/AI/AIProvider.php';
require_once __DIR__ . '/../app/AI/AIManager.php';
require_once __DIR__ . '/../app/AI/OpenAIProvider.php';

Auth::startSession();

try {
    $userId = Auth::userId();

    if ($userId === null) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'error' => 'Authentication required.'
        ]);

        exit;
    }

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

    $providerName = trim(
        (string) ($input['provider'] ?? '')
    );

    $model = trim(
        (string) ($input['model'] ?? '')
    );

    $messages = $input['messages'] ?? [];

    if (
        $providerName === '' ||
        $model === '' ||
        !is_array($messages)
    ) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'error' => 'Provider, model, and messages are required.'
        ]);

        exit;
    }

    $config = require __DIR__ . '/../config/config.php';

    $manager = new AIManager();

    $openAIConfig = $config['ai']['openai'];

    $manager->register(
        'openai',
        new OpenAIProvider(
            $openAIConfig['api_key'],
            $openAIConfig['base_url']
        )
    );

    $response = $manager->chat(
        $providerName,
        $model,
        $messages
    );

    echo json_encode([
        'success' => true,
        'provider' => $providerName,
        'model' => $model,
        'response' => $response
    ]);

} catch (Throwable $exception) {
    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'AI request failed.'
    ]);
}
