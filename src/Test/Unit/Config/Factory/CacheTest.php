<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Factory;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Factory\Cache;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Service\Redis;
use Magento\MagentoCloud\Service\Valkey;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 * @see Cache
 */
#[AllowMockObjectsWithoutExpectations]
class CacheTest extends TestCase
{
    /**
     * @var Redis|MockObject
     */
    private $redisMock;

    /**
     * @var Valkey|MockObject
     */
    private $valkeyMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Cache
     */
    private Cache $config;

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(Redis::class);
        $this->valkeyMock = $this->createMock(Valkey::class);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->config = new Cache(
            $this->redisMock,
            $this->valkeyMock,
            $this->stageConfigMock,
            $this->loggerMock,
            new ConfigMerger()
        );
    }

    /**
     * Test that the config is empty when no cache configuration is set.
     *
     * @return void
     * @throws ConfigException
     */
    public function testGetWithValidEnvConfig(): void
    {
        $this->stageConfigMock->expects(self::exactly(5))
            ->method('get')
            ->willReturnMap(
                [
                    [
                        DeployInterface::VAR_CACHE_CONFIGURATION,
                        ['frontend' => ['cache_option' => 'value']],
                    ],
                    [
                        DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                        false,
                    ],
                    [DeployInterface::VAR_CACHE_VALKEY_BACKEND, null],
                    [DeployInterface::VAR_CACHE_REDIS_BACKEND, null],
                    [DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION, null],
                ]
            );
        $this->redisMock->expects(self::never())
            ->method('getConfiguration');
        $this->valkeyMock->expects(self::never())
            ->method('getConfiguration');

        $this->loggerMock->expects(self::never())
            ->method('notice');

        self::assertEquals(
            ['frontend' => ['cache_option' => 'value']],
            $this->config->get()
        );
    }

    /**
     * Test that notice is logged when REDIS_USE_SLAVE_CONNECTION is set
     * but CACHE_CONFIGURATION is also set.
     *
     * @return void
     * @throws ConfigException
     */
    public function testGetWithValidEnvConfigWithEnabledRedisSlave(): void
    {
        $this->stageConfigMock->expects(self::exactly(4))
            ->method('get')
            ->willReturnMap(
                [
                    [
                        DeployInterface::VAR_CACHE_CONFIGURATION,
                        ['frontend' => ['cache_option' => 'value']],
                    ],
                    [
                        DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                        true,
                    ],
                    [DeployInterface::VAR_CACHE_VALKEY_BACKEND, null],
                    [DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION, null],
                ]
            );
        $this->redisMock->expects(self::never())
            ->method('getConfiguration');
        $this->valkeyMock->expects(self::never())
            ->method('getConfiguration');

        $this->loggerMock->expects(self::once())
            ->method('notice')
            ->with(
                'The variables \'' . DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION . '\', \''
                    . DeployInterface::VAR_CACHE_REDIS_BACKEND . '\' are ignored'
                    . ' as you set your own cache connection in \'' . DeployInterface::VAR_CACHE_CONFIGURATION . '\''
            );

        self::assertEquals(
            ['frontend' => ['cache_option' => 'value']],
            $this->config->get()
        );
    }

    /**
     * Test that the config is empty when no cache configuration is set.
     *
     * @return void
     * @throws ConfigException
     */
    public function testGetWithoutRedisAndWithNotValidEnvConfig(): void
    {
        $this->stageConfigMock->expects(self::exactly(3))
            ->method('get')
            ->willReturnCallback(
                fn($param) => match ([$param]) {
                    [DeployInterface::VAR_CACHE_CONFIGURATION] => [],
                    [DeployInterface::VAR_CACHE_REDIS_BACKEND] => '',
                    [DeployInterface::VAR_CACHE_VALKEY_BACKEND] => '',
                }
            );
        $this->redisMock->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([]);

        self::assertEmpty($this->config->get());
    }

    /**
     * Test get from relationships method.
     *
     * @param array   $envCacheConfig
     * @param array   $masterConnection
     * @param array   $slaveConnection
     * @param boolean $useSlave
     * @param string  $backendModel
     * @param int     $callingGetStageConfig
     * @param array   $expectedResult
     * @return void
     * @dataProvider getFromRelationshipsDataProvider
     * @throws       ConfigException
     */
    #[DataProvider('getFromRelationshipsDataProvider')]
    public function testGetFromRelationships(
        $envCacheConfig,
        $masterConnection,
        $slaveConnection,
        $useSlave,
        $backendModel,
        $callingGetStageConfig,
        $expectedResult
    ) {
        $this->stageConfigMock->expects(self::any())
            ->method('get')
            ->willReturnMap(
                [
                    [DeployInterface::VAR_CACHE_CONFIGURATION, $envCacheConfig],
                    [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, $useSlave],
                    [DeployInterface::VAR_CACHE_REDIS_BACKEND, $backendModel],
                    [DeployInterface::VAR_CACHE_VALKEY_BACKEND, null],
                    [DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION, null],
                ]
            );
        $this->redisMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($masterConnection);
        $this->redisMock->expects(self::any())
            ->method('getSlaveConfiguration')
            ->willReturn($slaveConnection);

        self::assertEquals(
            $expectedResult,
            $this->config->get()
        );
    }

    /**
     * Data provider for testGetFromRelationships.
     *
     * Results value for next data:
     * 1 - cache configuration from CACHE_CONFIGURATION variable
     * 2 - data for 'redis' relationships
     * 3 - data for 'redis-slave' relationships
     * 4 - value for REDIS_USE_SLAVE_CONNECTION variable
     * 5 - expected result
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function getFromRelationshipsDataProvider(): array
    {
        $redisConfiguration = [
            'host' => 'master.host',
            'port' => 'master.port',
            'password' => 'master.password',
            'scheme' => 'redis',
        ];
        $redisSlaveConfiguration = [
            'host' => 'slave.host',
            'port' => 'slave.port',
            'password' => 'slave.password',
            'scheme' => 'redis',
        ];

        $resultMasterOnlyConnection = [
            'frontend' => [
                'default' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => 'master.host',
                        'port' => 'master.port',
                        'password' => 'master.password',
                        'database' => Cache::CACHE_DATABASE_DEFAULT,
                    ],
                ],
                'page_cache' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => 'master.host',
                        'password' => 'master.password',
                        'port' => 'master.port',
                        'database' => Cache::CACHE_DATABASE_PAGE_CACHE,
                    ],
                ],
            ],
        ];
        $resultMasterOnlyConnectionRedisCache = $resultMasterOnlyConnection;
        $resultMasterOnlyConnectionRedisCache['frontend']['default']['backend'] = Cache::REDIS_BACKEND_REDIS_CACHE;
        $resultMasterOnlyConnectionRedisCache['frontend']['page_cache']['backend'] = Cache::REDIS_BACKEND_REDIS_CACHE;
        $resultMasterOnlyConnectionSyncCache = [
            'frontend' => [
                'default' => [
                    'backend' => Cache::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                    'backend_options' => [
                        'remote_backend' => Cache::REDIS_BACKEND_REDIS_CACHE,
                        'remote_backend_options' => [
                            'server' => 'master.host',
                            'port' => 'master.port',
                            'database' => Cache::CACHE_DATABASE_DEFAULT,
                            'persistent' => 0,
                            'password' => 'master.password',
                            'compress_data' => '1',
                        ],
                        'local_backend' => 'Cm_Cache_Backend_File',
                        'local_backend_options' => [
                            'cache_dir' => '/dev/shm/',
                        ],
                    ],
                    'frontend_options' => [
                        'write_control' => false,
                    ],
                ],
            ],
            'type' => [
                'default' => ['frontend' => 'default'],
            ],
        ];

        $backendOptions = [
            'load_from_slave' => [
                'server' => 'slave.host',
                'port' => 'slave.port',
                'password' => 'slave.password'
            ],
            'read_timeout' => 1,
            'retry_reads_on_master' => 1,
        ];

        $slaveConfiguration = [
            'backend_options' => $backendOptions,
            'frontend_options' => [
                'write_control' => false,
            ],
        ];

        $slaveConfigurationSyncCache = [
            'backend_options' => [
                'remote_backend_options' => $backendOptions,
            ],
        ];

        $resultMasterSlaveConnection = $resultMasterOnlyConnection;
        $resultMasterSlaveConnection['frontend']['default'] = array_merge_recursive(
            $resultMasterSlaveConnection['frontend']['default'],
            $slaveConfiguration
        );
        $resultMasterSlaveConnection['frontend']['page_cache'] = array_merge_recursive(
            $resultMasterSlaveConnection['frontend']['page_cache'],
            $slaveConfiguration
        );
        $resultMasterSlaveConnectionRedisCache = $resultMasterSlaveConnection;
        $resultMasterSlaveConnectionRedisCache['frontend']['default']['backend'] = Cache::REDIS_BACKEND_REDIS_CACHE;
        $resultMasterSlaveConnectionRedisCache['frontend']['page_cache']['backend'] = Cache::REDIS_BACKEND_REDIS_CACHE;
        $resultMasterSlaveConnectionSyncCache = $resultMasterOnlyConnectionSyncCache;
        $resultMasterSlaveConnectionSyncCache['frontend']['default'] = array_merge_recursive(
            $resultMasterSlaveConnectionSyncCache['frontend']['default'],
            $slaveConfigurationSyncCache
        );

        $resultMasterSlaveConnectionWithMergedValue = $resultMasterSlaveConnection;
        $resultMasterSlaveConnectionWithMergedValue['frontend']['default']['backend_options']['value'] = 'key';
        $resultMasterSlaveConnectionWithMergedValueRedisCache = $resultMasterSlaveConnectionWithMergedValue;
        $resultMasterSlaveConnectionWithMergedValueRedisCache['frontend']['default']['backend'] =
            Cache::REDIS_BACKEND_REDIS_CACHE;
        $resultMasterSlaveConnectionWithMergedValueRedisCache['frontend']['page_cache']['backend'] =
            Cache::REDIS_BACKEND_REDIS_CACHE;
        $resultMasterSlaveConnectionWithMergedValueSyncCache = $resultMasterSlaveConnectionSyncCache;
        $resultMasterSlaveConnectionWithMergedValueSyncCache['frontend']['default']['backend_options']['value'] = 'key';

        $resultMasterSlaveConnectionWithDiffHost = $resultMasterOnlyConnection;
        $resultMasterSlaveConnectionWithDiffHost['frontend']['default']['backend_options']['value'] = 'key';
        $resultMasterSlaveConnectionWithDiffHost['frontend']['default']['backend_options']['server'] = 'new.host';
        $resultMasterSlaveConnectionWithDiffHostRedisCache = $resultMasterSlaveConnectionWithDiffHost;
        $resultMasterSlaveConnectionWithDiffHostRedisCache['frontend']['default']['backend'] =
            Cache::REDIS_BACKEND_REDIS_CACHE;
        $resultMasterSlaveConnectionWithDiffHostRedisCache['frontend']['page_cache']['backend'] =
            Cache::REDIS_BACKEND_REDIS_CACHE;
        $resultMasterSlaveConnectionWithDiffHostSyncCache = $resultMasterOnlyConnectionSyncCache;
        $remoteBackendOptionsDiffHostSync = [
            'frontend' => [
                'default' => [
                    'backend_options' => [
                        'remote_backend_options' => [
                            'value' => 'key',
                            'server' => 'new.host',
                        ],
                    ],
                ],
            ],
        ];
        $resultMasterSlaveConnectionWithDiffHostSyncCache = array_replace_recursive(
            $resultMasterSlaveConnectionWithDiffHostSyncCache,
            $remoteBackendOptionsDiffHostSync
        );

        return [
            [
                [],
                $redisConfiguration,
                [],
                false,
                Cache::REDIS_BACKEND_CM_CACHE,
                6,
                $resultMasterOnlyConnection,
            ],
            [
                [],
                $redisConfiguration,
                $redisSlaveConfiguration,
                false,
                Cache::REDIS_BACKEND_CM_CACHE,
                6,
                $resultMasterOnlyConnection,
            ],
            [
                [],
                $redisConfiguration,
                [],
                true,
                Cache::REDIS_BACKEND_CM_CACHE,
                6,
                $resultMasterOnlyConnection,
            ],
            [
                [],
                $redisConfiguration,
                $redisSlaveConfiguration,
                true,
                Cache::REDIS_BACKEND_CM_CACHE,
                7,
                $resultMasterSlaveConnection,
            ],
            [
                [
                    'frontend' => [
                        'default' => [
                            'backend_options' => [
                                'value' => 'key',
                            ],
                        ],
                    ],
                    StageConfigInterface::OPTION_MERGE => true,
                ],
                $redisConfiguration,
                $redisSlaveConfiguration,
                true,
                Cache::REDIS_BACKEND_CM_CACHE,
                7,
                $resultMasterSlaveConnectionWithMergedValue,
            ],
            [
                [
                    'frontend' => [
                        'default' => [
                            'backend_options' => [
                                'server' => 'new.host',
                                'value' => 'key',
                            ],
                        ],
                    ],
                    StageConfigInterface::OPTION_MERGE => true,
                ],
                $redisConfiguration,
                $redisSlaveConfiguration,
                true,
                Cache::REDIS_BACKEND_CM_CACHE,
                7,
                $resultMasterSlaveConnectionWithDiffHost,
            ],
            [
                [],
                $redisConfiguration,
                [],
                false,
                Cache::REDIS_BACKEND_REDIS_CACHE,
                6,
                $resultMasterOnlyConnectionRedisCache,
            ],
            [
                [],
                $redisConfiguration,
                $redisSlaveConfiguration,
                false,
                Cache::REDIS_BACKEND_REDIS_CACHE,
                6,
                $resultMasterOnlyConnectionRedisCache,
            ],
            [
                [],
                $redisConfiguration,
                [],
                true,
                Cache::REDIS_BACKEND_REDIS_CACHE,
                6,
                $resultMasterOnlyConnectionRedisCache,
            ],
            [
                [],
                $redisConfiguration,
                $redisSlaveConfiguration,
                true,
                Cache::REDIS_BACKEND_REDIS_CACHE,
                7,
                $resultMasterSlaveConnectionRedisCache,
            ],
            [
                [
                    'frontend' => [
                        'default' => [
                            'backend_options' => [
                                'value' => 'key',
                            ],
                        ],
                    ],
                    StageConfigInterface::OPTION_MERGE => true,
                ],
                $redisConfiguration,
                $redisSlaveConfiguration,
                true,
                Cache::REDIS_BACKEND_REDIS_CACHE,
                7,
                $resultMasterSlaveConnectionWithMergedValueRedisCache,
            ],
            [
                [
                    'frontend' => [
                        'default' => [
                            'backend_options' => [
                                'server' => 'new.host',
                                'value' => 'key',
                            ],
                        ],
                    ],
                    StageConfigInterface::OPTION_MERGE => true,
                ],
                $redisConfiguration,
                $redisSlaveConfiguration,
                true,
                Cache::REDIS_BACKEND_REDIS_CACHE,
                7,
                $resultMasterSlaveConnectionWithDiffHostRedisCache,
            ],
            [
                [],
                $redisConfiguration,
                [],
                false,
                Cache::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                6,
                $resultMasterOnlyConnectionSyncCache,
            ],
            [
                [],
                $redisConfiguration,
                $redisSlaveConfiguration,
                false,
                Cache::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                6,
                $resultMasterOnlyConnectionSyncCache,
            ],
            [
                [],
                $redisConfiguration,
                [],
                true,
                Cache::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                6,
                $resultMasterOnlyConnectionSyncCache,
            ],
            [
                [],
                $redisConfiguration,
                $redisSlaveConfiguration,
                true,
                Cache::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                7,
                $resultMasterSlaveConnectionSyncCache,
            ],
            [
                [
                    'frontend' => [
                        'default' => [
                            'backend_options' => [
                                'value' => 'key',
                            ],
                        ],
                    ],
                    StageConfigInterface::OPTION_MERGE => true,
                ],
                $redisConfiguration,
                $redisSlaveConfiguration,
                true,
                Cache::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                7,
                $resultMasterSlaveConnectionWithMergedValueSyncCache,
            ],
            [
                [
                    'frontend' => [
                        'default' => [
                            'backend_options' => [
                                'remote_backend_options' => [
                                    'server' => 'new.host',
                                    'value' => 'key',
                                ],
                            ],
                        ],
                    ],
                    StageConfigInterface::OPTION_MERGE => true,
                ],
                $redisConfiguration,
                $redisSlaveConfiguration,
                true,
                Cache::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                7,
                $resultMasterSlaveConnectionWithDiffHostSyncCache,
            ],
        ];
    }

    /**
     * Test env configuration merging method.
     *
     * @param  array $envCacheConfiguration
     * @param  array $redisConfiguration
     * @param  array $expected
     * @return void
     * @dataProvider envConfigurationMergingDataProvider
     * @throws ConfigException
     */
    #[DataProvider('envConfigurationMergingDataProvider')]
    public function testEnvConfigurationMerging(
        array $envCacheConfiguration,
        array $redisConfiguration,
        array $expected
    ): void {
        $this->stageConfigMock
            ->method('get')
            ->willReturnMap(
                [
                    [
                        DeployInterface::VAR_CACHE_CONFIGURATION,
                        $envCacheConfiguration,
                    ],
                    [
                        DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION,
                        false,
                    ],
                    [
                        DeployInterface::VAR_CACHE_REDIS_BACKEND,
                        'Cm_Cache_Backend_Redis',
                    ],
                ]
            );
        $this->redisMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($redisConfiguration);
        $this->redisMock->expects(self::any())
            ->method('getSlaveConfiguration')
            ->willReturn([]);

        self::assertEquals(
            $expected,
            $this->config->get()
        );
    }

    /**
     * Data provider for envConfigurationMerging method.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function envConfigurationMergingDataProvider(): array
    {
        $redisConfiguration = [
            'host' => 'master.host',
            'port' => 'master.port',
            'password' => 'master.password',
            'scheme' => 'redis',
        ];

        $result = [
            'frontend' => [
                'default' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => 'master.host',
                        'port' => 'master.port',
                        'password' => 'master.password',
                        'database' => Cache::CACHE_DATABASE_DEFAULT,
                    ],
                ],
                'page_cache' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => [
                        'server' => 'master.host',
                        'port' => 'master.port',
                        'password' => 'master.password',
                        'database' => Cache::CACHE_DATABASE_PAGE_CACHE,
                    ],
                ],
            ],
        ];

        $resultWithMergedKey = $result;
        $resultWithMergedKey['key'] = 'value';

        $resultWithMergedHostAndPort = $result;
        $resultWithMergedHostAndPort['frontend']['default']['backend_options']['server'] = 'merged.server';
        $resultWithMergedHostAndPort['frontend']['default']['backend_options']['port'] = 'merged.port';
        $resultWithMergedHostAndPort['frontend']['default']['backend_options']['database'] = '10';

        return [
            [
                [],
                $redisConfiguration,
                $result,
            ],
            [
                [StageConfigInterface::OPTION_MERGE => true],
                $redisConfiguration,
                $result,
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'key' => 'value',
                ],
                $redisConfiguration,
                $resultWithMergedKey,
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'frontend' => [
                        'default' => [
                            'backend_options' => [
                                'server' => 'merged.server',
                                'port' => 'merged.port',
                                'database' => 10,
                            ],
                        ],
                    ],
                ],
                $redisConfiguration,
                $resultWithMergedHostAndPort,
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => false,
                    'frontend' => [
                        'default' => [
                            'backend_options' => [
                                'server' => 'merged.server',
                                'port' => 'merged.port',
                                'database' => 10,
                            ],
                        ],
                    ],
                ],
                $redisConfiguration,
                [
                    'frontend' => [
                        'default' => [
                            'backend_options' => [
                                'server' => 'merged.server',
                                'port' => 'merged.port',
                                'database' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
