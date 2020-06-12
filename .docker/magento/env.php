<?php
return [
    'MAGE_MODE' => 'developer',
    'cache_types' => [
        'compiled_config' => 1,
        'config' => 1,
        'layout' => 1,
        'block_html' => 1,
        'collections' => 1,
        'reflection' => 1,
        'db_ddl' => 1,
        'eav' => 1,
        'customer_notification' => 1,
        'config_integration' => 1,
        'config_integration_api' => 1,
        'target_rule' => 1,
        'full_page' => 1,
        'translate' => 1,
        'config_webservice' => 1
    ],
    'backend' => [
        'frontName' => 'admin'
    ],
    'db' => [
        'connection' => [
            'default' => [
                'username' => 'magento2',
                'host' => 'ece-tools_db_1',
                'dbname' => 'magento2',
                'password' => 'magento2'
            ],
            'indexer' => [
                'username' => 'magento2',
                'host' => 'db',
                'dbname' => 'magento2',
                'password' => 'magento2'
            ]
        ]
    ],
    'crypt' => [
        'key' => 'd21ee91741aa400260108b92ca4a5ded'
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'x-frame-options' => 'SAMEORIGIN',
    'session' => [
        'save' => 'redis',
        'redis' => [
            'host' => 'redis',
            'port' => 6379,
            'database' => 0,
            'disable_locking' => 1
        ]
    ],
    'install' => [
        'date' => 'Fri, 15 Jun 2018 10:17:40 +0000'
    ],
    'static_content_on_demand_in_production' => 0,
    'force_html_minification' => 1,
    'cron_consumers_runner' => [
        'cron_run' => false,
        'max_messages' => 10000,
        'consumers' => [

        ]
    ],
    'cache' => [
        'frontend' => [
            'default' => [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' => [
                    'server' => 'redis',
                    'port' => 6379,
                    'database' => 1
                ]
            ],
            'page_cache' => [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' => [
                    'server' => 'redis',
                    'port' => 6379,
                    'database' => 2
                ]
            ]
        ]
    ],
    'directories' => [
        'document_root_is_pub' => true
    ],
    'cron' => [

    ],
    'lock' => [
        'provider' => 'db',
        'config' => [
            'prefix' => NULL
        ]
    ]
];
