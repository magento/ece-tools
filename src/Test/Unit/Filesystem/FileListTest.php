<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Filesystem;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileList;
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
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->directoryListMock->expects($this->any())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->directoryListMock->expects($this->any())
            ->method('getLog')
            ->willReturn('magento_root/var/log');
        $this->directoryListMock->expects($this->any())
            ->method('getInit')
            ->willReturn('magento_root/init');

        $this->fileList = new FileList(
            $this->directoryListMock
        );
    }

    public function testGetConfig()
    {
        $this->assertSame('magento_root/app/etc/config.php', $this->fileList->getConfig());
    }

    public function testGetEnv()
    {
        $this->assertSame('magento_root/app/etc/env.php', $this->fileList->getEnv());
    }

    public function testGetBuildConfig()
    {
        $this->assertSame('magento_root/build_options.ini', $this->fileList->getBuildConfig());
    }

    public function testGetComposer()
    {
        $this->assertSame('magento_root/composer.json', $this->fileList->getComposer());
    }

    public function testGetToolsConfig()
    {
        $this->assertSame('magento_root/.magento.env.yaml', $this->fileList->getEnvConfig());
    }

    public function testGetCloudLog()
    {
        $this->assertSame('magento_root/var/log/cloud.log', $this->fileList->getCloudLog());
    }

    public function testGetInitCloudLog()
    {
        $this->assertSame('magento_root/init/var/log/cloud.log', $this->fileList->getInitCloudLog());
    }
}
