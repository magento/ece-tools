<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
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
     * @var Cpu
     */
    private $cpu;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->shellMock = $this->createMock(ShellInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->cpu = new Cpu($this->shellMock, $this->loggerMock);
    }

    public function testGetTreadsCount(): void
    {
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn('8');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('nproc')
            ->willReturn($processMock);

        $this->assertEquals(8, $this->cpu->getThreadsCount());
    }

    public function testGetTreadsCountWithError(): void
    {
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new ShellException('Some error'));

        $this->assertEquals(1, $this->cpu->getThreadsCount());
    }
}
