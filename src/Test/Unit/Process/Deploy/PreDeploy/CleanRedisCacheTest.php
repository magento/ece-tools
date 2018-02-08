<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\CleanRedisCache;
use Magento\MagentoCloud\Shell\ShellInterface;
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

        $this->process = new CleanRedisCache(
            $this->loggerMock,
            $this->shellMock,
            $this->environmentMock
        );
    }

    public function testExecute()
    {
        $redisConfig = [
            [
                'host' => 'localhost',
                'port' => 1234
            ]
        ];

        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('redis')
            ->willReturn($redisConfig);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Clearing redis cache');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('redis-cli -h localhost -p 1234 -n 1 flushdb');

        $this->process->execute();
    }

    public function testExecuteWithoutRedisRelationship()
    {
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('redis')
            ->willReturn([]);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
