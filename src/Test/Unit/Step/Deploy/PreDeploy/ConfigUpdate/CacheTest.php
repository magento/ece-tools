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
