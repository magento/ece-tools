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
     * Test symfony_l2 config generation with Valkey backend.
     *
     * Note: 'scheme' is 'redis' here on purpose - the relationship's scheme reflects the wire
     * protocol (Valkey is Redis-protocol-compatible), not the actual service, so remote_backend
     * must be resolved from which service config is non-empty, not from 'scheme'.
     *
     * @return void
     * @throws ConfigException
     */
    public function testGetSymfonyL2WithValkey(): void
    {
        $valkeyConfig = [
            'host'   => 'valkey.host',
            'port'   => '6379',
            'scheme' => 'redis',
        ];

        $this->stageConfigMock->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CACHE_CONFIGURATION, []],
                [DeployInterface::VAR_CACHE_REDIS_BACKEND, ''],
                [DeployInterface::VAR_CACHE_VALKEY_BACKEND, Cache::VALKEY_BACKEND_SYMFONY_L2],
                [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, false],
                [DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION, false],
            ]);

        $this->redisMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([]);
        $this->valkeyMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($valkeyConfig);
        $this->loggerMock->expects(self::never())
            ->method('notice');

        $remoteOptions = [
            'server'          => 'valkey.host',
            'port'            => '6379',
            'database'        => Cache::CACHE_DATABASE_DEFAULT,
            'compression_lib' => 'gzip',
        ];

        $expected = [
            'frontend' => [
                'default' => [
                    'backend' => Cache::VALKEY_BACKEND_SYMFONY_L2,
                    'backend_options' => [
                        'remote_backend'         => 'valkey',
                        'remote_backend_options' => array_merge(
                            $remoteOptions,
                            ['persistent_id' => 'magento_l2_default']
                        ),
                        'local_backend'          => 'file',
                        'local_backend_options'  => ['cache_dir' => '/dev/shm/magento_l1'],
                    ],
                ],
                'stale_cache_enabled' => [
                    'backend' => Cache::VALKEY_BACKEND_SYMFONY_L2,
                    'backend_options' => [
                        'remote_backend'         => 'valkey',
                        'remote_backend_options' => array_merge(
                            $remoteOptions,
                            ['persistent_id' => 'magento_l2_stale']
                        ),
                        'local_backend'          => 'file',
                        'local_backend_options'  => ['cache_dir' => '/dev/shm/magento_l1_stale'],
                        'use_stale_cache'        => true,
                    ],
                ],
            ],
            'type' => [
                'default'                => ['frontend' => 'default'],
                'layout'                 => ['frontend' => 'stale_cache_enabled'],
                'block_html'             => ['frontend' => 'stale_cache_enabled'],
                'reflection'             => ['frontend' => 'stale_cache_enabled'],
                'config_integration'     => ['frontend' => 'stale_cache_enabled'],
                'config_integration_api' => ['frontend' => 'stale_cache_enabled'],
                'full_page'              => ['frontend' => 'stale_cache_enabled'],
                'translate'              => ['frontend' => 'stale_cache_enabled'],
            ],
        ];

        self::assertEquals($expected, $this->config->get());
    }

    /**
     * Test symfony_l2 config generation with Redis backend.
     *
     * @return void
     * @throws ConfigException
     */
    public function testGetSymfonyL2WithRedis(): void
    {
        $redisConfig = [
            'host'   => 'redis.host',
            'port'   => '6379',
            'scheme' => 'redis',
        ];

        $this->stageConfigMock->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CACHE_CONFIGURATION, []],
                [DeployInterface::VAR_CACHE_REDIS_BACKEND, Cache::REDIS_BACKEND_SYMFONY_L2],
                [DeployInterface::VAR_CACHE_VALKEY_BACKEND, ''],
                [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, false],
                [DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION, false],
            ]);

        $this->redisMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($redisConfig);
        $this->valkeyMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([]);
        $this->loggerMock->expects(self::never())
            ->method('notice');

        $remoteOptions = [
            'server'          => 'redis.host',
            'port'            => '6379',
            'database'        => Cache::CACHE_DATABASE_DEFAULT,
            'compression_lib' => 'gzip',
        ];

        $expected = [
            'frontend' => [
                'default' => [
                    'backend' => Cache::REDIS_BACKEND_SYMFONY_L2,
                    'backend_options' => [
                        'remote_backend'         => 'redis',
                        'remote_backend_options' => array_merge(
                            $remoteOptions,
                            ['persistent_id' => 'magento_l2_default']
                        ),
                        'local_backend'          => 'file',
                        'local_backend_options'  => ['cache_dir' => '/dev/shm/magento_l1'],
                    ],
                ],
                'stale_cache_enabled' => [
                    'backend' => Cache::REDIS_BACKEND_SYMFONY_L2,
                    'backend_options' => [
                        'remote_backend'         => 'redis',
                        'remote_backend_options' => array_merge(
                            $remoteOptions,
                            ['persistent_id' => 'magento_l2_stale']
                        ),
                        'local_backend'          => 'file',
                        'local_backend_options'  => ['cache_dir' => '/dev/shm/magento_l1_stale'],
                        'use_stale_cache'        => true,
                    ],
                ],
            ],
            'type' => [
                'default'                => ['frontend' => 'default'],
                'layout'                 => ['frontend' => 'stale_cache_enabled'],
                'block_html'             => ['frontend' => 'stale_cache_enabled'],
                'reflection'             => ['frontend' => 'stale_cache_enabled'],
                'config_integration'     => ['frontend' => 'stale_cache_enabled'],
                'config_integration_api' => ['frontend' => 'stale_cache_enabled'],
                'full_page'              => ['frontend' => 'stale_cache_enabled'],
                'translate'              => ['frontend' => 'stale_cache_enabled'],
            ],
        ];

        self::assertEquals($expected, $this->config->get());
    }

    /**
     * Magento core added read-replica support to the Symfony Redis adapter for both single-tier and
     * symfony_l2 (no CACHE_CONFIGURATION shape change - 'load_from_slave' nests inside
     * remote_backend_options same as it already does for RemoteSynchronizedCache), so
     * REDIS_USE_SLAVE_CONNECTION must be wired into both symfony_l2 frontends.
     *
     * @return void
     * @throws ConfigException
     */
    public function testGetSymfonyL2WithRedisAppliesSlaveConnection(): void
    {
        $redisConfig = [
            'host'   => 'redis.host',
            'port'   => '6379',
            'scheme' => 'redis',
        ];
        $redisSlaveConfig = [
            'host' => 'redis-slave.host',
            'port' => '6380',
        ];

        $this->stageConfigMock->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CACHE_CONFIGURATION, []],
                [DeployInterface::VAR_CACHE_REDIS_BACKEND, Cache::REDIS_BACKEND_SYMFONY_L2],
                [DeployInterface::VAR_CACHE_VALKEY_BACKEND, ''],
                [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, true],
                [DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION, false],
            ]);

        $this->redisMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($redisConfig);
        $this->redisMock->expects(self::any())
            ->method('getSlaveConfiguration')
            ->willReturn($redisSlaveConfig);
        $this->valkeyMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with('Set Redis slave connection');
        $this->loggerMock->expects(self::never())
            ->method('notice');

        $result = $this->config->get();

        foreach (['default', 'stale_cache_enabled'] as $frontendName) {
            $remoteOptions = $result['frontend'][$frontendName]['backend_options']['remote_backend_options'];
            self::assertSame(
                ['server' => 'redis-slave.host', 'port' => '6380'],
                $remoteOptions['load_from_slave']
            );
            self::assertSame(1, $remoteOptions['read_timeout']);
            self::assertSame(1, $remoteOptions['retry_reads_on_master']);
        }
    }

    /**
     * Same as above, for Valkey.
     *
     * @return void
     * @throws ConfigException
     */
    public function testGetSymfonyL2WithValkeyAppliesSlaveConnection(): void
    {
        $valkeyConfig = [
            'host'   => 'valkey.host',
            'port'   => '6379',
            'scheme' => 'redis',
        ];
        $valkeySlaveConfig = [
            'host' => 'valkey-slave.host',
            'port' => '6380',
        ];

        $this->stageConfigMock->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CACHE_CONFIGURATION, []],
                [DeployInterface::VAR_CACHE_REDIS_BACKEND, ''],
                [DeployInterface::VAR_CACHE_VALKEY_BACKEND, Cache::VALKEY_BACKEND_SYMFONY_L2],
                [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, false],
                [DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION, true],
            ]);

        $this->redisMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([]);
        $this->valkeyMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($valkeyConfig);
        $this->valkeyMock->expects(self::any())
            ->method('getSlaveConfiguration')
            ->willReturn($valkeySlaveConfig);

        $this->loggerMock->expects(self::once())
            ->method('info')
            ->with('Set Valkey slave connection');
        $this->loggerMock->expects(self::never())
            ->method('notice');

        $result = $this->config->get();

        foreach (['default', 'stale_cache_enabled'] as $frontendName) {
            $remoteOptions = $result['frontend'][$frontendName]['backend_options']['remote_backend_options'];
            self::assertSame(
                ['server' => 'valkey-slave.host', 'port' => '6380'],
                $remoteOptions['load_from_slave']
            );
            self::assertSame(1, $remoteOptions['read_timeout']);
            self::assertSame(1, $remoteOptions['retry_reads_on_master']);
        }
    }

    /**
     * When the merchant has overridden the symfony_l2 remote connection details in CACHE_CONFIGURATION
     * to point somewhere other than the relationship's host/port, the slave connection must not be
     * force-applied (it would point at a replica of the WRONG master) - mirrors the existing
     * RemoteSynchronizedCache/legacy incompatibility notice.
     *
     * The override here only touches the 'default' frontend, so 'stale_cache_enabled' must still get
     * the slave connection - compatibility is evaluated per frontend, so one incompatible frontend
     * does not disable slave routing for the other.
     *
     * @return void
     * @throws ConfigException
     */
    public function testGetSymfonyL2WithRedisSkipsSlaveConnectionWhenOverrideIncompatible(): void
    {
        $redisConfig = [
            'host'   => 'redis.host',
            'port'   => '6379',
            'scheme' => 'redis',
        ];
        $redisSlaveConfig = [
            'host' => 'redis-slave.host',
            'port' => '6380',
        ];
        $envCacheConfiguration = [
            'frontend' => [
                'default' => [
                    'backend_options' => [
                        'remote_backend_options' => [
                            'server' => 'custom.host',
                        ],
                    ],
                ],
            ],
            StageConfigInterface::OPTION_MERGE => true,
        ];

        $this->stageConfigMock->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CACHE_CONFIGURATION, $envCacheConfiguration],
                [DeployInterface::VAR_CACHE_REDIS_BACKEND, Cache::REDIS_BACKEND_SYMFONY_L2],
                [DeployInterface::VAR_CACHE_VALKEY_BACKEND, ''],
                [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, true],
                [DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION, false],
            ]);

        $this->redisMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($redisConfig);
        $this->redisMock->expects(self::any())
            ->method('getSlaveConfiguration')
            ->willReturn($redisSlaveConfig);
        $this->valkeyMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->loggerMock->expects(self::once())
            ->method('notice')
            ->with(
                'The variable \'' . DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION . '\' is ignored as you\'ve'
                    . ' changed cache connection settings in \'' . DeployInterface::VAR_CACHE_CONFIGURATION . '\''
            );

        $result = $this->config->get();

        self::assertArrayNotHasKey(
            'load_from_slave',
            $result['frontend']['default']['backend_options']['remote_backend_options']
        );
        self::assertSame(
            ['server' => 'redis-slave.host', 'port' => '6380'],
            $result['frontend']['stale_cache_enabled']['backend_options']['remote_backend_options']['load_from_slave']
        );
    }

    /**
     * If both a Redis and a Valkey relationship happen to exist at the same time, only the flag
     * matching the backend actually selected as active (Redis takes priority when both are present)
     * may attach a slave connection. Otherwise a stale VALKEY_USE_SLAVE_CONNECTION=true left over from
     * before Redis was added could attach a Valkey replica's host/port to the Redis master's config.
     *
     * @return void
     * @throws ConfigException
     */
    public function testGetDoesNotApplySlaveConnectionFromInactiveBackendWhenBothRelationshipsExist(): void
    {
        $redisConfig = [
            'host'   => 'redis.host',
            'port'   => '6379',
            'scheme' => 'redis',
        ];
        $valkeyConfig = [
            'host'   => 'valkey.host',
            'port'   => '6380',
            'scheme' => 'redis',
        ];

        $this->stageConfigMock->expects(self::any())
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CACHE_CONFIGURATION, []],
                [DeployInterface::VAR_CACHE_REDIS_BACKEND, Cache::REDIS_BACKEND_CM_CACHE],
                [DeployInterface::VAR_CACHE_VALKEY_BACKEND, ''],
                [DeployInterface::VAR_REDIS_USE_SLAVE_CONNECTION, false],
                [DeployInterface::VAR_VALKEY_USE_SLAVE_CONNECTION, true],
            ]);

        $this->redisMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($redisConfig);
        $this->valkeyMock->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($valkeyConfig);
        $this->valkeyMock->expects(self::never())
            ->method('getSlaveConfiguration');

        $result = $this->config->get();

        self::assertArrayNotHasKey('load_from_slave', $result['frontend']['default']['backend_options']);
        self::assertArrayNotHasKey('load_from_slave', $result['frontend']['page_cache']['backend_options']);
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
