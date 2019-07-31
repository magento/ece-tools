<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\ConfigInterface;
use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
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
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->configMock = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);

        $this->module = new Module(
            $this->configMock,
            $shellFactoryMock
        );
    }

    public function testRefreshWithMissingModuleConfig()
    {
        $this->configMock->expects($this->exactly(2))
            ->method('get')
            ->with('modules')
            ->willReturnOnConsecutiveCalls(
                null,
                [
                    'Magento_Module1' => 1,
                    'Magento_Module2' => 1,
                ]
            );
        $this->configMock->expects($this->once())
            ->method('reset');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('module:enable --all');
        $this->configMock->expects($this->never())
            ->method('update');

        $this->assertEquals(
            [
                'Magento_Module1',
                'Magento_Module2',
            ],
            $this->module->refresh()
        );
    }

    public function testRefreshWithNewModules()
    {
        $this->configMock->expects($this->exactly(2))
            ->method('get')
            ->with('modules')
            ->willReturnOnConsecutiveCalls(
                [
                    'Magento_Module1' => 1,
                    'Magento_Module2' => 0,
                    'Magento_Module3' => 1,
                ],
                [
                    'Magento_Module1' => 1,
                    'Magento_Module2' => 1,
                    'Magento_Module3' => 1,
                    'Magento_Module4' => 1,
                    'Magento_Module5' => 1,
                ]
            );
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('module:enable --all');
        $this->configMock->expects($this->once())
            ->method('reset');
        $this->configMock->expects($this->once())
            ->method('update')
            ->with(['modules' => [
                'Magento_Module1' => 1,
                'Magento_Module2' => 0,
                'Magento_Module3' => 1,
            ]]);

        $this->assertEquals(
            [
                'Magento_Module4',
                'Magento_Module5',
            ],
            $this->module->refresh()
        );
    }
}
