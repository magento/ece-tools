<?php

return [
    'MAGENTO_CLOUD_RELATIONSHIPS' => base64_encode(json_encode(
        [
            'database' => [
                [
                    'host' => 'db',
                    'path' => 'magento2',
                    'password' => 'magento2',
                    'username' => 'magento2',
                    'port' => '3306',
                ],
            ],
            'redis' => [
                [
                    'host' => 'redis',
                    'service' => 'redis',
                    'port' => '6379',
                    'rel' => 'redis',
                    'scheme' => 'redis',
                ],
            ],
        ]
    )),
    'MAGENTO_CLOUD_ROUTES' => base64_encode(json_encode(
        [
            'http://127.0.0.1:8080/' => [
                'type' => 'upstream',
                'original_url' => 'http://{default}',
            ],
            'https://127.0.0.1:8082/' => [
                'type' => 'upstream',
                'original_url' => 'https://{default}',
            ],
        ]
    )),
    'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode([
            'ADMIN_EMAIL' => 'admin@example.com',
            'ADMIN_PASSWORD' => '123123q',
        ]
    )),
];
