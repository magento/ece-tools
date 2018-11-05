<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
return [
    'modules' =>
        [
            'Magento_Store' => 1,
            'Magento_Directory' => 1,
        ],
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
    'themes' => [
        'frontend/Magento/blank' => [
            'parent_id' => null,
            'theme_path' => 'Magento/blank',
            'theme_title' => 'Magento Blank',
            'is_featured' => '0',
            'area' => 'frontend',
            'type' => '0',
            'code' => 'Magento/blank',
        ],
        'frontend/Magento/luma' => [
            'parent_id' => 'Magento/blank',
            'theme_path' => 'Magento/luma',
            'theme_title' => 'Magento Luma',
            'is_featured' => '0',
            'area' => 'frontend',
            'type' => '0',
            'code' => 'Magento/luma',
        ],
        'adminhtml/Magento/backend' => [
            'parent_id' => null,
            'theme_path' => 'Magento/backend',
            'theme_title' => 'Magento 2 backend',
            'is_featured' => '0',
            'area' => 'adminhtml',
            'type' => '0',
            'code' => 'Magento/backend',
        ],
    ],
    'i18n' => [
    ],
    'system' => [
        'default' => [
            'dev' => [
                'debug' => [
                    'profiler' => '0',
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
                'image' => [
                    'default_adapter' => 'GD2',
                    'adapters' => [
                        'GD2' => [
                            'title' => 'PHP GD2',
                            'class' => 'Magento\\Framework\\Image\\Adapter\\Gd2',
                        ],
                        'IMAGEMAGICK' => [
                            'title' => 'ImageMagick',
                            'class' => 'Magento\\Framework\\Image\\Adapter\\ImageMagick',
                        ],
                    ],
                ],
                'static' => [
                    'sign' => '1',
                ],
                'template' => [
                    'minify_html' => '0',
                ],
                'grid' => [
                    'async_indexing' => '0',
                ],
                'front_end_development_workflow' => [
                    'type' => 'server_side_compilation',
                ],
                'translate_inline' => [
                    'active' => '0',
                    'active_admin' => '0',
                    'invalid_caches' =>
                        [
                            'block_html' => null,
                        ],
                ],
            ],
            'system' => [
                'filesystem' => [
                    'media' => '{{media_dir}}',
                ],
                'media_storage_configuration' => [
                    'media_storage' => '0',
                    'media_database' => 'default_setup',
                    'configuration_update_time' => '3600',
                    'allowed_resources' => [
                        'compiled_css_folder' => 'css',
                        'compiled_css_secure_folder' => 'css_secure',
                        'compiled_js_folder' => 'js',
                        'design_theme_folder' => 'theme',
                        'site_favicons' => 'favicon',
                        'site_logos' => 'logo',
                        'email_folder' => 'email',
                        'wysiwyg_image_folder' => 'wysiwyg',
                        'tmp_images_folder' => 'tmp',
                        'catalog_images_folder' => 'catalog',
                        'product_custom_options_fodler' => 'custom_options',
                        'dhl_folder' => 'dhl',
                        'captcha_folder' => 'captcha',
                        'enterprise_folder' => 'enterprise',
                        'giftwrapping_folder' => 'wrapping',
                    ],
                ],
                'emails' => [
                    'forgot_email_template' => 'system_emails_forgot_email_template',
                    'forgot_email_identity' => 'general',
                ],
                'dashboard' => [
                    'enable_charts' => '1',
                ],
                'smtp' => [
                    'disable' => '0',
                ],
                'full_page_cache' => [
                    'varnish5' => [
                        'path' => 'varnish5.vcl',
                    ],
                    'varnish4' => [
                        'path' => 'varnish4.vcl',
                    ],
                    'ttl' => '86400',
                    'caching_application' => '1',
                    'default' => [
                        'access_list' => 'localhost',
                        'backend_host' => 'localhost',
                        'backend_port' => '8080',
                        'ttl' => '86400',
                        'grace_period' => '300',
                    ],
                ],
                'rotation' => [
                    'frequency' => '1',
                    'lifetime' => '60',
                ],
                'mysqlmq' => [
                    'retry_inprogress_after' => '1440',
                    'new_messages_lifetime' => '10080',
                    'successful_messages_lifetime' => '10080',
                    'failed_messages_lifetime' => '10080',
                ],
                'magento_scheduled_import_export_log' => [
                    'save_days' => '5',
                ],
                'bulk' => [
                    'lifetime' => '60',
                ],
                'cron' => [
                    'index' => [
                        'schedule_generate_every' => '1',
                        'schedule_ahead_for' => '4',
                        'schedule_lifetime' => '2',
                        'history_cleanup_every' => '10',
                        'history_success_lifetime' => '60',
                        'history_failure_lifetime' => '600',
                        'use_separate_process' => '1',
                    ],
                    'staging' => [
                        'schedule_generate_every' => '1',
                        'schedule_ahead_for' => '4',
                        'schedule_lifetime' => '2',
                        'history_cleanup_every' => '10',
                        'history_success_lifetime' => '60',
                        'history_failure_lifetime' => '600',
                        'use_separate_process' => '1',
                    ],
                    'default' => [
                        'schedule_generate_every' => '15',
                        'schedule_ahead_for' => '20',
                        'schedule_lifetime' => '15',
                        'history_cleanup_every' => '10',
                        'history_success_lifetime' => '60',
                        'history_failure_lifetime' => '600',
                        'use_separate_process' => '0',
                    ],
                    'catalog_event' => [
                        'schedule_generate_every' => '1',
                        'schedule_ahead_for' => '4',
                        'schedule_lifetime' => '2',
                        'history_cleanup_every' => '10',
                        'history_success_lifetime' => '60',
                        'history_failure_lifetime' => '600',
                        'use_separate_process' => '1',
                    ],
                    'consumers' => [
                        'schedule_generate_every' => '15',
                        'schedule_ahead_for' => '20',
                        'schedule_lifetime' => '15',
                        'history_cleanup_every' => '10',
                        'history_success_lifetime' => '60',
                        'history_failure_lifetime' => '600',
                        'use_separate_process' => '1',
                    ],
                ],
            ],
            'web' => [
                'url' => [
                    'use_store' => '0',
                    'redirect_to_base' => '1',
                ],
                'unsecure' => [
                    'base_web_url' => '{{unsecure_base_url}}',
                ],
                'secure' => [
                    'base_web_url' => '{{secure_base_url}}',
                    'use_in_frontend' => '0',
                    'use_in_adminhtml' => '1',
                    'offloader_header' => 'X-Forwarded-Proto',
                ],
                'session' => [
                    'use_remote_addr' => '0',
                    'use_http_via' => '0',
                    'use_http_x_forwarded_for' => '0',
                    'use_http_user_agent' => '0',
                    'use_frontend_sid' => '1',
                ],
                'browser_capabilities' => [
                    'cookies' => '1',
                    'javascript' => '1',
                    'local_storage' => '0',
                ],
                'seo' => [
                    'use_rewrites' => '0',
                ],
                'default' => [
                    'cms_home_page' => 'home',
                    'cms_no_route' => 'no-route',
                    'cms_no_cookies' => 'enable-cookies',
                    'no_route' => 'cms/noroute/index',
                    'show_cms_breadcrumbs' => '1',
                ],
                'cookie' => [
                    'cookie_lifetime' => '3600',
                    'cookie_httponly' => '1',
                    'cookie_restriction' => '0',
                    'cookie_restriction_lifetime' => '31536000',
                ],
            ],
            'admin' => [
                'startup' => [
                    'menu_item_id' => 'dashboard',
                ],
                'url' => [
                    'use_custom' => '0',
                    'use_custom_path' => '0',
                ],
                'security' => [
                    'use_form_key' => '1',
                    'password_reset_link_expiration_period' => '2',
                    'lockout_failures' => '6',
                    'lockout_threshold' => '30',
                    'password_lifetime' => '90',
                    'password_is_forced' => '1',
                    'session_lifetime' => '900',
                    'admin_account_sharing' => '0',
                    'password_reset_protection_type' => '1',
                    'max_number_password_reset_requests' => '5',
                    'min_time_between_password_reset_requests' => '10',
                ],
                'emails' => [
                    'forgot_email_template' => 'admin_emails_forgot_email_template',
                    'forgot_email_identity' => 'general',
                    'user_notification_template' => 'admin_emails_user_notification_template',
                ],
                'captcha' => [
                    'type' => 'default',
                    'enable' => '1',
                    'font' => 'linlibertine',
                    'mode' => 'after_fail',
                    'forms' => 'backend_forgotpassword,backend_login',
                    'failed_attempts_login' => '3',
                    'failed_attempts_ip' => '1000',
                    'timeout' => '7',
                    'length' => '4-5',
                    'symbols' => 'ABCDEFGHJKMnpqrstuvwxyz23456789',
                    'case_sensitive' => '0',
                    'shown_to_logged_in_user' => null,
                    'always_for' =>
                        [
                            'backend_forgotpassword' => '1',
                        ],
                ],
            ],
            'general' => [
                'country' => [
                    'eu_countries' => 'AT',
                    'default' => 'US',
                ],
                'locale' => [
                    'firstday' => '0',
                    'weekend' => '0,6',
                    'datetime_format_long' => '%A, %B %e %Y [%I:%M %p]',
                    'datetime_format_medium' => '%a, %b %e %Y [%I:%M %p]',
                    'datetime_format_short' => '%m/%d/%y [%I:%M %p]',
                    'date_format_long' => '%A, %B %e %Y',
                    'date_format_medium' => '%a, %b %e %Y',
                    'date_format_short' => '%m/%d/%y',
                    'language' => 'en',
                    'code' => 'en_US',
                    'timezone' => 'America/Los_Angeles',
                    'weight_unit' => 'lbs',
                ],
                'file' =>
                    [
                        'protected_extensions' =>
                            [
                                'php' => 'php',
                                'htaccess' => 'htaccess',
                                'jsp' => 'jsp',
                                'pl' => 'pl',
                                'py' => 'py',
                                'asp' => 'asp',
                                'sh' => 'sh',
                                'cgi' => 'cgi',
                                'htm' => 'htm',
                                'html' => 'html',
                                'phtml' => 'phtml',
                                'shtml' => 'shtml',
                            ],
                        'public_files_valid_paths' =>
                            [
                                'protected' =>
                                    [
                                        'app' => '/app/*/*',
                                        'bin' => '/bin/*/*',
                                        'dev' => '/dev/*/*',
                                        'generated' => '/generated/*/*',
                                        'lib' => '/lib/*/*',
                                        'setup' => '/setup/*/*',
                                        'update' => '/update/*/*',
                                        'vendor' => '/vendor/*/*',
                                    ],
                            ],
                        'importexport_local_valid_paths' =>
                            [
                                'available' =>
                                    [
                                        'export_xml' => 'var/export/*/*.xml',
                                        'export_csv' => 'var/export/*/*.csv',
                                        'import_xml' => 'var/import/*/*.xml',
                                        'import_csv' => 'var/import/*/*.csv',
                                    ],
                            ],
                        'bunch_size' => '100',
                    ],
                'single_store_mode' =>
                    [
                        'enabled' => '0',
                    ],
                'validator_data' =>
                    [
                        'input_types' =>
                            [
                                'text' => 'text',
                                'textarea' => 'textarea',
                                'date' => 'date',
                                'boolean' => 'boolean',
                                'multiselect' => 'multiselect',
                                'select' => 'select',
                                'price' => 'price',
                                'media_image' => 'media_image',
                                'gallery' => 'gallery',
                                'weee' => 'weee',
                                'swatch_visual' => 'swatch_visual',
                                'swatch_text' => 'swatch_text',
                            ],
                    ],
                'restriction' =>
                    [
                        'cms_page' => 'service-unavialable',
                    ],
                'region' =>
                    [
                        'display_all' => '1',
                        'state_required' => 'AT,BR,CA,CH,EE,ES,FI,HR,LT,LV,RO,US',
                    ],
            ],
        ],
        'stores' => [
            'admin' => [
                'design' => [
                    'package' => [
                        'name' => 'default',
                    ],
                    'theme' => [
                        'default' => 'default',
                    ],
                ],
            ],
            'store1' => [
                'general' => [
                    'locale' => [
                        'code' => 'fr_FR'
                    ],
                ],
                'design' => [
                    'package' => [
                        'name' => 'default',
                    ],
                    'theme' => [
                        'default' => 'default',
                    ],
                ],
            ],
            'store2' => [
                'general' => [
                    'locale' => [
                        'code' => 'kz_KZ'
                    ],
                ],
                'design' => [
                    'package' => [
                        'name' => 'default',
                    ],
                    'theme' => [
                        'default' => 'default',
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
                'design' => [
                    'package' => [
                        'name' => 'default',
                    ],
                    'theme' => [
                        'default' => 'default',
                    ],
                ],
            ],
            'admin' => [
                'web' => [
                    'routers' => [
                        'frontend' => [
                            'disabled' => 'true',
                        ],
                    ],
                    'default' => [
                        'no_route' => 'admin/noroute/index',
                    ],
                ],
            ],
        ],
    ],
];
