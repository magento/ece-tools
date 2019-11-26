<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Shared;

use Magento\MagentoCloud\Config\Shared\Reader;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ReaderTest extends TestCase
{
    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->reader = new Reader(
            $this->fileMock,
            $this->fileListMock
        );
    }

    public function testRead(): void
    {
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn(__DIR__ . '/_file/config.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/_file/config.php')
            ->willReturn(true);

        $this->assertEquals(
            [
                'modules' => [
                    'Some_ModuleName' => 1,
                    'Another_Module' => 0,
                ],
            ],
            $this->reader->read()
        );
    }

    public function testReadBroken(): void
    {
        $this->fileListMock->expects($this->once())
            ->method('getConfig')
            ->willReturn(__DIR__ . '/_file/config_broken.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/_file/config_broken.php')
            ->willReturn(true);

        $this->assertEquals([], $this->reader->read());
    }

    public function testReadFileNotExists(): void
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
