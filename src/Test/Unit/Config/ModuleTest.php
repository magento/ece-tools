<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\ConfigInterface;
use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ModuleTest extends TestCase
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @var ConfigInterface|MockObject
     */
    private $configMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->configMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);

        $this->module = new Module(
            $this->configMock,
            $this->shellMock
        );
    }

    public function testRefreshWithMissingModuleConfig()
    {
        $this->configMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(null);
        $this->configMock->expects($this->once())
            ->method('reset');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento module:enable --all --ansi --no-interaction');
        $this->configMock->expects($this->never())
            ->method('update');

        $this->module->refresh();
    }

    public function testRefreshWithNewModules()
    {
        $this->configMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Some_OtherModule' => 1]);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento module:enable --all --ansi --no-interaction');
        $this->configMock->expects($this->never())
            ->method('reset');
        $this->configMock->expects($this->once())
            ->method('update')
            ->with(['modules' => ['Some_OtherModule' => 1]])
            ->willReturn(null);

        $this->module->refresh();
    }

    public function testRefreshWithNoNewModules()
    {
        $this->configMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn(['Some_ExistingModule' => 1]);
        $this->configMock->expects($this->any())
            ->method('all')
            ->willReturn(['modules' => ['Some_ExistingModule' => 1]]);
        $this->configMock->expects($this->any())
            ->method('update')
            ->willReturn(null);

        $this->module->refresh();
    }
}
