<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\CronUnlock;
use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Package\MagentoVersion;
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
     * @var JobUnlocker|Mock
     */
    private $jobUnlockerMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var CronUnlock
     */
    private $cronUnlockCommand;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->jobUnlockerMock = $this->createMock(JobUnlocker::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        
        $this->cronUnlockCommand = new CronUnlock(
            $this->jobUnlockerMock,
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    public function testExecute()
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        
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
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        
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
            '--job-code' => ['code1', 'code2']
        ]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Some error
     */
    public function testExecuteWithException()
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        
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
    }
    
    public function testSkipExecute()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);
            
        $this->magentoVersionMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.1.7');
        
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Unlocking crons is not supported in Magento 2.1.7.');
        
        $this->jobUnlockerMock->expects($this->never())
            ->method('unlockByJobCode');
        $this->jobUnlockerMock->expects($this->never())
            ->method('unlockAll');
        
        $tester = new CommandTester(
            $this->cronUnlockCommand
        );
        $tester->execute([]);
    }
}
