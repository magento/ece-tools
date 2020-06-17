<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\App;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\App\Logger\Prepare\ErrorLogFile;
use Magento\MagentoCloud\App\LoggerException;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\App\Logger\Pool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\App\Logger\Processor\SanitizeProcessor;

/**
 * @inheritdoc
 */
class LoggerTest extends TestCase
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
     * @var Pool|MockObject
     */
    private $poolMock;

    /**
     * @var SanitizeProcessor|MockObject
     */
    private $sanitizeProcessorMock;

    /**
     * @var ErrorLogFile|MockObject
     */
    private $errorLogFileMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->poolMock = $this->createMock(Pool::class);
        $this->sanitizeProcessorMock = $this->createMock(SanitizeProcessor::class);
        $this->errorLogFileMock = $this->createMock(ErrorLogFile::class);
    }

    /**
     * @param int $fileMockFileGetContentsExpects
     * @param string $buildPhaseLogContent
     * @param bool $buildLogFileExists
     * @param string $deployLogContent
     * @param bool $deployLogFileExists
     * @param int $fileMockFilePutContentsExpects
     * @param int $fileMockCopyExpects
     * @dataProvider executeDataProvider
     *
     * @throws LoggerException
     */
    public function testExecute(
        $fileMockFileGetContentsExpects,
        $buildPhaseLogContent,
        $buildLogFileExists,
        $deployLogContent,
        $deployLogFileExists,
        $fileMockFilePutContentsExpects,
        $fileMockCopyExpects
    ): void {
        $magentoRoot = 'magento_root';
        $deployLogPath = $magentoRoot . '/var/log/cloud.log';
        $buildPhaseLogPath = $magentoRoot . '/init/var/log/cloud.log';

        $this->fileListMock->expects($this->once())
            ->method('getCloudLog')
            ->willReturn($deployLogPath);
        $this->fileListMock->expects($this->once())
            ->method('getInitCloudLog')
            ->willReturn($buildPhaseLogPath);
        $this->directoryListMock->expects($this->once())
            ->method('getLog')
            ->willReturn($magentoRoot);
        $this->fileMock->expects($this->once())
            ->method('createDirectory')
            ->with($magentoRoot);
        $this->fileMock->expects($this->exactly($fileMockFileGetContentsExpects))
            ->method('fileGetContents')
            ->willReturnMap([
                [$buildPhaseLogPath, false, null, $buildPhaseLogContent],
                [$deployLogPath, false, null, $deployLogContent],
            ]);
        $this->fileMock->expects($this->exactly(2))
            ->method('isExists')
            ->willReturnMap([
                [$buildPhaseLogPath, $buildLogFileExists],
                [$deployLogPath, $deployLogFileExists],
            ]);
        $this->fileMock->expects($this->exactly($fileMockFilePutContentsExpects))
            ->method('filePutContents')
            ->with($deployLogPath, $buildPhaseLogContent, FILE_APPEND);
        $this->fileMock->expects($this->exactly($fileMockCopyExpects))
            ->method('copy')
            ->with($buildPhaseLogPath, $deployLogPath);

        $this->poolMock->expects($this->once())
            ->method('getHandlers')
            ->willReturn([]);

        new Logger(
            $this->fileMock,
            $this->directoryListMock,
            $this->fileListMock,
            $this->poolMock,
            $this->sanitizeProcessorMock,
            $this->errorLogFileMock
        );
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                'fileMockFileGetContentsExpects' => 1,
                'buildPhaseLogContent' => 'the build phase log was not applied',
                'buildLogFileExists' => true,
                'deployLogContent' => null,
                'deployLogFileExists' => false,
                'fileMockFilePutContentsExpects' => 0,
                'fileMockCopyExpects' => 1,
            ],
            [
                'fileMockFileGetContentsExpects' => 2,
                'buildPhaseLogContent' => 'the build phase log was applied',
                'buildLogFileExists' => true,
                'deployLogContent' => 'some log the build phase log was applied some log',
                'deployLogFileExists' => true,
                'fileMockFilePutContentsExpects' => 0,
                'fileMockCopyExpects' => 0,
            ],
            [
                'fileMockFileGetContentsExpects' => 2,
                'buildPhaseLogContent' => 'the build phase log was not applied',
                'buildLogFileExists' => true,
                'deployLogContent' => 'some log the build phase log was applied some log',
                'deployLogFileExists' => true,
                'fileMockFilePutContentsExpects' => 1,
                'fileMockCopyExpects' => 0,
            ],
            [
                'fileMockFileGetContentsExpects' => 0,
                'buildPhaseLogContent' => '',
                'buildLogFileExists' => false,
                'deployLogContent' => 'some log the build phase log was applied some log',
                'deployLogFileExists' => true,
                'fileMockFilePutContentsExpects' => 0,
                'fileMockCopyExpects' => 0,
            ],
        ];
    }
}
