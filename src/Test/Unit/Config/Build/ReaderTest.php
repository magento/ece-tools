<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Build;

use Magento\MagentoCloud\Config\Build\Reader;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;

/**
 * {@inheritdoc}
 *
 * @deprecated
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
     * @var FileList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->reader = new Reader(
            $this->fileMock,
            $this->fileListMock
        );
    }

    public function testRead()
    {
        $this->fileListMock->expects($this->once())
            ->method('getBuildConfig')
            ->willReturn('magento_root/build_options.ini');
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
        $this->fileListMock->expects($this->once())
            ->method('getBuildConfig')
            ->willReturn('magento_root/build_options.ini');
        $this->fileMock->method('isExists')
            ->with('magento_root/build_options.ini')
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('parseIni');

        $this->assertSame([], $this->reader->read());
    }
}
