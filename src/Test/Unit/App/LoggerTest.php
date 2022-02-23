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
use Magento\MagentoCloud\Package\UndefinedPackageException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\App\Logger\Processor\SanitizeProcessor;

/**
 * @inheritdoc
 */
class LoggerTest extends TestCase
{
    use PHPMock;

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
    protected function setUp(): void
    {
        self::defineFunctionMock('Magento\MagentoCloud\App', 'shell_exec');

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
                [$buildPhaseLogPath, false, null, $buildPhaseLogContent]
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
        if ($buildLogFileExists && $deployLogFileExists) {
            $shellExecMock = $this->getFunctionMock(
                'Magento\MagentoCloud\App',
                'shell_exec'
            );
            $shellExecMock->expects($this->once())
                ->willReturn($fileMockFilePutContentsExpects ? null : 'some match');
        }

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
                'deployLogFileExists' => false,
                'fileMockFilePutContentsExpects' => 0,
                'fileMockCopyExpects' => 1,
            ],
            [
                'fileMockFileGetContentsExpects' => 1,
                'buildPhaseLogContent' => 'the build phase log was applied',
                'buildLogFileExists' => true,
                'deployLogFileExists' => true,
                'fileMockFilePutContentsExpects' => 0,
                'fileMockCopyExpects' => 0,
            ],
            [
                'fileMockFileGetContentsExpects' => 1,
                'buildPhaseLogContent' => 'the build phase log was not applied',
                'buildLogFileExists' => true,
                'deployLogFileExists' => true,
                'fileMockFilePutContentsExpects' => 1,
                'fileMockCopyExpects' => 0,
            ],
            [
                'fileMockFileGetContentsExpects' => 0,
                'buildPhaseLogContent' => '',
                'buildLogFileExists' => false,
                'deployLogFileExists' => true,
                'fileMockFilePutContentsExpects' => 0,
                'fileMockCopyExpects' => 0,
            ],
        ];
    }

    /**
     * @throws LoggerException
     */
    public function testWithLoggerException()
    {
        $this->expectException(LoggerException::class);
        $this->expectExceptionMessage('some error');

        $this->fileListMock->expects($this->once())
            ->method('getCloudLog')
            ->willThrowException(new UndefinedPackageException('some error'));

        new Logger(
            $this->fileMock,
            $this->directoryListMock,
            $this->fileListMock,
            $this->poolMock,
            $this->sanitizeProcessorMock,
            $this->errorLogFileMock
        );
    }
}
