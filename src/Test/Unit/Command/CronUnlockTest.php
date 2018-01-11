<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
use Magento\MagentoCloud\Command\CronUnlock;
use Magento\MagentoCloud\Cron\JobUnlocker;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class CronUnlockTest extends TestCase
{
    /**
     * @var CronUnlock
     */
    private $cronUnlockCommand;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var JobUnlocker|Mock
     */
    private $jobUnlockerMock;

    /**
     * @var Wrapper|Mock
     */
    private $wrapperMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->jobUnlockerMock = $this->createMock(JobUnlocker::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->wrapperMock = $this->createTestProxy(Wrapper::class, [$this->loggerMock]);

        $this->cronUnlockCommand = new CronUnlock(
            $this->jobUnlockerMock,
            $this->loggerMock,
            $this->wrapperMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Starting unlocking.'],
                ['Unlocking all cron jobs.'],
                ['Unlocking completed.']
            );
        $this->jobUnlockerMock->expects($this->once())
            ->method('unlockAll')
            ->with(CronUnlock::UNLOCK_MESSAGE);
        $this->jobUnlockerMock->expects($this->never())
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
                ['Unlocking cron jobs with code #code1.'],
                ['Unlocking cron jobs with code #code2.'],
                ['Unlocking completed.']
            );
        $this->jobUnlockerMock->expects($this->never())
            ->method('unlockAll');
        $this->jobUnlockerMock->expects($this->exactly(2))
            ->method('unlockByJobCode')
            ->withConsecutive(
                ['code1', CronUnlock::UNLOCK_MESSAGE],
                ['code2', CronUnlock::UNLOCK_MESSAGE]
            );

        $tester = new CommandTester(
            $this->cronUnlockCommand
        );
        $tester->execute([
            '--job-code' => ['code1', 'code2'],
        ]);

        $this->assertSame(Wrapper::CODE_SUCCESS, $tester->getStatusCode());
    }

    public function testExecuteWithException()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting unlocking.');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->jobUnlockerMock->expects($this->once())
            ->method('unlockAll')
            ->willThrowException(new \Exception('Some error'));

        $tester = new CommandTester(
            $this->cronUnlockCommand
        );
        $tester->execute([]);

        $this->assertSame(Wrapper::CODE_FAILURE, $tester->getStatusCode());
        $this->assertContains('Some error', $tester->getDisplay());
    }
}
