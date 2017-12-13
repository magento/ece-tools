<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Shared;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Util\ModuleInformation;
use Magento\MagentoCloud\Process\Build\PrepareModuleConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PrepareModuleConfigTest extends TestCase
{
    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var SharedConfig|Mock
     */
    private $sharedConfigMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->sharedConfigMock = $this->createMock(SharedConfig::class);
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->process = new PrepareModuleConfig(
            $this->sharedConfigMock,
            $this->shellMock,
            $this->loggerMock
        );
    }

    public function testExecuteWithMissingModuleConfig()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Reconciling installed modules with shared config.'],
                ['Shared config file is missing module section. Updating with all installed modules.']
            );
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn([]);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php bin/magento module:enable --all');
        $this->sharedConfigMock->expects($this->never())
            ->method('update');

        $this->process->execute();
    }

    public function testExecuteWithNewModules()
    {
        $this->loggerMock->expects($this->exactly(1))
            ->method('info')
            ->withConsecutive(['Reconciling installed modules with shared config.']);
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Some_OtherModule' => 1]);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php bin/magento module:enable --all');
        $this->sharedConfigMock->expects($this->any())
            ->method('read')
            ->willReturn(['modules' => ['Some_ExistingModule' => 1]]);
        $this->sharedConfigMock->expects($this->any())
            ->method('update')
            ->willReturn(null);

        $this->process->execute();
    }

    public function testExecuteWithNoNewModules()
    {
        $this->loggerMock->expects($this->exactly(1))
            ->method('info')
            ->withConsecutive(['Reconciling installed modules with shared config.']);
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Some_ExistingModule' => 1]);
        $this->sharedConfigMock->expects($this->any())
            ->method('read')
            ->willReturn(['modules' => ['Some_ExistingModule' => 1]]);
        $this->sharedConfigMock->expects($this->any())
            ->method('update')
            ->willReturn(null);

        $this->process->execute();
    }
}
