<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Util\CronJobUnlocker;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class CronJobUnlockerTest extends TestCase
{
    /**
     * @var ConnectionInterface|Mock
     */
    private $connectionMock;

    /**
     * @var CronJobUnlocker
     */
    private $cronJobUnlocker;

    protected function setUp()
    {
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);

        $this->cronJobUnlocker = new CronJobUnlocker($this->connectionMock);
    }

    public function testUnlockAll()
    {
        $this->connectionMock->expects($this->once())
        ->method('affectingQuery')
        ->with(
            'UPDATE `cron_schedule` SET `status` = :to_status WHERE `status` = :from_status',
            [
                ':to_status' => CronJobUnlocker::STATUS_MISSED,
                ':from_status' => CronJobUnlocker::STATUS_RUNNING
            ]
        )
        ->willReturn(3);

        $this->assertEquals(3, $this->cronJobUnlocker->unlockAll());
    }

    public function testUnlockByJobCode()
    {
        $this->connectionMock->expects($this->once())
            ->method('affectingQuery')
            ->with(
                'UPDATE `cron_schedule` SET `status` = :to_status WHERE `status` = :from_status'
                . ' AND `job_code` = :job_code',
                [
                    ':to_status' => CronJobUnlocker::STATUS_MISSED,
                    ':from_status' => CronJobUnlocker::STATUS_RUNNING,
                    ':job_code' => 'some_code'
                ]
            )
            ->willReturn(3);

        $this->assertEquals(3, $this->cronJobUnlocker->unlockByJobCode('some_code'));
    }
}
