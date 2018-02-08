<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Deploy;

use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
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
            ->method('getEnv')
            ->willReturn(__DIR__ . '/../_file/Deploy/app/etc/env.php');
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
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn(__DIR__ . '/../_file/Deploy/app/etc/env.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/../_file/Deploy/app/etc/env.php')
            ->willReturn(false);

        $this->assertEquals(
            [],
            $this->reader->read()
        );
    }
}
