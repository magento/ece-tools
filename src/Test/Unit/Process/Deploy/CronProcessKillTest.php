<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Process\Deploy\CronProcessKill;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test for Magento\MagentoCloud\Process\Deploy\CronProcessKill process
 */
class CronProcessKillTest extends TestCase
{
    /**
     * @var CronProcessKill
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
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->createMock(ShellInterface::class);

        $this->process = new CronProcessKill(
            $this->loggerMock,
            $this->shellMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Trying to kill running cron jobs');
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturnMap(
                [
                    ['exec pgrep -U "$(id -u)" -f "bin/magento cron:run"', [], [111, 222]],
                    ["kill 111", [], []],
                    ["kill 222", [], []],
                ]
            );
        $this->process->execute();
    }

    /**
     * Test situation when pgrep process returns code 1 because of no processes mathed
     */
    public function testExecuteWithNoRunningCrons()
    {
        $this->loggerMock->expects($this->atLeastOnce())
            ->method('info')
            ->withConsecutive(
                ['Trying to kill running cron jobs'],
                ['Running Magento cron processes were not found.']
            );
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('exec pgrep -U "$(id -u)" -f "bin/magento cron:run"')
            ->willThrowException(new \RuntimeException('return code 1', 1));
        $this->process->execute();
    }

    /**
     * Test situation when pgrep process returns error code
     */
    public function testExecuteWithError()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Trying to kill running cron jobs');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('exec pgrep -U "$(id -u)" -f "bin/magento cron:run"')
            ->willThrowException(new \RuntimeException('return code 2', 2));
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Error happening during kill cron: return code 2');

        $this->process->execute();
    }

    /**
     * Check that if shell command returns error when killing the process - it is logged as info message
     *
     * @return void
     */
    public function testExecuteWithExeption()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Trying to kill running cron jobs'],
                ['There is an error during killing the cron processes: some error']
            );
        $this->shellMock->expects($this->at(0))
            ->method('execute')
            ->with('exec pgrep -U "$(id -u)" -f "bin/magento cron:run"')
            ->willReturn([111, 222]);
        $this->shellMock->expects($this->at(1))
            ->method('execute')
            ->with("kill 111")
            ->willThrowException(new \RuntimeException('some error', 1));
        $this->process->execute();
    }
}
