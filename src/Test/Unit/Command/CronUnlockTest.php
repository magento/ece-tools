<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\CronUnlock;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class CronUnlockTest extends TestCase
{
    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var CronUnlock
     */
    private $cronUnlockCommand;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->cronUnlockCommand = new CronUnlock(
            $this->processMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting unlocking.'],
                ['Unlocking completed.']
            );
        $this->processMock->expects($this->once())
            ->method('execute');

        $tester = new CommandTester(
            $this->cronUnlockCommand
        );
        $tester->execute([]);

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
        $this->processMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Some error'));

        $tester = new CommandTester(
            $this->cronUnlockCommand
        );
        $tester->execute([]);
    }
}
