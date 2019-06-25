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
        $modules = [
            'Magento_Module1',
            'Magento_Module2',
            'Magento_Module3',
        ];

        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Refreshing modules started.'],
                ['The following modules have been enabled:' . PHP_EOL . implode(PHP_EOL, $modules)],
                ['Refreshing modules completed.']
            );
        $this->configMock->expects($this->once())
            ->method('refresh')
            ->willReturn($modules);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }

    public function testExecuteNoModulesChanged()
    {
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Refreshing modules started.'],
                ['No modules were changed.'],
                ['Refreshing modules completed.']
            );
        $this->configMock->expects($this->once())
            ->method('refresh')
            ->willReturn([]);

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
