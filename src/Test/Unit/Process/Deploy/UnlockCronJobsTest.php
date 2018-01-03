<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Process\Deploy\UnlockCronJobs;
use Magento\MagentoCloud\Cron\JobUnlocker;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class UnlockCronJobsTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var JobUnlocker|Mock
     */
    private $jobUnlockerMock;

    /**
     * @var UnlockCronJobs
     */
    private $process;

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
        $this->updateCronJobs(5);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('5 cron jobs were updated from status "running" to status "error"');

        $this->process->execute();
    }

    public function testExecuteNoJobsUpdated()
    {
        $this->updateCronJobs(0);
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->process->execute();
    }

    /**
     * @param int $updatedRowsCount
     */
    private function updateCronJobs(int $updatedRowsCount)
    {
        $this->jobUnlockerMock->expects($this->once())
            ->method('unlockAll')
            ->willReturn($updatedRowsCount);
    }
}
