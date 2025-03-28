<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\RestorePatchLog;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class RestorePatchLogTest extends TestCase
{
    const PATCH_LOG_PATH = 'path/patch.log';

    const INIT_PATCH_LOG_PATH = 'init/path/patch.log';

    /**
     * @var RestorePatchLog
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->step = new RestorePatchLog(
            $this->loggerMock,
            $this->directoryListMock,
            $this->fileMock,
            $this->fileListMock
        );
    }

    public function testExecute(): void
    {
        $this->fileListMock->expects($this->once())
            ->method('getInitPatchLog')
            ->willReturn(self::INIT_PATCH_LOG_PATH);
        $this->fileListMock->expects($this->once())
            ->method('getPatchLog')
            ->willReturn(self::PATCH_LOG_PATH);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(self::INIT_PATCH_LOG_PATH)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Restoring patch log file');
        $this->directoryListMock->expects($this->once())
            ->method('getLog')
            ->willReturn('/path/to/dir/log');
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with('/path/to/dir/log');
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with(self::INIT_PATCH_LOG_PATH)
            ->willReturn('content');
        $this->fileMock->expects($this->once())
            ->method('filePutContents')
            ->with(self::PATCH_LOG_PATH, 'content');

        $this->step->execute();
    }

    public function testExecutePatchLogFileNotExist(): void
    {
        $this->fileListMock->expects($this->once())
            ->method('getInitPatchLog')
            ->willReturn(self::INIT_PATCH_LOG_PATH);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(self::INIT_PATCH_LOG_PATH)
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('fileGetContents');
        $this->fileMock->expects($this->never())
            ->method('createDirectory');
        $this->fileMock->expects($this->never())
            ->method('filePutContents');

        $this->step->execute();
    }

    public function testExecuteWithUndefinedPackageException(): void
    {
        $this->fileListMock->expects($this->once())
            ->method('getInitPatchLog')
            ->willThrowException(new UndefinedPackageException(''));
        $this->fileMock->expects($this->never())
            ->method('isExists');

        $this->expectException(StepException::class);
        $this->step->execute();
    }

    public function testExecuteWithFilesystemException(): void
    {
        $this->fileListMock->expects($this->once())
            ->method('getInitPatchLog')
            ->willReturn(self::INIT_PATCH_LOG_PATH);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with(self::INIT_PATCH_LOG_PATH)
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willThrowException(new FileSystemException(''));

        $this->expectException(StepException::class);
        $this->step->execute();
    }
}
