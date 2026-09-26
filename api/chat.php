<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Auth.php';
require_once __DIR__ . '/../app/Conversation.php';
require_once __DIR__ . '/../app/Message.php';
require_once __DIR__ . '/../app/AI/AIProvider.php';
require_once __DIR__ . '/../app/AI/AIManager.php';
require_once __DIR__ . '/../app/AI/OpenAIProvider.php';

Auth::startSession();

try {
    /*
     * Require authentication.
     */
    $userId = Auth::userId();

    if ($userId === null) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'error' => 'Authentication required.'
        ]);

        exit;
    }

    /*
     * Read JSON request.
     */
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

    /*
     * Read request values.
     */
    $conversationId = isset($input['conversation_id'])
        ? (int) $input['conversation_id']
        : null;

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
     * Validate request.
     */
    if ($providerName === '') {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'error' => 'AI provider is required.'
        ]);

        exit;
    }

    if ($model === '') {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'error' => 'AI model is required.'
        ]);

        exit;
    }

    if ($message === '') {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'error' => 'Message cannot be empty.'
        ]);

        exit;
    }

    /*
     * Connect to database.
     */
    $db = Database::connect();

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
     * Save the user's message.
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

    /*
     * Convert database messages into
     * the format expected by the AI provider.
     */
    $aiMessages = [];

    foreach ($storedMessages as $storedMessage) {
        $aiMessages[] = [
            'role' => $storedMessage['role'],
            'content' => $storedMessage['content'],
        ];
    }

    /*
     * Load application configuration.
     */
    $config = require __DIR__ . '/../config/config.php';

    /*
     * Create AI manager.
     */
    $manager = new AIManager();

    /*
     * Register OpenAI.
     */
    $openAIConfig = $config['ai']['openai'];

    $manager->register(
        'openai',
        new OpenAIProvider(
            $openAIConfig['api_key'],
            $openAIConfig['base_url']
        )
    );

    /*
     * Send conversation to the AI provider.
     */
    $aiResponse = $manager->chat(
        $providerName,
        $model,
        $aiMessages
    );

    /*
     * Extract the assistant response.
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
     * Save the AI response.
     */
    $assistantMessageId = $messageModel->create(
        $conversationId,
        'assistant',
        $assistantMessage,
        $model
    );

    /*
     * Return the result.
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

    /*
     * Log the detailed error on the server.
     * Do not expose internal details to users.
     */
    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'AI request failed.'
    ]);
}
