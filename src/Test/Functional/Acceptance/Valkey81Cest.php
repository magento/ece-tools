<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * Checks Valkey configuration
 *
 * @group php81
 */
class Valkey81Cest extends ValkeyCest
{
    /**
     * @return array
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.4.5',
            ],
            [
                'version' => '2.4.6',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function wrongConfigurationValkeyBackendDataProvider(): array
    {
        return [
            [
                'version' => '2.4.5',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => 'TestValkeyModel'
                        ]
                    ]
                ],
                'buildSuccess' => false,
                'deploySuccess' => false,
                'errorBuildMessage' => 'The VALKEY_BACKEND variable contains an invalid value TestValkeyModel.'
                    . ' Use one of the available value options: Cm_Cache_Backend_Redis,'
                    . ' \Magento\Framework\Cache\Backend\Redis,'
                    . ' \Magento\Framework\Cache\Backend\RemoteSynchronizedCache.',
                'errorDeployMessage' => '',
            ],
            [
                'version' => '2.4.6',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => 'TestValkeyModel'
                        ]
                    ]
                ],
                'buildSuccess' => false,
                'deploySuccess' => false,
                'errorBuildMessage' => 'The VALKEY_BACKEND variable contains an invalid value TestValkeyModel.'
                    . ' Use one of the available value options: Cm_Cache_Backend_Redis,'
                    . ' \Magento\Framework\Cache\Backend\Redis,'
                    . ' \Magento\Framework\Cache\Backend\RemoteSynchronizedCache.',
                'errorDeployMessage' => '',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function valkeyWrongConnectionDataProvider(): array
    {
        return [
            [
                'version' => '2.4.5',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        'backend' => '\Magento\Framework\Cache\Backend\Redis',
                                        'backend_options' => [
                                            'port' => 9999,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'version' => '2.4.5',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        '_custom_valkey_backend' => true,
                                        'backend' => '\CustomValkeyModel',
                                        'backend_options' => [
                                            'port' => 9999,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'version' => '2.4.6',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        'backend' => '\Magento\Framework\Cache\Backend\Redis',
                                        'backend_options' => [
                                            'port' => 9999,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'version' => '2.4.6',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        '_custom_valkey_backend' => true,
                                        'backend' => '\CustomValkeyModel',
                                        'backend_options' => [
                                            'port' => 9999,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return                                        array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function goodConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.4.5',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => '\Magento\Framework\Cache\Backend\Redis',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\Redis',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'cache',
                        'port' => '6379',
                        'database' => 1,
                    ]
                ],
            ],
            [
                'version' => '2.4.5',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        'backend' => '\CustomValkeyModel',
                                        'backend_options' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedBackend' => '\CustomValkeyModel',
                'expectedConfig' => [],
            ],
            [
                'version' => '2.4.5',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                'expectedConfig' => [
                    'backend_options' => [
                        'remote_backend' => '\Magento\Framework\Cache\Backend\Redis',
                        'remote_backend_options' => [
                            'persistent' => 0,
                            'server' => 'cache',
                            'database' => 1,
                            'port' => '6379',
                            'password' => '',
                            'compress_data' => '1',
                        ],
                        'local_backend' => 'Cm_Cache_Backend_File',
                        'local_backend_options' => [
                            'cache_dir' => '/dev/shm/',
                        ],
                    ],
                ],
            ],
            [
                'version' => '2.4.6',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => '\Magento\Framework\Cache\Backend\Redis',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\Redis',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'cache',
                        'port' => '6379',
                        'database' => 1,
                    ]
                ],
            ],
            [
                'version' => '2.4.6',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        'backend' => '\CustomValkeyModel',
                                        'backend_options' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedBackend' => '\CustomValkeyModel',
                'expectedConfig' => [],
            ],
            [
                'version' => '2.4.6',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'VALKEY_BACKEND' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                'expectedConfig' => [
                    'backend_options' => [
                        'remote_backend' => '\Magento\Framework\Cache\Backend\Redis',
                        'remote_backend_options' => [
                            'persistent' => 0,
                            'server' => 'cache',
                            'database' => 1,
                            'port' => '6379',
                            'password' => '',
                            'compress_data' => '1',
                        ],
                        'local_backend' => 'Cm_Cache_Backend_File',
                        'local_backend_options' => [
                            'cache_dir' => '/dev/shm/',
                        ],
                    ],
                ],
            ],
        ];
    }
}
