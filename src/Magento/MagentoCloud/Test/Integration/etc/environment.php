<?php

return [
    'relationships' => [
        'database' => [
            0 => [
                'host' => 'localhost',
                'path' => 'integration_tests',
                'password' => '',
                'username' => 'root',
                'port' => '3306',
            ],
        ],
    ],
    'routes' => [
        'http://localhost/' => [
            'type' => 'upstream',
            'original_url' => 'http://{default}',
        ],
        'https://localhost/' => [
            'type' => 'upstream',
            'original_url' => 'https://{default}',
        ],
    ],
    'variables' => [

    ],
];
