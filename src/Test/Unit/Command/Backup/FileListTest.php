<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Backup;

use Magento\MagentoCloud\Command\Backup\FileList;
use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class FileListTest extends TestCase
{
    /**
     * @var BackupList|Mock
     */
    private $backupListMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->backupListMock = $this->createMock(BackupList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->fileList = new FileList($this->backupListMock, $this->fileMock);
    }

    public function testGet()
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
