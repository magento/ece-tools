<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Process\Deploy\UnlockCronJobs;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\DB\ConnectionInterface;

class UnlockCronJobsTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConnectionInterface|Mock
     */
    private $connectionMock;

    /**
     * @var UnlockCronJobs
     */
    private $process;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->process = new UnlockCronJobs(
            $this->connectionMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->updateCronJobs(5);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('5 cron jobs was updated from status running to status missed');

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
        $this->connectionMock->expects($this->once())
            ->method('affectingQuery')
            ->with(
                'UPDATE `cron_schedule` SET `status` = :to_status WHERE `status` = :from_status',
                [
                    ':to_status' => UnlockCronJobs::STATUS_MISSED,
                    ':from_status' => UnlockCronJobs::STATUS_RUNNING
                ]
            )
            ->willReturn($updatedRowsCount);
    }
}
