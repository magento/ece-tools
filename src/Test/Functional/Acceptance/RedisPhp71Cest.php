<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

/**
 * @group php71
 */
class RedisPhp71Cest extends RedisCest
{
    /**
     * @return array
     */
    protected function defaultConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.2.10',
            ],
            [
                'version' => '2.2.11',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function wrongConfigurationDataProvider(): array
    {
        return [
            [
                'version' => '2.2.10',
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
            [
                'version' => '2.2.10',
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
                'version' => '2.2.10',
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
                'version' => '2.2.11',
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
        ];
    }
}
