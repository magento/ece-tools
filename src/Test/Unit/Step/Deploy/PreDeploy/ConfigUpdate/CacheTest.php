<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Factory\Cache as CacheFactory;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\ConfigUpdate\Cache;
use Magento\MagentoCloud\Step\StepException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * @inheritdoc
 */
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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->cacheConfigMock = $this->createMock(CacheFactory::class);
        $this->magentoVersion = $this->createMock(MagentoVersion::class);

        $this->step = new Cache(
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->cacheConfigMock,
            $this->magentoVersion
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
     * @param array $config
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $config, bool $isGreaterOrEqual)
    {
        $this->magentoVersion->expects($this->any())
            ->method('isGreaterOrEqual')
            ->with('2.3.0')
            ->willReturn($isGreaterOrEqual);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn($config);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => $config]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->socketCreateMock->expects($this->once());
        $this->socketConnectMock->expects($this->once())
            ->willReturn(true);
        $this->socketCloseMock->expects($this->once());

        $this->step->execute();
    }

    public function executeDataProvider(): array
    {
        return [
            'backend model without remote_backend_options' => [
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
                'isGreaterOrEqual' => false,
            ],
            'backend model with remote_backend_options' => [
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
                'isGreaterOrEqual' => true,
            ],
        ];
    }

    public function testExecuteEmptyConfig()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['cache' => [
                'frontend' => ['frontName' => ['backend' => 'cacheDriver']],
            ]]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Cache configuration was not found. Removing cache configuration.');

        $this->socketCreateMock->expects($this->never());
        $this->socketConnectMock->expects($this->never());
        $this->socketCloseMock->expects($this->never());

        $this->step->execute();
    }

    public function testExecuteRedisService()
    {
        $this->prepareMocks();

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['cache' => [
                'frontend' => ['frontName' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                ]],
            ]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->step->execute();
    }

    public function testExecuteRedisFailed()
    {
        $this->prepareMocks(false);

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([]);
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Cache is configured for a Redis service that is not available. Configuration will be ignored.');

        $this->step->execute();
    }

    public function testExecuteMixedBackends()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
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
                            'remote_backend_options' => ['server' => 'redis.server', 'port' => 6379],],
                    ],
                    'frontName4' => [
                        'backend' => 'SomeModel',
                    ],
                ],
            ]);

        $this->magentoVersion->expects($this->exactly(2))
            ->method('isGreaterOrEqual')
            ->with('2.3.0')
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
            ->with(['cache' => [
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
                            'remote_backend_options' => ['server' => 'redis.server', 'port' => 6379],],
                    ],
                    'frontName4' => [
                        'backend' => 'SomeModel',
                    ],
                ],
            ]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating cache configuration.');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithWrongConfiguration()
    {
        $this->expectExceptionCode(Error::DEPLOY_WRONG_CACHE_CONFIGURATION);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Missing required Redis configuration!');

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => ['frontName' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => ['server' => 'redis.server'],
                ]],
            ]);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithFileSystemException()
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
     * @param bool $socketConnect
     */
    public function prepareMocks(bool $socketConnect = true): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => ['frontName' => [
                    'backend' => 'Cm_Cache_Backend_Redis',
                    'backend_options' => ['server' => 'redis.server', 'port' => 6379],
                ]],
            ]);

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
