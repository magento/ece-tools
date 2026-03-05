<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy;

use Credis_Client;
use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Factory\Cache as CacheConfig;
use Magento\MagentoCloud\Service\Adapter\CredisFactory;
use Magento\MagentoCloud\Service\Redis as RedisService;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\CleanRedisCache;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class CleanRedisCacheTest extends TestCase
{
    /**
     * @var CleanRedisCache
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var CacheConfig|MockObject
     */
    private $cacheConfigMock;

    /**
     * @var CredisFactory|MockObject
     */
    private $credisFactoryMock;

    /**
     * @var RedisService|MockObject
     */
    private $redisServiceMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->cacheConfigMock = $this->createMock(CacheConfig::class);
        $this->credisFactoryMock = $this->createMock(CredisFactory::class);
        $this->redisServiceMock = $this->createMock(RedisService::class);
        $this->redisServiceMock->method('getConfiguration')->willReturn(['host' => 'redis']);

        $this->step = new CleanRedisCache(
            $this->loggerMock,
            $this->cacheConfigMock,
            $this->credisFactoryMock,
            $this->redisServiceMock
        );
    }

    /**
     * Test execute method.
     *
     * @return void
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'localhost',
                            'port' => 1234,
                            'database' => 0
                        ]
                    ],
                    'page_cache' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'port' => 1234,
                            'database' => 1
                        ]
                    ],
                    'some_type0' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'localhost',
                            'database' => 2,
                            'password' => 'password'
                        ]
                    ],
                    'some_type1' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'localhost',
                            'port' => 1234,
                        ]
                    ],
                    'some_type2' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => []
                    ],
                    'some_type3' => [
                        'backend' => 'SomeClase',
                    ],
                    'some_type4' => [
                        'backend' => 'SomeClase',
                        '_custom_redis_backend' => true,
                        'backend_options' => []
                    ],
                ]
            ]);
        $this->loggerMock->expects($this->exactly(6))
            ->method('info')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($args) {
                static $series = [
                    'Clearing redis cache: default',
                    'Clearing redis cache: page_cache',
                    'Clearing redis cache: some_type0',
                    'Clearing redis cache: some_type1',
                    'Clearing redis cache: some_type2',
                    'Clearing redis cache: some_type4'
                ];
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });

        /** @var Credis_Client|MockObject $credisClient */
        $credisClient = $this->getMockBuilder(Credis_Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['connect', '__call'])
            ->getMock();
        $this->credisFactoryMock->expects($this->exactly(6))
            ->method('create')
            ->willReturnCallback(fn($param) => match ($param) {
                'localhost', '1234', 0 => $credisClient,
                '127.0.0.1', 1234, 1 => $credisClient,
                'localhost', 6379, 2, 'password' => $credisClient,
                'localhost', 1234, 0 => $credisClient,
                '127.0.0.1', 6379, 0 => $credisClient,
            });
        $credisClient->expects($this->exactly(6))
            ->method('connect');
        $credisClient->expects($this->exactly(6))
            ->method('__call')
            ->with('flushDb', [])
            ->willReturn(null);

        $this->step->execute();
    }

    /**
     * Test execute method with error.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteWithError(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');

        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'localhost',
                            'port' => 1234,
                            'database' => 0
                        ]
                    ],
                    'page_cache' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'port' => 1234,
                            'database' => 1
                        ]
                    ],
                    'some_type0' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'localhost',
                            'database' => 2
                        ]
                    ],
                    'some_type1' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'localhost',
                            'port' => 1234,
                        ]
                    ],
                    'some_type2' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => []
                    ],
                    'some_type3' => [
                        'backend' => 'SomeClase',
                    ]
                ]
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->willReturnCallback(function ($args) {
                static $series = [
                    'Clearing redis cache: default'
                ];
                $expectedArgs = array_shift($series);
                $this->assertSame($expectedArgs, $args);
            });

        /** @var Credis_Client|MockObject $credisClient */
        $credisClient = $this->getMockBuilder(Credis_Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['connect', '__call'])
            ->getMock();
        $this->credisFactoryMock->expects($this->once())
            ->method('create')
            ->willReturnCallback(fn($param) => match ($param) {
                'localhost', '1234', 0 => $credisClient
            });

        $credisClient->method('connect')
            ->willThrowException(new \CredisException('Some error'));

        $this->step->execute();
    }

    /**
     * Test execute method without Redis.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteWithoutRedis(): void
    {
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->credisFactoryMock->expects($this->never())
            ->method('create');

        $this->step->execute();
    }

    /**
     * Test execute method with Credis exception.
     *
     * @return void
     * @throws StepException
     */
    public function testExecuteWithCredisException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_REDIS_CACHE_CLEAN_FAILED);
        $this->expectExceptionMessage('connection error');

        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'frontend' => [
                    'default' => [
                        'backend' => 'Cm_Cache_Backend_Redis',
                        'backend_options' => [
                            'server' => 'localhost',
                            'port' => 1234,
                            'database' => 0
                        ]
                    ],
                ]
            ]);
        $credisClientMock = $this->getMockBuilder(Credis_Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->credisFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($credisClientMock);
        $credisClientMock->expects($this->once())
            ->method('connect')
            ->willThrowException(new \CredisException('connection error'));

        $this->step->execute();
    }
}
