<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Process\Deploy\UnlockCronJobs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class UnlockCronJobsTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var JobUnlocker|MockObject
     */
    private $jobUnlockerMock;

    /**
     * @var UnlockCronJobs
     */
    private $process;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->jobUnlockerMock = $this->createMock(JobUnlocker::class);

        $this->process = new UnlockCronJobs(
            $this->jobUnlockerMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->jobUnlockerMock->expects($this->once())
            ->method('unlockAll')
            ->willReturn(5);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('5 cron jobs were updated from status "running" to status "error"');

        $this->process->execute();
    }

    public function testExecuteNoJobsUpdated()
    {
        $this->jobUnlockerMock->expects($this->once())
            ->method('unlockAll')
            ->willReturn(0);
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->process->execute();
    }
}
