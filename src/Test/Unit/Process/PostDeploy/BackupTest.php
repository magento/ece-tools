<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PostDeploy;

use Magento\MagentoCloud\Process\PostDeploy\Backup;
use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class BackupTest extends TestCase
{
    /**
     * @var BackupList|Mock
     */
    private $backupListMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var Backup
     */
    private $backup;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->backupListMock = $this->createMock(BackupList::class);
        $this->fileMock = $this->createMock(File::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->backup = new Backup(
            $this->backupListMock,
            $this->fileMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $configPath = 'path/config.php';
        $envPath = 'path/env.php';
        $this->backupListMock->expects($this->once())
            ->method('getList')
            ->willReturn([
                'config.php' => $configPath,
                'env.php' => $envPath,
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                [$configPath, false],
                ['path/env.php', true],
            ]);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('File ' . $configPath . ' does not exist. Skipped.');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Create backup of important files.'],
                ['Backup ' . $envPath . BackupList::BACKUP_SUFFIX . ' for ' . $envPath . ' was created.']
            );
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with($envPath, $envPath . BackupList::BACKUP_SUFFIX);

        $this->backup->execute();
    }
}
