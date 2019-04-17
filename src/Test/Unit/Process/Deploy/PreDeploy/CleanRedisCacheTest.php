<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\CleanRedisCache;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Config\Factory\Cache as СacheConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CleanRedisCacheTest extends TestCase
{
    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var СacheConfig|Mock
     */
    private $cacheConfigMock;

    /**
     * @var CleanRedisCache
     */
    private $process;

    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->cacheConfigMock = $this->createMock(СacheConfig::class);

        $this->process = new CleanRedisCache(
            $this->loggerMock,
            $this->shellMock,
            $this->environmentMock,
            $this->cacheConfigMock
        );
    }

    public function testExecute()
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
        $this->shellMock->expects($this->exactly(5))
            ->method('execute')
            ->withConsecutive(
                ['redis-cli -h localhost -p 1234 -n 0 flushdb'],
                ['redis-cli -p 1234 -n 1 flushdb'],
                ['redis-cli -h localhost -n 2 flushdb'],
                ['redis-cli -h localhost -p 1234 flushdb'],
                ['redis-cli flushdb']
            );

        $this->process->execute();
    }

    public function testExecuteWithoutRedis()
    {
        $this->cacheConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->shellMock->expects($this->never())
            ->method('execute');
        $this->process->execute();
    }
}
