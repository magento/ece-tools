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
 * @group php74
 */
class Redis74Cest extends RedisCest
{
    /**
     * @return array
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.4.3',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function wrongConfigurationRedisBackendDataProvider(): array
    {
        return [
            [
                'version' => '2.4.3',
                'wrongConfiguration' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => 'TestRedisModel'
                        ]
                    ]
                ],
                'buildSuccess' => false,
                'deploySuccess' => false,
                'errorBuildMessage' => 'The REDIS_BACKEND variable contains an invalid value TestRedisModel.'
                    . ' Use one of the available value options: Cm_Cache_Backend_Redis,'
                    . ' \Magento\Framework\Cache\Backend\Redis,'
                    . ' \Magento\Framework\Cache\Backend\RemoteSynchronizedCache.',
                'errorDeployMessage' => '',
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
                'version' => '2.4.3',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => '\Magento\Framework\Cache\Backend\Redis',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\Redis',
                'expectedConfig' => [
                    'backend_options' => [
                        'server' => 'redis',
                        'port' => '6379',
                        'database' => 1,
                    ]
                ],
            ],
            [
                'version' => '2.4.3',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'CACHE_CONFIGURATION' => [
                                '_merge' => true,
                                'frontend' => [
                                    'default' => [
                                        'backend' => '\CustomRedisModel',
                                        'backend_options' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'expectedBackend' => '\CustomRedisModel',
                'expectedConfig' => [],
            ],
            [
                'version' => '2.4.3',
                'configuration' => [
                    'stage' => [
                        'deploy' => [
                            'REDIS_BACKEND' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                        ],
                    ],
                ],
                'expectedBackend' => '\Magento\Framework\Cache\Backend\RemoteSynchronizedCache',
                'expectedConfig' => [
                    'backend_options' => [
                        'remote_backend' => '\Magento\Framework\Cache\Backend\Redis',
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
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @skip
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testRedisWrongConnection(\CliTester $I, \Codeception\Example $data): void
    {
        return;
    }

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @skip
     */
    public function testWrongConfigurationRedisBackend(\CliTester $I, \Codeception\Example $data): void
    {
        return;
    }
}
