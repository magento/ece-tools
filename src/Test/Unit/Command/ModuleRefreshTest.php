<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\ModuleRefresh;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class ModuleRefreshTest extends TestCase
{
    /**
     * @var ModuleRefresh
     */
    private $command;

    /**
     * @var ProcessInterface|MockObject
     */
    private $processMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->command = new ModuleRefresh(
            $this->processMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->processMock->expects($this->once())
            ->method('execute');
        $this->loggerMock->expects($this->never())
            ->method('critical');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage Some error
     */
    public function testExecuteWithException()
    {
        $this->processMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new ProcessException('Some error'));
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
