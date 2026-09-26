<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/CSRF.php';
require_once __DIR__ . '/../app/RateLimiter.php';
require_once __DIR__ . '/../app/Conversation.php';
require_once __DIR__ . '/../app/Message.php';
require_once __DIR__ . '/../app/AI/AIProvider.php';
require_once __DIR__ . '/../app/AI/AIManager.php';
require_once __DIR__ . '/../app/AI/OpenAIProvider.php';

Auth::startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed.'
    ]);

    exit;
}

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

    $rawInput = file_get_contents('php://input');

    $input = json_decode(
        $rawInput,
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

    if (!CSRF::validate($input['csrf_token'] ?? null)) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'error' => 'Invalid CSRF token.'
        ]);

        exit;
    }

    /*
     * Connect to database.
     */
    $db = Database::connect();

    /*
     * Rate limit chat requests.
     *
     * Maximum:
     * 20 requests per user per 60 seconds.
     */
    $rateLimiter = new RateLimiter($db);

    if (!$rateLimiter->check(
        $userId,
        'chat',
        20,
        60
    )) {
        http_response_code(429);

        echo json_encode([
            'success' => false,
            'error' => 'Too many requests. Please try again later.'
        ]);

        exit;
    }

    /*
     * Read conversation ID.
     */
    $conversationId = null;

    if (
        array_key_exists('conversation_id', $input) &&
        $input['conversation_id'] !== null &&
        $input['conversation_id'] !== ''
    ) {
        if (
            filter_var(
                $input['conversation_id'],
                FILTER_VALIDATE_INT
            ) === false
        ) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'error' => 'Invalid conversation ID.'
            ]);

            exit;
        }

        $conversationId = (int) $input['conversation_id'];

        if ($conversationId <= 0) {
            http_response_code(422);

            echo json_encode([
                'success' => false,
                'error' => 'Invalid conversation ID.'
            ]);

            exit;
        }
    }

    /*
     * Read provider, model and message.
     */
    $providerName = trim(
        (string) ($input['provider'] ?? '')
    );

    $model = trim(
        (string) ($input['model'] ?? '')
    );

    $message = trim(
        (string) ($input['message'] ?? '')
    );

    /*
     * Only currently configured provider.
     */
    if ($providerName !== 'openai') {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'error' => 'Unsupported AI provider.'
        ]);

        exit;
    }

    /*
     * Validate model.
     */
    if ($model === '') {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'error' => 'AI model is required.'
        ]);

        exit;
    }

    if (strlen($model) > 100) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'error' => 'AI model name is too long.'
        ]);

        exit;
    }

    /*
     * Validate message.
     */
    if ($message === '') {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'error' => 'Message cannot be empty.'
        ]);

        exit;
    }

    $messageLength = function_exists('mb_strlen')
        ? mb_strlen($message, 'UTF-8')
        : strlen($message);

    if ($messageLength > 12000) {
        http_response_code(413);

        echo json_encode([
            'success' => false,
            'error' => 'Message is too long. Maximum length is 12000 characters.'
        ]);

        exit;
    }

    /*
     * Load application configuration.
     */
    $config = require __DIR__ . '/../config/config.php';

    $openAIConfig = $config['ai']['openai'];

    if (
        !isset($openAIConfig['api_key']) ||
        trim((string) $openAIConfig['api_key']) === ''
    ) {
        http_response_code(503);

        echo json_encode([
            'success' => false,
            'error' => 'AI provider is not configured.'
        ]);

        exit;
    }

    /*
     * Create models.
     */
    $conversationModel = new Conversation($db);
    $messageModel = new Message($db);

    /*
     * Create or verify conversation.
     */
    if ($conversationId === null) {
        $conversationId = $conversationModel->create(
            $userId,
            'New Conversation'
        );
    } else {
        $conversation = $conversationModel->find(
            $conversationId,
            $userId
        );

        if ($conversation === null) {
            http_response_code(404);

            echo json_encode([
                'success' => false,
                'error' => 'Conversation not found.'
            ]);

            exit;
        }
    }

    /*
     * Save user message.
     */
    $messageModel->create(
        $conversationId,
        'user',
        $message,
        null
    );

    /*
     * Load conversation history.
     */
    $storedMessages =
        $messageModel->allForConversation(
            $conversationId
        );

    $aiMessages = [];

    foreach ($storedMessages as $storedMessage) {
        $aiMessages[] = [
            'role' => $storedMessage['role'],
            'content' => $storedMessage['content'],
        ];
    }

    /*
     * Create AI manager.
     */
    $manager = new AIManager();

    /*
     * Register OpenAI.
     */
    $manager->register(
        'openai',
        new OpenAIProvider(
            $openAIConfig['api_key'],
            $openAIConfig['base_url']
        )
    );

    /*
     * Send request to AI provider.
     */
    $aiResponse = $manager->chat(
        $providerName,
        $model,
        $aiMessages
    );

    /*
     * Extract assistant response.
     */
    $assistantMessage =
        $aiResponse['choices'][0]['message']['content']
        ?? null;

    if (
        !is_string($assistantMessage) ||
        trim($assistantMessage) === ''
    ) {
        throw new RuntimeException(
            'AI provider returned an empty response.'
        );
    }

    /*
     * Save assistant response.
     */
    $assistantMessageId = $messageModel->create(
        $conversationId,
        'assistant',
        $assistantMessage,
        $model
    );

    /*
     * Return response.
     */
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversationId,
        'message_id' => $assistantMessageId,
        'provider' => $providerName,
        'model' => $model,
        'message' => $assistantMessage
    ]);

} catch (Throwable $exception) {

    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'AI request failed.'
    ]);
}