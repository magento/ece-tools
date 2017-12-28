<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\CronUnlock;
use Magento\MagentoCloud\Util\CronJobUnlocker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class CronUnlockTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var CronJobUnlocker|Mock
     */
    private $cronJobUnlockerMock;

    /**
     * @var CronUnlock
     */
    private $cronUnlockCommand;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->cronJobUnlockerMock = $this->createMock(CronJobUnlocker::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->cronUnlockCommand = new CronUnlock(
            $this->cronJobUnlockerMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Starting unlocking.'],
                ['Unlocking all cron jobs..'],
                ['Unlocking completed.']
            );
        $this->cronJobUnlockerMock->expects($this->once())
            ->method('unlockAll');
        $this->cronJobUnlockerMock->expects($this->never())
            ->method('unlockByJobCode');

        $tester = new CommandTester(
            $this->cronUnlockCommand
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteWithJobCode()
    {
        $this->loggerMock->expects($this->exactly(4))
            ->method('info')
            ->withConsecutive(
                ['Starting unlocking.'],
                ['Unlocking cron jobs with job_code #code1.'],
                ['Unlocking cron jobs with job_code #code2.'],
                ['Unlocking completed.']
            );
        $this->cronJobUnlockerMock->expects($this->never())
            ->method('unlockAll');
        $this->cronJobUnlockerMock->expects($this->exactly(2))
            ->method('unlockByJobCode')
            ->withConsecutive(
                ['code1'],
                ['code2']
            );

        $tester = new CommandTester(
            $this->cronUnlockCommand
        );
        $tester->execute([
            '--job_code' => ['code1', 'code2']
        ]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Some error
     */
    public function testExecuteWithException()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting unlocking.');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->cronJobUnlockerMock->expects($this->once())
            ->method('unlockAll')
            ->willThrowException(new \Exception('Some error'));

        $tester = new CommandTester(
            $this->cronUnlockCommand
        );
        $tester->execute([]);
    }
}
