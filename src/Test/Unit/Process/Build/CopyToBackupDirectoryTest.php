<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Build\CopyToBackupDirectory;
use Magento\MagentoCloud\Util\BackgroundDirectoryCleaner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CopyToBackupDirectoryTest extends TestCase
{
    /**
     * @var CopyToBackupDirectory
     */
    private $process;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @var BackgroundDirectoryCleaner|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cleanerMock;


    private $magentoRoot = 'magentoRoot';
    private $backupDir = 'magento_root/init';

    /**
     * @inheritdoc
     */
    protected function setUp()
    {

        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cleanerMock = $this->getMockBuilder(BackgroundDirectoryCleaner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($this->magentoRoot);

        $this->directoryListMock->expects($this->once())
            ->method('getPath')
            ->willReturn($this->backupDir);

        $this->environmentMock->expects($this->once())
            ->method('clearFlag')
            ->with(Environment::REGENERATE_FLAG);

        $this->process = new CopyToBackupDirectory(
            $this->fileMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->directoryListMock,
            $this->cleanerMock
        );
    }

    public function testProcess()
    {
        $dir = 'some_dir';
        $srcDir = $this->magentoRoot . "/$dir";
        $dstDir = $this->backupDir . "/$dir";

        $this->loggerMock->expects($this->exactly(4))
            ->method('info')
            ->withConsecutive(
                ['Copying restorable directories to backup directory.'],
                ["Reinitialize $srcDir"],
                ["Reinitialize $dstDir"],
                ["Copied $srcDir to $dstDir"]
            );

        $this->environmentMock->expects($this->once())
            ->method('getRestorableDirectories')
            ->willReturn(['test_dir' => $dir]);

        $this->fileMock->expects($this->exactly(2))
            ->method('createDirectory')
            ->withConsecutive(
                [$srcDir, Environment::DEFAULT_DIRECTORY_MODE],
                [$dstDir, Environment::DEFAULT_DIRECTORY_MODE]
            )
            ->willReturn(true);

        $this->fileMock->expects($this->once())
            ->method('scanDir')
            ->with($srcDir)
            ->willReturn(['thing1', 'thing2', 'thing3']);

        $this->fileMock->expects($this->once())
            ->method('copyDirectory')
            ->with($srcDir, $dstDir)
            ->willReturn(true);

        $this->cleanerMock->expects($this->once())
            ->method('backgroundClearDirectory')
            ->with($srcDir);

        $this->environmentMock->expects($this->once())
            ->method('hasFlag')
            ->with(Environment::STATIC_CONTENT_DEPLOY_FLAG)
            ->willReturn(true);

        $this->environmentMock->expects($this->once())
            ->method('setFlag')
            ->with($this->backupDir . '/' . Environment::STATIC_CONTENT_DEPLOY_FLAG);

        $this->process->execute();
    }

    public function testProcessNoWritableDirs()
    {
        $this->environmentMock->expects($this->once())
            ->method('clearFlag')
            ->with(Environment::REGENERATE_FLAG);
        $this->fileMock->expects($this->never())
            ->method('createDirectory');
        $this->fileMock->expects($this->never())
            ->method('deleteDirectory');
        $this->fileMock->expects($this->never())
            ->method('copyDirectory');
        $this->fileMock->expects($this->never())
            ->method('copy');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Copying restorable directories to backup directory.');
        $this->environmentMock->expects($this->once())
            ->method('getRestorableDirectories')
            ->willReturn([]);
        $this->fileMock->expects($this->never())
            ->method('scanDir');

        $this->process->execute();
    }
}
