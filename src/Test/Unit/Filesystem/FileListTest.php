<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\SystemList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class FileListTest extends TestCase
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var SystemList|MockObject
     */
    private $systemListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->systemListMock = $this->createMock(SystemList::class);

        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->directoryListMock->expects($this->any())
            ->method('getRoot')
            ->willReturn('root');
        $this->directoryListMock->expects($this->any())
            ->method('getLog')
            ->willReturn('magento_root/var/log');
        $this->directoryListMock->expects($this->any())
            ->method('getInit')
            ->willReturn('magento_root/init');

        $this->fileList = new FileList(
            $this->directoryListMock,
            $this->systemListMock
        );
    }

    public function testGetCloudLog()
    {
        $this->assertSame('magento_root/var/log/cloud.log', $this->fileList->getCloudLog());
    }

    public function testGetInitCloudLog()
    {
        $this->assertSame('magento_root/init/var/log/cloud.log', $this->fileList->getInitCloudLog());
    }

    public function testGetPatches()
    {
        $this->assertSame('root/patches.json', $this->fileList->getPatches());
    }

    public function testGetInstallUpgradeLog()
    {
        $this->assertSame('magento_root/var/log/install_upgrade.log', $this->fileList->getInstallUpgradeLog());
    }

    public function testGetMagentoComposer()
    {
        $this->assertSame('magento_root/composer.json', $this->fileList->getMagentoComposer());
    }

    public function testGetMagentoDockerCompose()
    {
        $this->assertSame('magento_root/docker-compose.yml', $this->fileList->getMagentoDockerCompose());
    }

    public function testGetToolsDockerCompose()
    {
        $this->assertSame('root/docker-compose.yml', $this->fileList->getToolsDockerCompose());
    }

    public function testGetAppConfig()
    {
        $this->assertSame('magento_root/.magento.app.yaml', $this->fileList->getAppConfig());
    }

    public function testGetServicesConfig()
    {
        $this->assertSame('magento_root/.magento/services.yaml', $this->fileList->getServicesConfig());
    }

    public function testGetTtfbLog()
    {
        $this->assertSame('magento_root/var/log/ttfb_results.json', $this->fileList->getTtfbLog());
    }
}
