<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Factory\Cache as CacheFactory;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\ConfigUpdate\Cache;
use Magento\MagentoCloud\Step\StepException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class CacheTest extends TestCase
{
    use PHPMock;

    /**
     * @var Cache
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var CacheFactory|MockObject
     */
    private $cacheConfigMock;

    /**
     * @var MockObject
     */
    private $socketCreateMock;

    /**
     * @var MockObject
     */
    private $socketConnectMock;

    /**
     * @var MockObject
     */
    private $socketCloseMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersion;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock       = $this->createMock(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->cacheConfigMock  = $this->createMock(CacheFactory::class);
        $this->magentoVersion   = $this->createMock(MagentoVersion::class);
        $this->stageConfig      = $this->createMock(DeployInterface::class);

        $this->step = new Cache(
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->cacheConfigMock,
            $this->magentoVersion,
            $this->stageConfig
        );

        $this->socketCreateMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Step\Deploy\PreDeploy\ConfigUpdate',
            'socket_create'
        );
        $this->socketConnectMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Step\Deploy\PreDeploy\ConfigUpdate',
            'socket_connect'
        );
        $this->socketCloseMock = $this->getFunctionMock(
            'Magento\MagentoCloud\Step\Deploy\PreDeploy\ConfigUpdate',
            'socket_close'
        );
    }
    
    /**
     * Test execute method.
     *
     * @param array $configFromFile
     * @param array $config
     * @param array $finalConfig
     * @param bool $isGreaterOrEqual
     * @param string $address
     * @param int $port
     * @dataProvider executeDataProvider
     * @return void
     * @throws StepException
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(
        array $configFromFile,
        array $config,
        array $finalConfig,
        bool $isGreaterOrEqual,
        string $address,
        int $port
    ): void {
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->with($this->anything())
            ->willReturn($isGreaterOrEqual);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($configFromFile);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($config);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($finalConfig);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->socketCreateMock->expects($this->once())
            ->willReturn($sock);
        $this->socketConnectMock->expects($this->once())
            ->with($sock, $address, $port)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->once())
            ->with($sock);
        socket_close($sock);

        $this->step->execute();
    }

    public function testExecuteSetsLuaOptionsForDefaultFrontend(): void
    {
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                        'backend_options' => [
                            'server' => 'localhost',
                            'port' => 6370,
                        ],
                    ],
                ],
            ]);
        $this->stageConfig->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_USE_LUA, true],
                [DeployInterface::VAR_USE_LUA_ON_GC, false],
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'cache' => [
                    'frontend' => [
                        'default' => [
                            'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                            'backend_options' => [
                                'server' => 'localhost',
                                'port' => 6370,
                                'use_lua' => true,
                                'use_lua_on_gc' => false,
                            ],
                        ],
                    ],
                ],
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->socketCreateMock->expects($this->once())
            ->willReturn($sock);
        $this->socketConnectMock->expects($this->once())
            ->with($sock, 'localhost', 6370)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->once())
            ->with($sock);
        socket_close($sock);

        $this->step->execute();
    }

    public function testExecuteDoesNotSetUseLuaOnGcForUnsupportedVersion(): void
    {
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->willReturnCallback(static function (string $version): bool {
                if ($version === '2.4.8') {
                    return false;
                }

                return true;
            });
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                        'backend_options' => [
                            'server' => 'localhost',
                            'port' => 6370,
                            'use_lua_on_gc' => true,
                        ],
                    ],
                ],
            ]);
        $this->stageConfig->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_USE_LUA, true],
                [DeployInterface::VAR_USE_LUA_ON_GC, true],
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'cache' => [
                    'frontend' => [
                        'default' => [
                            'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                            'backend_options' => [
                                'server' => 'localhost',
                                'port' => 6370,
                                'use_lua' => true,
                            ],
                        ],
                    ],
                ],
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->socketCreateMock->expects($this->once())
            ->willReturn($sock);
        $this->socketConnectMock->expects($this->once())
            ->with($sock, 'localhost', 6370)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->once())
            ->with($sock);
        socket_close($sock);

        $this->step->execute();
    }

    public function testExecuteDoesNotSetUseLuaForUnsupportedVersion(): void
    {
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->willReturnCallback(static function (string $version): bool {
                if ($version === '2.4.7' || $version === '2.4.8') {
                    return false;
                }

                return true;
            });
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                        'backend_options' => [
                            'server' => 'localhost',
                            'port' => 6370,
                            'use_lua' => true,
                            'use_lua_on_gc' => true,
                        ],
                    ],
                ],
            ]);
        $this->stageConfig->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_USE_LUA, true],
                [DeployInterface::VAR_USE_LUA_ON_GC, true],
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'cache' => [
                    'frontend' => [
                        'default' => [
                            'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                            'backend_options' => [
                                'server' => 'localhost',
                                'port' => 6370,
                            ],
                        ],
                    ],
                ],
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->socketCreateMock->expects($this->once())
            ->willReturn($sock);
        $this->socketConnectMock->expects($this->once())
            ->with($sock, 'localhost', 6370)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->once())
            ->with($sock);
        socket_close($sock);

        $this->step->execute();
    }

    public function testExecuteSetsLuaOptionsWhenDefaultBackendOptionsAreMissing(): void
    {
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => 'file',
                    ],
                ],
            ]);
        $this->stageConfig->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_USE_LUA, true],
                [DeployInterface::VAR_USE_LUA_ON_GC, false],
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'cache' => [
                    'frontend' => [
                        'default' => [
                            'backend' => 'file',
                            'backend_options' => [
                                'use_lua' => true,
                                'use_lua_on_gc' => false,
                            ],
                        ],
                    ],
                ],
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');
        $this->socketCreateMock->expects($this->never());
        $this->socketConnectMock->expects($this->never());
        $this->socketCloseMock->expects($this->never());

        $this->step->execute();
    }

    /**
     * DataProvider for execute method.
     *
     * @return array[]
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function executeDataProvider(): array
    {
        return [
            'with qraphql config in file' => [
                'configFromFile' => [
                    'cache' => ['graphql' => ['id_salt' => 'some salt']],
                ],
                'config' => [
                    'frontend' => [
                        'frontName' => [
                            'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                            'backend_options' => [
                                'server' => 'localhost',
                                'port' => 6370,
                            ],
                        ],
                    ],
                ],
                'finalConfig' => [
                    'cache' => [
                        'frontend' => [
                            'frontName' => [
                                'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                                'backend_options' => [
                                    'server' => 'localhost',
                                    'port' => 6370,
                                ],
                            ],
                        ],
                        'graphql' => [
                            'id_salt' => 'some salt',
                        ],
                    ],
                ],
                'isGreaterOrEqual' => false,
                'address' => 'localhost',
                'port' => 6370
            ],
            'backend model without remote_backend_options' => [
                'configFromFile' => [],
                'config' => [
                    'frontend' => [
                        'frontName' => [
                            'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                            'backend_options' => [
                                'server' => 'localhost',
                                'port' => 6370,
                            ],
                        ],
                    ],
                ],
                'finalConfig' => [
                    'cache' => [
                        'frontend' => [
                            'frontName' => [
                                'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                                'backend_options' => [
                                    'server' => 'localhost',
                                    'port' => 6370,
                                ],
                            ],
                        ],
                    ],
                ],
                'isGreaterOrEqual' => false,
                'address' => 'localhost',
                'port' => 6370
            ],
            'backend model with remote_backend_options' => [
                'configFromFile' => [],
                'config' => [
                    'frontend' => [
                        'frontName' => [
                            'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                            'backend_options' => [
                                'remote_backend_options' => [
                                    'server' => 'localhost',
                                    'port' => 6370,
                                ],
                            ],
                        ],
                    ],
                ],
                'finalConfig' => [
                    'cache' => [
                        'frontend' => [
                            'frontName' => [
                                'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                                'backend_options' => [
                                    'remote_backend_options' => [
                                        'server' => 'localhost',
                                        'port' => 6370,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'isGreaterOrEqual' => true,
                'address' => 'localhost',
                'port' => 6370
            ],
            'Server contains port data' => [
                'configFromFile' => [],
                'config' => [
                    'frontend' => [
                        'frontName' => [
                            'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                            'backend_options' => [
                                'remote_backend_options' => [
                                    'server' => '127.0.0.1:6371',
                                ],
                            ],
                        ],
                    ],
                ],
                'finalConfig' => [
                    'cache' => [
                        'frontend' => [
                            'frontName' => [
                                'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                                'backend_options' => [
                                    'remote_backend_options' => [
                                        'server' => '127.0.0.1:6371',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'isGreaterOrEqual' => true,
                'address' => '127.0.0.1',
                'port' => 6371
            ],
            'Server contains protocol and port data' => [
                'configFromFile' => [],
                'config' => [
                    'frontend' => [
                        'frontName' => [
                            'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                            'backend_options' => [
                                'remote_backend_options' => [
                                    'server' => 'tcp://localhost:6379',
                                ],
                            ],
                        ],
                    ],
                ],
                'finalConfig' => [
                    'cache' => [
                        'frontend' => [
                            'frontName' => [
                                'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                                'backend_options' => [
                                    'remote_backend_options' => [
                                        'server' => 'tcp://localhost:6379',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'isGreaterOrEqual' => true,
                'address' => 'localhost',
                'port' => 6379
            ],
            'Custom redis model' => [
                'configFromFile' => [],
                'config' => [
                    'frontend' => [
                        'frontName' => [
                            'backend' => 'SomeCustomRedisModel',
                            '_custom_redis_backend' => true,
                            'backend_options' => [
                                'server' => 'localhost',
                                'port' => 6370,
                            ],
                        ],
                    ],
                ],
                'finalConfig' => [
                    'cache' => [
                        'frontend' => [
                            'frontName' => [
                                'backend' => 'SomeCustomRedisModel',
                                'backend_options' => [
                                    'server' => 'localhost',
                                    'port' => 6370,
                                ],
                            ],
                        ],
                    ],
                ],
                'isGreaterOrEqual' => true,
                'address' => 'localhost',
                'port' => 6370
            ],
        ];
    }
    
    /**
     * Test execute empty config method.
     *
     * @param array $cacheConfig
     * @param array $finalConfig
     * @dataProvider executeEmptyConfig
     * @return void
     * @throws StepException
     */
    #[DataProvider('executeEmptyConfig')]
    public function testExecuteEmptyConfig(array $cacheConfig, array $finalConfig): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($cacheConfig);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with($finalConfig);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Cache configuration was not found. Removing cache configuration.');

        $this->socketCreateMock->expects($this->never());
        $this->socketConnectMock->expects($this->never());
        $this->socketCloseMock->expects($this->never());

        $this->step->execute();
    }

    /**
     * DataProvider for execute empty config method.
     *
     * @return array
     */
    public static function executeEmptyConfig(): array
    {
        return [
            'without graphql in config' => [
                'cacheConfig' => [
                    'cache' => [
                        'frontend' => ['frontName' => ['backend' => 'cacheDriver']],
                    ],
                ],
                'finalConfig' => [],
            ],
            'with graphql in config' => [
                'cacheConfig' => [
                    'cache' => [
                        'frontend' => ['frontName' => ['backend' => 'cacheDriver']],
                        'graphql' => ['id_salt' => 'some salt'],
                    ],
                ],
                'finalConfig' => [
                    'cache' => [
                        'graphql' => ['id_salt' => 'some salt'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test execute redis service method.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteRedisService(): void
    {
        $this->prepareMocks();

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(
                ['cache' => [
                    'frontend' => ['frontName' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                    ]],
                ]]
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->step->execute();
    }

    /**
     * Test execute redis failed method.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteRedisFailed(): void
    {
        $this->prepareMocks(false);

        $this->configWriterMock->expects($this->any())
            ->method('create')
            ->with([]);
        $this->loggerMock->expects($this->any())
            ->method('warning')
            ->with('Cache is configured for a Redis service that is not available. Configuration will be ignored.');

        $this->step->execute();
    }

    /**
     * Test execute mixed backends method.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteMixedBackends(): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(
                [
                    'frontend' => [
                        'frontName1' => [
                            'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                            'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                        ],
                        'frontName2' => [
                            'backend' => CacheFactory::REDIS_BACKEND_REDIS_CACHE,
                            'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                        ],
                        'frontName3' => [
                            'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                            'backend_options' => [
                                'remote_backend_options' => ['server' => 'redis.server', 'port' => 6379],
                            ],
                        ],
                        'frontName4' => [
                            'backend' => 'SomeModel',
                        ],
                    ],
                ]
            );

        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->with($this->anything())
            ->willReturn(true);
        $this->socketCreateMock->expects($this->exactly(3))
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->exactly(3))
            ->with('socket resource', 'redis.server', 6379)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->exactly(3))
            ->with('socket resource');

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(
                ['cache' => [
                    'frontend' => [
                        'frontName2' => [
                            'backend' => CacheFactory::REDIS_BACKEND_REDIS_CACHE,
                            'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                        ],
                        'frontName1' => [
                            'backend' => CacheFactory::REDIS_BACKEND_CM_CACHE,
                            'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                        ],
                        'frontName3' => [
                            'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                            'backend_options' => [
                                'remote_backend_options' => ['server' => 'redis.server', 'port' => 6379],
                            ],
                        ],
                        'frontName4' => [
                            'backend' => 'SomeModel',
                        ],
                    ],
                ]]
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->step->execute();
    }

    /**
     * Test execute with wrong configuration method.
     *
     * @param array $options
     * @param string $errorMessage
     * @dataProvider dataProviderExecuteWithWrongConfiguration
     * @return void
     * @throws StepException
     */
    #[DataProvider('dataProviderExecuteWithWrongConfiguration')]
    public function testExecuteWithWrongConfiguration(array $options, string $errorMessage): void
    {
        $this->expectExceptionCode(Error::DEPLOY_WRONG_CACHE_CONFIGURATION);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage($errorMessage);

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(
                [
                    'frontend' => ['frontName' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => $options,
                    ]],
                ]
            );

        $this->step->execute();
    }

    /**
     * DataProvider for execute with wrong configuration method.
     *
     * @return array
     */
    public static function dataProviderExecuteWithWrongConfiguration(): array
    {
        return [
            [
                ['server' => 'redis.server'],
                'Missing required Redis or Valkey configuration \'port\'!'
            ],
            [
                ['server' => '', 'port' => '6379'],
                'Missing required Redis or Valkey configuration \'server\'!'
            ],
            [
                ['port' => '6379'],
                'Missing required Redis or Valkey configuration \'server\'!'
            ],
        ];
    }

    /**
     * Test that a symfony_l2 frontend with no 'remote_backend_options' key at all (e.g. a merchant's
     * CACHE_CONFIGURATION that only sets a top-level 'preload_keys' without full remote connection
     * details) fails with a clean StepException instead of an undefined array key warning.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteSymfonyL2MissingRemoteBackendOptionsThrowsCleanException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_WRONG_CACHE_CONFIGURATION);
        $this->expectExceptionMessage('Missing required Redis or Valkey configuration \'server\'!');

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => CacheFactory::VALKEY_BACKEND_SYMFONY_L2,
                        'backend_options' => [
                            'preload_keys' => ['EAV_ENTITY_TYPES:hash'],
                        ],
                    ],
                ],
            ]);
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->willReturn(true);

        $this->socketCreateMock->expects($this->never());

        $this->step->execute();
    }

    /**
     * Same missing-'remote_backend_options' scenario as above, for the legacy RemoteSynchronizedCache
     * (L2) backend model rather than symfony_l2.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteRemoteSynchronizedCacheMissingRemoteBackendOptionsThrowsCleanException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_WRONG_CACHE_CONFIGURATION);
        $this->expectExceptionMessage('Missing required Redis or Valkey configuration \'server\'!');

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                        'backend_options' => [
                            'preload_keys' => ['061_EAV_ENTITY_TYPES:hash'],
                        ],
                    ],
                ],
            ]);
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->willReturn(true);

        $this->socketCreateMock->expects($this->never());

        $this->step->execute();
    }

    /**
     * When 'remote_backend_options' is absent for a symfony_l2 frontend but the connection details
     * ('server'/'port') were placed flat under 'backend_options' instead, the connection test must
     * fall back to those flat options rather than testing an empty array.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteSymfonyL2FallsBackToFlatBackendOptionsWhenRemoteBackendOptionsMissing(): void
    {
        $config = [
            'frontend' => [
                'default' => [
                    'backend' => CacheFactory::VALKEY_BACKEND_SYMFONY_L2,
                    'backend_options' => [
                        'server' => 'valkey.server',
                        'port' => 6379,
                    ],
                ],
            ],
        ];

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($config);
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->willReturn(true);

        $this->socketCreateMock->expects($this->once())
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->once())
            ->with('socket resource', 'valkey.server', 6379)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->once())
            ->with('socket resource');

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => $config]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->step->execute();
    }

    /**
     * Same flat-'backend_options' fallback as above, for the legacy RemoteSynchronizedCache (L2)
     * backend model rather than symfony_l2.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteRemoteSyncCacheFallsBackToFlatBackendOptionsWhenRemoteBackendOptionsMissing(): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                        'backend_options' => [
                            'server' => 'redis.server',
                            'port' => 6379,
                        ],
                    ],
                ],
            ]);
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->willReturn(true);

        $this->socketCreateMock->expects($this->once())
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->once())
            ->with('socket resource', 'redis.server', 6379)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->once())
            ->with('socket resource');

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => [
                'frontend' => [
                    'default' => [
                        'backend' => CacheFactory::REDIS_BACKEND_REMOTE_SYNCHRONIZED_CACHE,
                        'backend_options' => [
                            'server' => 'redis.server',
                            'port' => 6379,
                            'use_lua' => false,
                            'use_lua_on_gc' => false,
                        ],
                    ],
                ],
            ]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->step->execute();
    }

    /**
     * Mirrors the two real client CACHE_CONFIGURATION shapes: 'default' frontend puts 'preload_keys'
     * directly under top-level 'backend_options' (alongside a fully-populated 'remote_backend_options'
     * coming from the auto-generated skeleton, as happens after a normal _merge: true), while
     * 'stale_cache_enabled' nests 'preload_keys' inside 'remote_backend_options' instead. Both frontends
     * have a complete 'remote_backend_options' (server/port), so neither hits the missing-key case
     * directly - this confirms deployment succeeds cleanly for both placements, for both frontends,
     * in the same deploy.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteSymfonyL2PreloadKeysWorksInBothPlacementsForDefaultAndStaleFrontends(): void
    {
        $symfonyL2Config = [
            'frontend' => [
                'default' => [
                    'backend' => CacheFactory::VALKEY_BACKEND_SYMFONY_L2,
                    'id_prefix' => '061_',
                    'backend_options' => [
                        'remote_backend' => 'valkey',
                        'remote_backend_options' => [
                            'server' => 'valkey.server',
                            'port' => 6379,
                            'database' => 1,
                        ],
                        'local_backend' => 'file',
                        'local_backend_options' => ['cache_dir' => '/dev/shm/magento_l1'],
                        // top-level placement (client config #1)
                        'preload_keys' => ['061_EAV_ENTITY_TYPES:hash', '061_GLOBAL_PLUGIN_LIST:hash'],
                    ],
                ],
                'stale_cache_enabled' => [
                    'backend' => CacheFactory::VALKEY_BACKEND_SYMFONY_L2,
                    'id_prefix' => '069_',
                    'backend_options' => [
                        'remote_backend' => 'valkey',
                        'remote_backend_options' => [
                            'server' => 'valkey.server',
                            'port' => 6379,
                            'database' => 1,
                            'serializer' => 'igbinary',
                            'read_timeout' => 10,
                            'connect_retries' => 3,
                            // nested placement (client config #2)
                            'preload_keys' => ['069_EAV_ENTITY_TYPES'],
                        ],
                        'local_backend' => 'file',
                        'local_backend_options' => ['cache_dir' => '/dev/shm/magento_l1_stale'],
                        'use_stale_cache' => true,
                    ],
                ],
            ],
            'type' => [
                'default' => ['frontend' => 'default'],
                'layout' => ['frontend' => 'stale_cache_enabled'],
            ],
        ];

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($symfonyL2Config);
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->stageConfig->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_USE_LUA, false],
                [DeployInterface::VAR_USE_LUA_ON_GC, false],
            ]);

        $this->socketCreateMock->expects($this->exactly(2))
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->exactly(2))
            ->with('socket resource', 'valkey.server', 6379)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->exactly(2))
            ->with('socket resource');

        $expectedConfig = $symfonyL2Config;
        $expectedConfig['frontend']['default']['backend_options']['remote_backend_options']['use_lua'] = '0';
        $expectedConfig['frontend']['default']['backend_options']['remote_backend_options']['use_lua_on_gc'] = '0';
        $expectedConfig['frontend']['stale_cache_enabled']['backend_options']['remote_backend_options']['use_lua']
            = '0';
        $expectedConfig['frontend']['stale_cache_enabled']['backend_options']['remote_backend_options']
            ['use_lua_on_gc'] = '0';

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => $expectedConfig]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->step->execute();
    }

    /**
     * Test that symfony_l2 backend is rejected on Magento versions older than 2.4.9.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteSymfonyL2RejectedOnOldMagentoVersion(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_WRONG_CACHE_CONFIGURATION);
        $this->expectExceptionMessage('does not support symfony_l2 cache backend');

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => CacheFactory::VALKEY_BACKEND_SYMFONY_L2,
                        'backend_options' => [
                            'remote_backend'         => 'redis',
                            'remote_backend_options' => ['server' => 'localhost', 'port' => 6379],
                            'local_backend'          => 'file',
                            'local_backend_options'  => ['cache_dir' => '/dev/shm/magento_l1'],
                        ],
                    ],
                ],
            ]);

        $this->magentoVersion->method('isGreaterOrEqual')
            ->willReturnMap([
                ['2.4.5', true],
                ['2.4.7', true],
                ['2.4.8', false],
                ['2.4.9', false],
                ['2.3.0', true],
            ]);

        $this->socketCreateMock->expects($this->never());

        $this->step->execute();
    }

    /**
     * Test that symfony_l2 config with both default and stale_cache_enabled frontends passes
     * connection testing and gets Lua options injected into both frontends' remote_backend_options,
     * written as '1'/'0' strings (required by Magento's SymfonyAdapterProvider).
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteSymfonyL2TwoFrontendsConnectAndSetsLua(): void
    {
        $symfonyL2Config = [
            'frontend' => [
                'default' => [
                    'backend' => CacheFactory::VALKEY_BACKEND_SYMFONY_L2,
                    'backend_options' => [
                        'remote_backend'         => 'redis',
                        'remote_backend_options' => ['server' => 'redis.server', 'port' => 6379],
                        'local_backend'          => 'file',
                        'local_backend_options'  => ['cache_dir' => '/dev/shm/magento_l1'],
                    ],
                ],
                'stale_cache_enabled' => [
                    'backend' => CacheFactory::VALKEY_BACKEND_SYMFONY_L2,
                    'backend_options' => [
                        'remote_backend'         => 'redis',
                        'remote_backend_options' => ['server' => 'redis.server', 'port' => 6379],
                        'local_backend'          => 'file',
                        'local_backend_options'  => ['cache_dir' => '/dev/shm/magento_l1_stale'],
                        'use_stale_cache'        => true,
                    ],
                ],
            ],
            'type' => [
                'default'    => ['frontend' => 'default'],
                'layout'     => ['frontend' => 'stale_cache_enabled'],
                'block_html' => ['frontend' => 'stale_cache_enabled'],
            ],
        ];

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($symfonyL2Config);
        $this->magentoVersion->method('isGreaterOrEqual')
            ->willReturnMap([
                ['2.4.5', true],
                ['2.4.7', true],
                ['2.4.8', true],
                ['2.4.9', true],
                ['2.3.0', true],
            ]);
        $this->stageConfig->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_USE_LUA, true],
                [DeployInterface::VAR_USE_LUA_ON_GC, false],
            ]);

        $this->socketCreateMock->expects($this->exactly(2))
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->exactly(2))
            ->with('socket resource', 'redis.server', 6379)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->exactly(2))
            ->with('socket resource');

        $expectedConfig = $symfonyL2Config;
        $expectedConfig['frontend']['default']['backend_options']['remote_backend_options']['use_lua'] = '1';
        $expectedConfig['frontend']['default']['backend_options']['remote_backend_options']['use_lua_on_gc'] = '0';
        $expectedConfig['frontend']['stale_cache_enabled']['backend_options']['remote_backend_options']['use_lua']
            = '1';
        $expectedConfig['frontend']['stale_cache_enabled']['backend_options']['remote_backend_options']
            ['use_lua_on_gc'] = '0';

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => $expectedConfig]);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->step->execute();
    }

    /**
     * Test that Lua options are not injected into symfony_l2 backend_options on Magento
     * versions that don't support them (mirrors the legacy-backend version gating).
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteSymfonyL2DoesNotSetLuaForUnsupportedVersion(): void
    {
        $symfonyL2Config = [
            'frontend' => [
                'default' => [
                    'backend' => CacheFactory::VALKEY_BACKEND_SYMFONY_L2,
                    'backend_options' => [
                        'remote_backend'         => 'redis',
                        'remote_backend_options' => ['server' => 'redis.server', 'port' => 6379],
                        'local_backend'          => 'file',
                        'local_backend_options'  => ['cache_dir' => '/dev/shm/magento_l1'],
                    ],
                ],
            ],
            'type' => [
                'default' => ['frontend' => 'default'],
            ],
        ];

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($symfonyL2Config);
        $this->magentoVersion->method('isGreaterOrEqual')
            ->willReturnCallback(static function (string $version): bool {
                if ($version === '2.4.7' || $version === '2.4.8') {
                    return false;
                }

                return true;
            });
        $this->stageConfig->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_USE_LUA, true],
                [DeployInterface::VAR_USE_LUA_ON_GC, true],
            ]);

        $this->socketCreateMock->expects($this->once())
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->once())
            ->with('socket resource', 'redis.server', 6379)
            ->willReturn(true);
        $this->socketCloseMock->expects($this->once())
            ->with('socket resource');

        // Neither use_lua nor use_lua_on_gc is supported below 2.4.7/2.4.8, so neither is injected.
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => $symfonyL2Config]);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->step->execute();
    }

    /**
     * Test execute with file system exception method.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteWithFileSystemException(): void
    {
        $this->expectExceptionCode(Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->prepareMocks();

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->willThrowException(new FileSystemException('some error'));

        $this->step->execute();
    }

    /**
     * Prepare mocks method.
     *
     * @param bool $socketConnect
     * @return void
     */
    public function prepareMocks(bool $socketConnect = true): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(
                [
                    'frontend' => ['frontName' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                    ]],
                ]
            );

        $this->socketCreateMock->expects($this->once())
            ->with(AF_INET, SOCK_STREAM, SOL_TCP)
            ->willReturn('socket resource');
        $this->socketConnectMock->expects($this->once())
            ->with('socket resource', 'redis.server', 6379)
            ->willReturn($socketConnect);
        $this->socketCloseMock->expects($this->once())
            ->with('socket resource');
    }
}
