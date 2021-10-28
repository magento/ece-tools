<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App\Logger\Prepare;

use Magento\MagentoCloud\App\Logger\Prepare\ErrorLogFile;
use Magento\MagentoCloud\App\LoggerException;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ErrorLogFileTest extends TestCase
{
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
     * @var ErrorLogFile
     */
    private $errorLogFile;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);

        $this->errorLogFile = new ErrorLogFile(
            $this->fileMock,
            $this->directoryListMock,
            $this->fileListMock
        );
    }

    public function testUndefinedPackageException()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getLog')
            ->willThrowException(new UndefinedPackageException('some error', 19));
        $this->fileMock->expects($this->never())
            ->method($this->anything());

        $this->expectExceptionCode(19);
        $this->expectExceptionMessage('some error');
        $this->expectException(LoggerException::class);

        $this->errorLogFile->prepare();
    }

    public function testBuildLogFileNotExists()
    {
        $this->defaultMocks();
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(false);
        $this->fileMock->expects($this->never())
            ->method('copy');

        $this->errorLogFile->prepare();
    }

    public function testBuildLogFileEmpty()
    {
        $this->defaultMocks();
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->with('/init/var/log/cloud.error.log')
            ->willReturn('');
        $this->fileMock->expects($this->never())
            ->method('copy');

        $this->errorLogFile->prepare();
    }

    public function testDeployLogFileNotExists()
    {
        $this->defaultMocks();
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('content');
        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with('/init/var/log/cloud.error.log', '/var/log/cloud.error.log');

        $this->errorLogFile->prepare();
    }

    public function testBuildLogContentNotInDeployLog()
    {
        $this->defaultMocks();
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetContents')
            ->withConsecutive(['/init/var/log/cloud.error.log'], ['/var/log/cloud.error.log'])
            ->willReturnOnConsecutiveCalls('some build log', 'some deploy log');

        $this->fileMock->expects($this->once())
            ->method('copy')
            ->with('/init/var/log/cloud.error.log', '/var/log/cloud.error.log');

        $this->errorLogFile->prepare();
    }

    public function testBuildLogContentInDeployLog()
    {
        $this->defaultMocks();
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturn(true);
        $this->fileMock->expects($this->exactly(2))
            ->method('fileGetContents')
            ->withConsecutive(['/init/var/log/cloud.error.log'], ['/var/log/cloud.error.log'])
            ->willReturnOnConsecutiveCalls('some build log', 'some build log, some deploy log');

        $this->fileMock->expects($this->never())
            ->method('copy');

        $this->errorLogFile->prepare();
    }

    private function defaultMocks()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getLog')
            ->willReturn('/var/log/');
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with('/var/log/');
        $this->fileListMock->expects($this->once())
            ->method('getCloudErrorLog')
            ->willReturn('/var/log/cloud.error.log');
        $this->fileListMock->expects($this->once())
            ->method('getInitCloudErrorLog')
            ->willReturn('/init/var/log/cloud.error.log');
    }
}
