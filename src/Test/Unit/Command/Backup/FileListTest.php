<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Backup;

use Magento\MagentoCloud\Command\Backup\FileList;
use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var BackupList|MockObject
     */
    private $backupListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->backupListMock = $this->createMock(BackupList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->fileList = new FileList($this->backupListMock, $this->fileMock);
    }

    public function testGet(): void
    {
        $this->backupListMock->expects($this->once())
            ->method('getList')
            ->willReturn([
                'config.php' => 'path/config.php',
                'env.php' => 'path/env.php',
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                ['path/config.php' . BackupList::BACKUP_SUFFIX, false],
                ['path/env.php' . BackupList::BACKUP_SUFFIX, true],
            ]);

        $this->assertSame(['env.php'], $this->fileList->get());
    }
}
