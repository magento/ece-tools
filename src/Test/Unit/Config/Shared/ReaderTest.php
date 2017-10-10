<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Shared;

use Magento\MagentoCloud\Config\Shared\Reader;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ReaderTest extends TestCase
{
    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var Reader
     */
    private $reader;

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
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/../_file/Shared');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/../_file/Shared/app/etc/config.php')
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
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/../_file/Shared');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/../_file/Shared/app/etc/config.php')
            ->willReturn(false);

        $this->assertEquals(
            [],
            $this->reader->read()
        );
    }


    public function testGetPath()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('/path');

        $this->assertEquals(
            '/path/app/etc/config.php',
            $this->reader->getPath()
        );
    }
}
