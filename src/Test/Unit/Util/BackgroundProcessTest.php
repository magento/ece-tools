<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Util\BackgroundProcess;
use Magento\MagentoCloud\Shell\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class BackgroundProcessTest extends TestCase
{
    /**
     * @var BackgroundProcess
     */
    private $process;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->createMock(ShellInterface::class);

        $this->process = new BackgroundProcess(
            $this->loggerMock,
            $this->shellMock
        );
    }

    public function testKill()
    {
        $processMock1 = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock1->expects($this->once())
            ->method('getOutput')
            ->willReturn("111\n222");
        $processMock2 = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock2->expects($this->any())
            ->method('getOutput')
            ->willReturn([]);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Trying to kill running cron jobs and consumers processes');
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturnMap(
                [
                    ['pgrep -U "$(id -u)" -f "bin/magento +(cron:run|queue:consumers:start)"', [], $processMock1],
                    ["kill 111", [], $processMock2],
                    ["kill 222", [], $processMock2],
                ]
            );
        $this->process->kill();
    }

    /**
     * Test situation when pgrep process returns code 1 because of no processes mathed
     */
    public function testExecuteWithNoRunningCrons()
    {
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('info')
            ->withConsecutive(
                ['Trying to kill running cron jobs and consumers processes'],
                ['Running Magento cron and consumers processes were not found.']
            );
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('pgrep -U "$(id -u)" -f "bin/magento +(cron:run|queue:consumers:start)"')
            ->willThrowException(new ShellException('return code 1', 1));

        $this->process->kill();
    }

    /**
     * Test situation when pgrep process returns error code
     */
    public function testExecuteWithError()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Trying to kill running cron jobs and consumers processes');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('pgrep -U "$(id -u)" -f "bin/magento +(cron:run|queue:consumers:start)"')
            ->willThrowException(new ShellException('return code 2', 2));
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Error happening during kill cron or consumers processes: return code 2');

        $this->process->kill();
    }

    /**
     * Check that if shell command returns error when killing the process - it is logged as info message
     *
     * @return void
     */
    public function testExecuteWithExeption()
    {
        $processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $processMock->expects($this->once())
            ->method('getOutput')
            ->willReturn("111");
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Trying to kill running cron jobs and consumers processes'],
                ['Couldn\'t kill process #111 it may be already finished']
            );
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with('some error');
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['pgrep -U "$(id -u)" -f "bin/magento +(cron:run|queue:consumers:start)"'],
                ['kill 111']
            )
            ->willReturnOnConsecutiveCalls(
                $processMock,
                $this->throwException(new ShellException('some error', 1))
            );

        $this->process->kill();
    }
}
