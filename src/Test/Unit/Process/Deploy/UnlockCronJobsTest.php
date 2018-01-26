<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\UnlockCronJobs;
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
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var UnlockCronJobs
     */
    private $process;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->jobUnlockerMock = $this->createMock(JobUnlocker::class);

        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        
        $this->process = new UnlockCronJobs(
            $this->jobUnlockerMock,
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    public function testExecute()
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        
        $this->updateCronJobs(5);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('5 cron jobs were updated from status "running" to status "error"');

        $this->process->execute();
    }

    public function testExecuteNoJobsUpdated()
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        
        $this->updateCronJobs(0);
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->process->execute();
    }

    public function testSkipExecute()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);
        
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);
            
        $this->magentoVersionMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.1.7');
        
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Unlocking cron jobs is not supported in Magento 2.1.7, skipping.');
        
        $this->jobUnlockerMock->expects($this->never())
            ->method('unlockAll');
        
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
