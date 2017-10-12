<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\PreStart;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Package\Manager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class PreStartTest extends TestCase
{
    /**
     * @var PreStart
     */
    private $command;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ProcessInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processMock;

    /**
     * @var Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $managerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();
        $this->managerMock = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new PreStart(
            $this->processMock,
            $this->loggerMock,
            $this->managerMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting pre-start. Some info.'],
                ['Completed pre-start.']
            );
        $this->processMock->expects($this->once())
            ->method('execute');
        $this->managerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('Some info.');

        $tester = new CommandTester(
            $this->command
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
        $this->managerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('Some info.');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting pre-start. Some info.');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->processMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Some error'));

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
