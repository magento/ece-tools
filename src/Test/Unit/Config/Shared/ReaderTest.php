<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Shared;

use Magento\MagentoCloud\Config\Shared\Reader;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ReaderTest extends TestCase
{
    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var FileList|Mock
     */
    private $fileListMock;

    /**
     * @var Reader
     */
    private $reader;

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
            ->method('getConfig')
            ->willReturn(__DIR__ . '/_file/app/etc/config.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/_file/app/etc/config.php')
            ->willReturn(true);

        $this->assertEquals(
            [
                'modules' => [
                    'Some_ModuleName' => 1,
                    'Another_Module' => 0
                ]
            ],
            $this->reader->read()
        );
    }

    public function testReadFileNotExists()
    {
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn('/path/to/file');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with('/path/to/file')
            ->willReturn(false);

        $this->assertEquals(
            [],
            $this->reader->read()
        );
    }
}
