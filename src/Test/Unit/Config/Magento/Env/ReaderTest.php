<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Magento\Env;

use Magento\MagentoCloud\Config\Magento\Env\Reader;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
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
            ->method('getEnv')
            ->willReturn(__DIR__ . '/../../_file/Deploy/app/etc/env.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/../../_file/Deploy/app/etc/env.php')
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

    public function testReadFileNotExists(): void
    {
        $this->fileListMock->expects($this->once())
            ->method('getEnv')
            ->willReturn(__DIR__ . '/../../_file/Deploy/app/etc/env.php');
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(__DIR__ . '/../../_file/Deploy/app/etc/env.php')
            ->willReturn(false);

        $this->assertEquals(
            [],
            $this->reader->read()
        );
    }
}
