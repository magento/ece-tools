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
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');

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
}
