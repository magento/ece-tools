<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Cron;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Cron\JobUnlocker;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class JobUnlockerTest extends TestCase
{
    /**
     * @var ConnectionInterface|Mock
     */
    private $connectionMock;

    /**
     * @var JobUnlocker
     */
    private $cronJobUnlocker;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->cronJobUnlocker = new JobUnlocker($this->connectionMock);
    }

    public function testUnlockAll()
    {
        $this->connectionMock->expects($this->once())
            ->method('affectingQuery')
            ->with(
                'UPDATE `cron_schedule` SET `status` = :to_status, `messages` = :messages ' .
                'WHERE `status` = :from_status',
                [
                    ':to_status' => JobUnlocker::STATUS_ERROR,
                    ':from_status' => JobUnlocker::STATUS_RUNNING,
                    ':messages' => 'some message'
                ]
            )
            ->willReturn(3);
        $this->connectionMock->expects($this->once())
            ->method('getTableName')
            ->with('cron_schedule')
            ->willReturn('cron_schedule');

        $this->assertEquals(3, $this->cronJobUnlocker->unlockAll('some message'));
    }

    public function testUnlockByJobCode()
    {
        $this->connectionMock->expects($this->once())
            ->method('affectingQuery')
            ->with(
                'UPDATE `cron_schedule` SET `status` = :to_status, `messages` = :messages'
                    . ' WHERE `status` = :from_status AND `job_code` = :job_code',
                [
                    ':to_status' => JobUnlocker::STATUS_ERROR,
                    ':from_status' => JobUnlocker::STATUS_RUNNING,
                    ':job_code' => 'some_code',
                    ':messages' => 'some_message'
                ]
            )
            ->willReturn(3);
        $this->connectionMock->expects($this->once())
            ->method('getTableName')
            ->with('cron_schedule')
            ->willReturn('cron_schedule');

        $this->assertEquals(3, $this->cronJobUnlocker->unlockByJobCode('some_code', 'some_message'));
    }
}
