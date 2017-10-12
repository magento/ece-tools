<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Process\Build\ClearBackupDirectory;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ClearBackupDirectoryTest extends TestCase
{
    /**
     * @var ClearBackupDirectory
     */
    private $process;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->process = new ClearBackupDirectory(
            $this->fileMock,
            $this->directoryListMock,
            $this->loggerMock
        );
    }

    /**
     * @param bool $isExists
     * @param int $clearDirectory
     * @param int $deleteFile
     * @dataProvider executeDataProvider
     */
    public function testExecute($isExists, $clearDirectory, $deleteFile)
    {
        $magentoRoot = 'magento_root';
        $backupDir = 'magento_root/init';
        $envPhpPath = 'magento_root/app/etc/env.php';

        if ($isExists) {
            $this->loggerMock->expects($this->exactly(2))
                ->method('info')
                ->withConsecutive(
                    ["Clearing backup directory: $backupDir"],
                    ['Deleting env.php']
                );
        }
        $this->directoryListMock->expects($this->once())
            ->method('getPath')
            ->with('backup')
            ->willReturn($backupDir);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magentoRoot);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                [$backupDir, $isExists],
                [$envPhpPath, $isExists]
            ]);
        $this->fileMock->expects($this->exactly($clearDirectory))
            ->method('clearDirectory')
            ->with($backupDir)
            ->willReturn(true);
        $this->fileMock->expects($this->exactly($deleteFile))
            ->method('deleteFile')
            ->with($envPhpPath)
            ->willReturn(true);

        $this->process->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            ['isExists' => true, 'clearDirectory' => 1, 'deleteFile' => 1],
            ['isExists' => false, 'clearDirectory' => 0, 'deleteFile' => 0],
        ];
    }
}
