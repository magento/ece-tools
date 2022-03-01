<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Backup;

use Magento\MagentoCloud\Command\Backup\Restore;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RestoreTest extends TestCase
{
    /**
     * @var BackupList|MockObject
     */
    private $backupListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var Restore
     */
    private $restore;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->backupListMock = $this->createMock(BackupList::class);
        $this->fileMock = $this->createMock(File::class);

        $this->restore = new Restore($this->backupListMock, $this->fileMock);
    }

    /**
     * @param int $getOptionExpects
     * @param string $fileOption
     * @param bool $forceOption
     * @param int $isExistsExpects
     * @param bool $backupExists
     * @param bool $fileExists
     * @param int $copyExpects
     * @param string $writeLnMsg
     * @dataProvider runDataProvider
     */
    public function testRun(
        int $getOptionExpects,
        string $fileOption,
        bool $forceOption,
        int $isExistsExpects,
        bool $backupExists,
        bool $fileExists,
        int $copyExpects,
        string $writeLnMsg
    ) {
        $aliasPath = 'config.php';
        $filePath = 'path/config.php';
        $backupPath = $filePath . BackupList::BACKUP_SUFFIX;

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockBuilder(InputInterface::class)
            ->getMockForAbstractClass();
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockBuilder(OutputInterface::class)
            ->getMockForAbstractClass();

        $inputMock->expects($this->exactly($getOptionExpects))
            ->method('getOption')
            ->willReturnMap([
                ['file', $fileOption],
                ['force', $forceOption],
            ]);
        $this->backupListMock->expects($this->once())
            ->method('getList')
            ->willReturn([$aliasPath => $filePath]);
        $this->fileMock->expects($this->exactly($isExistsExpects))
            ->method('isExists')
            ->willReturnMap([
                [$backupPath, $backupExists],
                [$filePath, $fileExists],
            ]);
        $this->fileMock->expects($this->exactly($copyExpects))
            ->method('copy')
            ->with($backupPath, $filePath);
        $outputMock->expects($this->once())
            ->method('writeln')
            ->with($writeLnMsg);

        $this->restore->run($inputMock, $outputMock);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function runDataProvider(): array
    {
        return [
            [
                'getOptionExpects' => 2,
                'fileOption' => '',
                'forceOption' => false,
                'isExistsExpects' => 2,
                'backupExists' => true,
                'fileExists' => true,
                'copyExpects' => 0,
                'writeLnMsg' => '<info>config.php file exists!</info>'
                    . ' <comment>If you want to rewrite existed files use --force</comment>',
            ],
            [
                'getOptionExpects' => 2,
                'fileOption' => '',
                'forceOption' => true,
                'isExistsExpects' => 2,
                'backupExists' => true,
                'fileExists' => true,
                'copyExpects' => 1,
                'writeLnMsg' => '<info>Backup file config.php was restored.</info>',
            ],
            [
                'getOptionExpects' => 1,
                'fileOption' => '',
                'forceOption' => true,
                'isExistsExpects' => 2,
                'backupExists' => true,
                'fileExists' => false,
                'copyExpects' => 1,
                'writeLnMsg' => '<info>Backup file config.php was restored.</info>',
            ],
            [
                'getOptionExpects' => 1,
                'fileOption' => '',
                'forceOption' => false,
                'isExistsExpects' => 2,
                'backupExists' => true,
                'fileExists' => false,
                'copyExpects' => 1,
                'writeLnMsg' => '<info>Backup file config.php was restored.</info>',
            ],
            [
                'getOptionExpects' => 1,
                'fileOption' => '',
                'forceOption' => false,
                'isExistsExpects' => 1,
                'backupExists' => false,
                'fileExists' => false,
                'copyExpects' => 0,
                'writeLnMsg' => '<info>Backup for config.php does not exist.</info> <comment>Skipped.</comment>',
            ],
            [
                'getOptionExpects' => 2,
                'fileOption' => 'config.php',
                'forceOption' => false,
                'isExistsExpects' => 2,
                'backupExists' => true,
                'fileExists' => true,
                'copyExpects' => 0,
                'writeLnMsg' => '<info>config.php file exists!</info>'
                    . ' <comment>If you want to rewrite existed files use --force</comment>',
            ],
            [
                'getOptionExpects' => 2,
                'fileOption' => 'config.php',
                'forceOption' => true,
                'isExistsExpects' => 2,
                'backupExists' => true,
                'fileExists' => true,
                'copyExpects' => 1,
                'writeLnMsg' => '<info>Backup file config.php was restored.</info>',
            ],
            [
                'getOptionExpects' => 1,
                'fileOption' => 'config.php',
                'forceOption' => true,
                'isExistsExpects' => 2,
                'backupExists' => true,
                'fileExists' => false,
                'copyExpects' => 1,
                'writeLnMsg' => '<info>Backup file config.php was restored.</info>',
            ],
            [
                'getOptionExpects' => 1,
                'fileOption' => 'config.php',
                'forceOption' => false,
                'isExistsExpects' => 2,
                'backupExists' => true,
                'fileExists' => false,
                'copyExpects' => 1,
                'writeLnMsg' => '<info>Backup file config.php was restored.</info>',
            ],
            [
                'getOptionExpects' => 1,
                'fileOption' => 'config.php',
                'forceOption' => false,
                'isExistsExpects' => 1,
                'backupExists' => false,
                'fileExists' => false,
                'copyExpects' => 0,
                'writeLnMsg' => '<info>Backup for config.php does not exist.</info> <comment>Skipped.</comment>',
            ],
            [
                'getOptionExpects' => 1,
                'fileOption' => 'some.php',
                'forceOption' => false,
                'isExistsExpects' => 0,
                'backupExists' => false,
                'fileExists' => false,
                'copyExpects' => 0,
                'writeLnMsg' => '<error>There is no some.php file in the backup list.</error>'
                    . ' <comment>Run backup:list to show files from backup list.</comment>',
            ],
        ];
    }
}
