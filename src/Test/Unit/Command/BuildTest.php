<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class BuildTest extends TestCase
{
    /**
     * @var Build
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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();

        $this->command = new Build(
            $this->processMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting build.'],
                ['Building completed.']
            );
        $this->processMock->expects($this->once())
            ->method('execute');

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
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting build.');
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
