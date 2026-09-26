<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Multi-AI Terminal',
        'version' => '0.1.0',
        'environment' => 'development',
    ],

    'database' => [
        'host' => '',
        'name' => '',
        'username' => '',
        'password' => '',
        'charset' => 'utf8mb4',
    ],

    'ai' => [
        'openai' => [
            'api_key' => '',
            'base_url' => 'https://api.openai.com/v1',
        ],
    ],
];
