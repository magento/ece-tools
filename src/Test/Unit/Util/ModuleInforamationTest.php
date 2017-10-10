<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
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
     * @var ModuleInformation
     */
    private $moduleInfo;

    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->moduleInfo = new ModuleInformation(
            $this->directoryListMock,
            $this->fileMock
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
}
