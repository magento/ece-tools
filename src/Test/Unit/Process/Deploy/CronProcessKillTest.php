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
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with("pkill -f 'bin/magento cron:run'");
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->process->execute();
    }

    /**
     * Check that if shell command returns error - it is logged as warning message
     *
     * @return void
     */
    public function testExecuteWithExeption()
    {
        $errorMessage = 'pkill returns error code';
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Trying to kill running cron jobs');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with("pkill -f 'bin/magento cron:run'")
            ->willThrowException(new \RuntimeException($errorMessage));
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('There is an error during killing the cron processes: ' . $errorMessage);
        $this->process->execute();
    }
}
