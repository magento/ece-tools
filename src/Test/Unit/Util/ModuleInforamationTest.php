<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\Shared;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Util\ModuleInformation;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ModuleInformationTest extends TestCase
{
    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var Shared|Mock
     */
    private $sharedConfigMock;

    /**
     * @var Manager|Mock
     */
    private $managerMock;

    /**
     * @var ModuleInformation
     */
    private $moduleInfo;

    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->sharedConfigMock = $this->createMock(Shared::class);
        $this->managerMock = $this->createMock(Manager::class);

        $this->moduleInfo = new ModuleInformation(
            $this->directoryListMock,
            $this->fileMock,
            $this->sharedConfigMock,
            $this->managerMock
        );
    }

    public function testGetModuleNameByPackageInstalledModule()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files');

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/_files/vendor/acme/exploditron/etc/module.xml')
            ->willReturn(true);

        $this->assertEquals(
            $this->moduleInfo->getModuleNameByPackage('acme/exploditron'),
            'Acme_Exploditron'
        );
    }

    public function testGetModuleNameByPackageNotInstalledModule()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/_files');

        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/_files/vendor/not/installed/etc/module.xml')
            ->willReturn(false);

        $this->assertEquals(
            $this->moduleInfo->getModuleNameByPackage('not/installed'),
            ''
        );
    }

    public function testGetThirdPartyModuleNamesAllMagento()
    {
        $packages = ['magento/module-shipping', 'magento/magento2-base'];

        $this->assertEquals(
            $this->moduleInfo->getThirdPartyModuleNames($packages),
            []
        );
    }

    /**
     * @dataProvider testGetNewModuleNamesProvider
     */
    public function testGetNewModuleNames($config, $packages, $new)
    {
        $this->sharedConfigMock->expects($this->once())
            ->method('get')
            ->with('modules')
            ->willReturn($config);
        $this->managerMock->expects($this->once())
            ->method('getRequiredPackageNames')
            ->willReturn($packages);
        if (!empty($new)) {
            $this->directoryListMock->expects($this->once())
                ->method('getMagentoRoot')
                ->willReturn(__DIR__ . '/_files');
            $this->fileMock->expects($this->once())
                ->method('isExists')
                ->with(__DIR__ . '/_files/vendor/acme/exploditron/etc/module.xml')
                ->willReturn(true);
        }

        $this->assertEquals(
            $this->moduleInfo->getNewModuleNames(),
            $new
        );
    }

    public function testGetNewModuleNamesProvider()
    {
        return [
            [
                'config' => ['Magento_Backend' => 1],
                'packages' => ['magento/module-backend'],
                'new' => [],
            ],
            [
                'config' => [],
                'packages' => ['not/anm2module'],
                'new' => [],
            ],
            [
                'config' => ['Magento_Backend' => 1],
                'packages' => ['acme/exploditron'],
                'new' => ['Acme_Exploditron'],
            ],
        ];
    }
}
