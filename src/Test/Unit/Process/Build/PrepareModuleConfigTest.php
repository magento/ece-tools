<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Shared;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Magento\MagentoCloud\Package\Manager;
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
     * @var SharedConfig|Mock
     */
    private $sharedConfigMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var Manager|Mock
     */
    private $managerMock;

    /**
     * @var ModuleInformation|Mock
     */
    private $moduleInformationMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->sharedConfigMock = $this->getMockBuilder(SharedConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();

        $this->managerMock = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->moduleInformationMock = $this->getMockBuilder(ModuleInformation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->process = new PrepareModuleConfig(
            $this->sharedConfigMock,
            $this->shellMock,
            $this->managerMock,
            $this->moduleInformationMock,
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

        $this->process->execute();
    }

    public function testExecuteWithNewModules()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Reconciling installed modules with shared config.'],
                ['Enabling newly installed modules not found in shared config.']
            );

        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Some_OtherModule' => 1]);

        $this->managerMock->expects($this->once())
            ->method('getRequiredPackageNames')
            ->willReturn(['magento/magento2-base', 'some/new-package']);

        $this->moduleInformationMock->expects($this->once())
            ->method('getModuleNameByPackage')
            ->willReturn('Some_NewModule');

        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php bin/magento module:enable Some_NewModule');

        $this->process->execute();
    }

    public function testExecuteWithNoNewModules()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Reconciling installed modules with shared config.'],
                ['All installed modules present in shared config.']
            );

        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Some_ExistingModule' => 1]);

        $this->managerMock->expects($this->once())
            ->method('getRequiredPackageNames')
            ->willReturn(['magento/magento2-base', 'some/existing-package']);

        $this->moduleInformationMock->expects($this->once())
            ->method('getModuleNameByPackage')
            ->willReturn('Some_ExistingModule');

        $this->process->execute();
    }
}
