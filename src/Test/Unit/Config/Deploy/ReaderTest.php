<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Deploy;

use Magento\MagentoCloud\Config\Deploy\Reader;
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
            ->willReturn(__DIR__ . '/../_file/Deploy');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/../_file/Deploy/app/etc/env.php')
            ->willReturn(true);

        $this->assertEquals(
            [
                'install' => [
                    'date' => 'Wed, 12 Sep 2017 10:40:30 +0000'
                ]
            ],
            $this->reader->read()
        );
    }

    public function testReadFileNotExists()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn(__DIR__ . '/../_file/Deploy');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/../_file/Deploy/app/etc/env.php')
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
            '/path/app/etc/env.php',
            $this->reader->getPath()
        );
    }
}
