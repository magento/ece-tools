<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Build;

use Magento\MagentoCloud\Config\Build\Reader;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ReaderTest extends TestCase
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->reader = new Reader(
            $this->fileMock,
            $this->directoryListMock
        );
    }

    public function testRead()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->method('isExists')
            ->with('magento_root/build_options.ini')
            ->willReturn(true);
        $this->fileMock->method('parseIni')
            ->with('magento_root/build_options.ini')
            ->willReturn(['some_data']);

        $this->assertSame(['some_data'], $this->reader->read());
    }

    public function testReadNoFile()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->method('isExists')
            ->with('magento_root/build_options.ini')
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('parseIni');

        $this->assertSame([], $this->reader->read());
    }

    public function testGetPath()
    {
        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');

        $this->assertSame(
            'magento_root/build_options.ini',
            $this->reader->getPath()
        );
    }
}
