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
        ]
    )),
    'MAGENTO_CLOUD_ROUTES' => base64_encode(json_encode(
        [
            'http://127.0.0.1:8080/' => [
                'type' => 'upstream',
                'original_url' => 'http://{default}',
            ],
        ]
    )),
    'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode([
            'ADMIN_EMAIL' => 'admin@example.com',
            'ADMIN_PASSWORD' => '123123q',
        ]
    )),
];
