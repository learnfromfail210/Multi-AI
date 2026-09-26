<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Multi-AI Terminal',
        'version' => '0.1.0',
        'environment' => getenv('APP_ENV') ?: 'development',
    ],

    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: '',
        'username' => getenv('DB_USERNAME') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],

    'ai' => [
        'openai' => [
            'api_key' => getenv('OPENAI_API_KEY') ?: '',
            'base_url' => getenv('OPENAI_BASE_URL')
                ?: 'https://api.openai.com/v1',
        ],
    ],
];
