<?php

declare(strict_types=1);

final class OpenAIProvider implements AIProvider
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl = 'https://api.openai.com/v1'
    ) {
    }

    public function getName(): string
    {
        return 'OpenAI';
    }

    public function chat(
        string $model,
        array $messages
    ): array {
        $url = rtrim($this->baseUrl, '/') . '/chat/completions';

        $payload = [
            'model' => $model,
            'messages' => $messages,
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(
                'AI provider request failed: ' . $error
            );
        }

        $statusCode = curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        curl_close($ch);

        $data = json_decode($response, true);

        if (!is_array($data)) {
            throw new RuntimeException(
                'AI provider returned invalid JSON.'
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = $data['error']['message']
                ?? 'AI provider returned an error.';

            throw new RuntimeException($message);
        }

        return $data;
    }
}
