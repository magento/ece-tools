<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\PreStart;

use Magento\MagentoCloud\Process\PreStart\RestoreFromBuild;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Util\BackgroundDirectoryCleaner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class RestoreFromBuildTest extends TestCase
{
    /**
     * @var RestoreFromBuild
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var BackgroundDirectoryCleaner|Mock
     */
    private $cleanerMock;

    private $magentoRoot;
    private $backupDir;
    private $localDir;
    private $cloudFlagsDir;
    private $staticDir;
    private $etcDir;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->directoryListMock = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cleanerMock = $this->getMockBuilder(BackgroundDirectoryCleaner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->magentoRoot = 'magento_root';
        $this->backupDir = 'magento_root/init';
        $this->localDir = 'magento_root/local';
        $this->cloudFlagsDir = 'var/cloud_flags';
        $this->staticDir = 'pub/static';
        $this->etcDir = 'app/etc';


        $this->process = new RestoreFromBuild(
            $this->environmentMock,
            $this->loggerMock,
            $this->shellMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->cleanerMock
        );

        parent::setUp();
    }

    public function testExecuteAlreadyCompleted()
    {
        $this->environmentMock->expects($this->once())
            ->method('hasFlag')
            ->with(Environment::DEPLOY_READY_FLAG)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Environment is ready for deployment. Aborting pre-start.');

        $this->process->execute();
    }

    public function testStaticToLocal()
    {

        $restoreFlagsMsg = 'Copied ' . $this->backupDir . '/' . $this->cloudFlagsDir
            . ' to ' . $this->magentoRoot . '/' . $this->cloudFlagsDir;

        $this->environmentMock->expects($this->once())
            ->method('hasFlag')
            ->with(Environment::DEPLOY_READY_FLAG)
            ->willReturn(false);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($this->magentoRoot);
        $this->environmentMock->expects($this->once())
            ->method('getRestorableDirectories')
            ->willReturn(['static' => $this->staticDir, 'cloud_flags' => 'var/cloud_flags']);
        $this->directoryListMock->expects($this->exactly(2))
            ->method('getPath')
            ->withConsecutive(['backup'], ['local'])
            ->willReturnOnConsecutiveCalls($this->backupDir, $this->localDir);
        $this->fileMock->expects($this->once())
            ->method('isDirectory')
            ->with($this->magentoRoot . '/' .$this->cloudFlagsDir)
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Restoring recoverable data from backup.'],
                [$restoreFlagsMsg]
            );
        $this->environmentMock->expects($this->exactly(2))
            ->method('setFlag')
            ->withConsecutive([Environment::PRE_START_FLAG], [Environment::DEPLOY_READY_FLAG]);

        $this->fileMock->expects($this->once())
            ->method('isWritable')
            ->with($this->localDir)
            ->willReturn(true);
        $this->cleanerMock->expects($this->once())
            ->method('backgroundDeleteDirectory')
            ->with($this->localDir . '/' . $this->staticDir);
        $this->cleanerMock->expects($this->once())
            ->method('backgroundClearDirectory')
            ->with($this->magentoRoot . '/' . $this->staticDir);
        $this->fileMock->expects($this->exactly(2))
            ->method('copyDirectory')
            ->withConsecutive(
                [$this->backupDir . '/' . $this->staticDir, $this->localDir . '/' . $this->staticDir],
                [$this->backupDir . '/' . $this->cloudFlagsDir, $this->magentoRoot . '/' . $this->cloudFlagsDir]
            );
        $this->environmentMock->expects($this->once())
            ->method('symlinkDirectoryContents')
            ->with($this->localDir . '/' . $this->staticDir, $this->magentoRoot . '/' . $this->staticDir);

        $this->environmentMock->expects($this->once())
            ->method('clearFlag')
            ->with(Environment::PRE_START_FLAG);

        $this->process->execute();
    }

    public function testStaticToBackupSymlinked()
    {

        $restoreFlagsMsg = 'Copied ' . $this->backupDir . '/' . $this->cloudFlagsDir
            . ' to ' . $this->magentoRoot . '/' . $this->cloudFlagsDir;

        $this->environmentMock->expects($this->once())
            ->method('hasFlag')
            ->with(Environment::DEPLOY_READY_FLAG)
            ->willReturn(false);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($this->magentoRoot);
        $this->environmentMock->expects($this->once())
            ->method('getRestorableDirectories')
            ->willReturn(['static' => $this->staticDir, 'cloud_flags' => 'var/cloud_flags']);
        $this->directoryListMock->expects($this->exactly(2))
            ->method('getPath')
            ->withConsecutive(['backup'], ['local'])
            ->willReturnOnConsecutiveCalls($this->backupDir, $this->localDir);
        $this->fileMock->expects($this->once())
            ->method('isDirectory')
            ->with($this->magentoRoot . '/' .$this->cloudFlagsDir)
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Restoring recoverable data from backup.'],
                [$restoreFlagsMsg]
            );
        $this->environmentMock->expects($this->exactly(2))
            ->method('setFlag')
            ->withConsecutive([Environment::PRE_START_FLAG], [Environment::DEPLOY_READY_FLAG]);

        $this->fileMock->expects($this->once())
            ->method('isWritable')
            ->with($this->localDir)
            ->willReturn(false);
        $this->environmentMock->expects($this->once())
            ->method('isVariableDisabled')
            ->with('STATIC_CONTENT_SYMLINK')
            ->willReturn(false);
        $this->cleanerMock->expects($this->once())
            ->method('backgroundClearDirectory')
            ->with($this->magentoRoot . '/' . $this->staticDir);

        $this->environmentMock->expects($this->once())
            ->method('symlinkDirectoryContents')
            ->with($this->backupDir . '/' . $this->staticDir, $this->magentoRoot . '/' . $this->staticDir);

        $this->fileMock->expects($this->exactly(1))
            ->method('copyDirectory')
            ->with($this->backupDir . '/' . $this->cloudFlagsDir, $this->magentoRoot . '/' . $this->cloudFlagsDir);

        $this->environmentMock->expects($this->once())
            ->method('clearFlag')
            ->with(Environment::PRE_START_FLAG);

        $this->process->execute();
    }

    public function testNormalRestoration()
    {
        $restoreFlagsMsg1 = 'Copied ' . $this->backupDir . '/' . $this->etcDir
            . ' to ' . $this->magentoRoot . '/' . $this->etcDir;
        $restoreFlagsMsg2 = 'Copied ' . $this->backupDir . '/' . $this->cloudFlagsDir
            . ' to ' . $this->magentoRoot . '/' . $this->cloudFlagsDir;

        $this->environmentMock->expects($this->once())
            ->method('hasFlag')
            ->with(Environment::DEPLOY_READY_FLAG)
            ->willReturn(false);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($this->magentoRoot);
        $this->environmentMock->expects($this->once())
            ->method('getRestorableDirectories')
            ->willReturn(['etc' => $this->etcDir, 'cloud_flags' => 'var/cloud_flags']);
        $this->directoryListMock->expects($this->once())
            ->method('getPath')
            ->with('backup')
            ->willReturn($this->backupDir);
        $this->fileMock->expects($this->once())
            ->method('isDirectory')
            ->with($this->magentoRoot . '/' .$this->cloudFlagsDir)
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['Restoring recoverable data from backup.'],
                [$restoreFlagsMsg1],
                [$restoreFlagsMsg2]
            );
        $this->environmentMock->expects($this->exactly(2))
            ->method('setFlag')
            ->withConsecutive([Environment::PRE_START_FLAG], [Environment::DEPLOY_READY_FLAG]);

        $this->fileMock->expects($this->exactly(2))
            ->method('copyDirectory')
            ->withConsecutive(
                [$this->backupDir . '/' . $this->etcDir, $this->magentoRoot . '/' . $this->etcDir],
                [$this->backupDir . '/' . $this->cloudFlagsDir, $this->magentoRoot . '/' . $this->cloudFlagsDir]
            );

        $this->environmentMock->expects($this->once())
            ->method('clearFlag')
            ->with(Environment::PRE_START_FLAG);

        $this->process->execute();
    }
}
