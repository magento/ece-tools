<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\ModuleRefresh;
use Magento\MagentoCloud\Config\Module;
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
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Module|MockObject
     */
    private $configMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configMock = $this->createMock(Module::class);

        $this->command = new ModuleRefresh(
            $this->loggerMock,
            $this->configMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Refreshing modules started.'],
                ['Refreshing modules completed.']
            );
        $this->configMock->expects($this->once())
            ->method('refresh');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Some exception
     */
    public function testExecuteWithException()
    {
        $this->configMock->expects($this->once())
            ->method('refresh')
            ->willThrowException(new \RuntimeException('Some exception'));
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some exception');

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
