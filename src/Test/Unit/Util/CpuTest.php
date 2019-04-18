<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\Cpu;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CpuTest extends TestCase
{
    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Cpu
     */
    private $cpu;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->createMock(ShellInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->cpu = new Cpu($this->shellMock, $this->loggerMock);
    }

    public function testGetTreadsCount()
    {
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('grep -c processor /proc/cpuinfo')
            ->willReturn([8]);

        $this->assertEquals(8, $this->cpu->getThreadsCount());
    }
}
