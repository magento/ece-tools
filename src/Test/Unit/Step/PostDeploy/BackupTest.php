<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\PostDeploy;

use Magento\MagentoCloud\Step\PostDeploy\Backup;
use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class BackupTest extends TestCase
{
    /**
     * @var Backup
     */
    private $backup;

    /**
     * @var BackupList|MockObject
     */
    private $backupListMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

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
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->backup = new Backup(
            $this->backupListMock,
            $this->fileMock,
            $this->loggerMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
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
                ['Successfully created backup ' . $envPath . BackupList::BACKUP_SUFFIX . ' for ' . $envPath . '.']
            );
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with($envPath, $envPath . BackupList::BACKUP_SUFFIX)
            ->willReturn(true);

        $this->backup->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteFailed()
    {
        $envPath = 'path/env.php';
        $this->backupListMock->expects($this->once())
            ->method('getList')
            ->willReturn([
                'env.php' => $envPath,
            ]);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);

        $this->fileMock->expects($this->once())
            ->method('copy')
            ->willReturn(false);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Failed to create backup ' . $envPath . BackupList::BACKUP_SUFFIX . ' for ' . $envPath . '.');

        $this->backup->execute();
    }
}
