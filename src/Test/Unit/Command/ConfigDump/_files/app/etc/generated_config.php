<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
return [
    'scopes' => [
        'websites' => [
            'admin' => [
                'website_id' => '0',
                'code' => 'admin',
                'name' => 'Admin',
                'sort_order' => '0',
                'default_group_id' => '0',
                'is_default' => '0',
            ],
        ],
        'groups' => [
            0 => [
                'group_id' => '0',
                'website_id' => '0',
                'code' => 'default',
                'name' => 'Default',
                'root_category_id' => '0',
                'default_store_id' => '0',
            ],
        ],
        'stores' => [
            'admin' => [
                'store_id' => '0',
                'code' => 'admin',
                'website_id' => '0',
                'group_id' => '0',
                'name' => 'Admin',
                'sort_order' => '0',
                'is_active' => '1',
            ],
        ],
    ],
    'system' => [
        'default' => [
            'general' => [
                'locale' => [
                    'code' => 'en_US',
                ],
            ],
            'dev' => [
                'static' => [
                    'sign' => '1',
                ],
                'front_end_development_workflow' => [
                    'type' => 'server_side_compilation',
                ],
                'template' => [
                    'minify_html' => '0',
                ],
                'js' => [
                    'merge_files' => '0',
                    'minify_files' => '0',
                    'minify_exclude' => '
                      /tiny_mce/
                  ',
                    'session_storage_logging' => '0',
                    'translate_strategy' => 'dictionary',
                ],
                'css' => [
                    'minify_files' => '0',
                    'minify_exclude' => '
                      /tiny_mce/
                  ',
                ],
            ],
        ],
        'stores' => [
            'store1' => [
                'general' => [
                    'locale' => [
                        'code' => 'fr_FR'
                    ],
                ],
            ],
            'store2' => [
                'general' => [
                    'locale' => [
                        'code' => 'kz_KZ'
                    ],
                ],
            ],
        ],
        'websites' => [
            'base' => [
                'general' => [
                    'locale' => [
                        'code' => 'kz_KZ'
                    ],
                ],
            ],
        ],
    ],
    'modules' => [
        'Magento_Store' => 1,
        'Magento_Directory' => 1,
    ],
    'admin_user' => [
        'locale' => [
            'code' => [
                'fr_FR',
                'ua_UA',
            ],
        ],
    ],
];
