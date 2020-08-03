<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * Checks Redis configuration
 *
 * @group php73
 */
class Redis73Cest extends RedisCest
{
    /**
     * @return array
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.3.4',
            ],
            [
                'version' => '2.3.5',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function wrongConfigurationData(): array
    {
        return [
            [
                'version' => '2.3.4',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => '\Magento\Framework\Cache\Backend\Redis'
                        ]
                    ]
                ],
                'buildSuccess' => true,
                'deploySuccess' => false,
                'errorBuildMessage' => '',
                'errorDeployMessage' => 'does not support Redis backend model '
                    . '\'\Magento\Framework\Cache\Backend\Redis\'',
            ],
            [
                'version' => '2.3.4',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache'
                        ]
                    ]
                ],
                'buildSuccess' => true,
                'deploySuccess' => false,
                'errorBuildMessage' => '',
                'errorDeployMessage' => 'does not support Redis backend model '
                    . '\'\Magento\Framework\Cache\Backend\RemoteSynchronizedCache\'',
            ],
        ];
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function goodConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.3.4',
                'backendModel' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => 'Cm_Cache_Backend_Redis',
                        ],
                    ],
                ],
                'expectedBackend' => 'Cm_Cache_Backend_Redis',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'redis',
                        'port' => '6379',
                        'database' => 1,
                    ],
                ],
            ],
            [
                'version' => '2.3.5',
                'backendModel' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => '\Magento\Framework\Cache\Backend\Redis',
                        ],
                    ],
                ],
                'expectedBackend' => '\\\Magento\\\Framework\\\Cache\\\Backend\\\Redis',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'redis',
                        'port' => '6379',
                        'database' => 1,
                    ],
                ],
            ],
            [
                'version' => '2.3.5',
                'backendModel' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                        ],
                    ],
                ],
                'expectedBackend' => '\\\Magento\\\Framework\\\Cache\\\Backend\\\RemoteSynchronizedCache',
                'expectedConfig' => [
                    'backend_options' => [
                        'remote_backend' => '\\\Magento\\\Framework\\\Cache\\\Backend\\\Redis',
                        'remote_backend_options' => [
                            'persistent' => 0,
                            'server' => 'redis',
                            'database' => 1,
                            'port' => '6379',
                            'password' => '',
                            'compress_data' => '1',
                        ],
                        'local_backend' => 'Cm_Cache_Backend_File',
                        'local_backend_options' => [
                            'cache_dir' => '/dev/shm/',
                        ]
                    ]
                ],
            ],
        ];
    }
}
