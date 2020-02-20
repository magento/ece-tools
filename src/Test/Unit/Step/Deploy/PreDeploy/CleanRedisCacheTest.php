<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\Service\Adapter\CredisFactory;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\CleanRedisCache;
use Magento\MagentoCloud\Config\Factory\Cache as CacheConfig;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Credis_Client;

/**
 * @inheritdoc
 */
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
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->cacheConfigMock = $this->createMock(CacheConfig::class);
        $this->credisFactoryMock = $this->createMock(CredisFactory::class);

        $this->step = new CleanRedisCache(
            $this->loggerMock,
            $this->cacheConfigMock,
            $this->credisFactoryMock
        );
    }

    /**
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
        $this->loggerMock->expects($this->exactly(5))
            ->method('info')
            ->withConsecutive(
                ['Clearing redis cache: default'],
                ['Clearing redis cache: page_cache'],
                ['Clearing redis cache: some_type0'],
                ['Clearing redis cache: some_type1'],
                ['Clearing redis cache: some_type2']
            );

        /** @var Credis_Client|MockObject $credisClient */
        $credisClient = $this->getMockBuilder(Credis_Client::class)
            ->setMethods(['connect', 'flushDb'])
            ->getMock();
        $this->credisFactoryMock->expects($this->exactly(5))
            ->method('create')
            ->withConsecutive(
                ['localhost', '1234', 0],
                ['127.0.0.1', 1234, 1],
                ['localhost', 6379, 2],
                ['localhost', 1234, 0],
                ['127.0.0.1', 6379, 0]
            )->willReturn($credisClient);

        $credisClient->expects($this->exactly(5))
            ->method('connect');
        $credisClient->expects($this->exactly(5))
            ->method('flushDb');

        $this->step->execute();
    }

    /**
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
}
