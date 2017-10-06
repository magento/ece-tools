<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Logger as LoggerConfig;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\DeployPreparer;
use PHPUnit\Framework\TestCase;

class DeployPreparerTest extends TestCase
{
    /**
     * @var DeployPreparer
     */
    private $deployPreparer;

    /**
     * @var LoggerConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerConfigMock;

    /**
     * @var File|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    protected function setUp()
    {
        $this->loggerConfigMock = $this->createMock(LoggerConfig::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->deployPreparer = new DeployPreparer(
            $this->loggerConfigMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->environmentMock
        );
    }

    /**
     * @param $fileFileGetContentsExpects
     * @param $buildLogContent
     * @param $deployLogContent
     * @param $fileIsExistsWillReturn
     * @param $fileFilePutContentsExpects
     * @param $fileCreateDirectoryExpects
     * @param $fileCopyExpects
     * @dataProvider prepareDataProvider
     */
    public function testPrepare(
        $fileFileGetContentsExpects,
        $buildLogContent,
        $deployLogContent,
        $fileIsExistsWillReturn,
        $fileFilePutContentsExpects,
        $fileCreateDirectoryExpects,
        $fileCopyExpects
    ) {
        $deployLogPath = 'path/to/deploy/cloud.log';
        $backupBuildLogPath = 'path/to/backup/build/cloud.log';
        $this->loggerConfigMock->expects($this->once())
            ->method('getDeployLogPath')
            ->willReturn($deployLogPath);
        $this->loggerConfigMock->expects($this->once())
            ->method('getBackupBuildLogPath')
            ->willReturn($backupBuildLogPath);
        $this->fileMock->expects($fileFileGetContentsExpects)
            ->method('fileGetContents')
            ->willReturnOnConsecutiveCalls($buildLogContent, $deployLogContent);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->willReturn($fileIsExistsWillReturn);
        $this->fileMock->expects($fileFilePutContentsExpects)
            ->method('filePutContents')
            ->with($deployLogPath, $buildLogContent, FILE_APPEND);
        $this->fileMock->expects($fileCreateDirectoryExpects)
            ->method('createDirectory')
            ->with(dirname($deployLogPath));
        $this->fileMock->expects($fileCopyExpects)
            ->method('copy')
            ->with($backupBuildLogPath, $deployLogPath);

        $this->deployPreparer->prepare();
    }

    /**
     * @return array
     */
    public function prepareDataProvider()
    {
        return [
            [
                'fileFileGetContentsExpects' => $this->once(),
                'buildLogContent' => 'build log is not applied',
                'deployLogContent' => null,
                'fileIsExistsWillReturn' => false,
                'fileFilePutContentsExpects' => $this->never(),
                'fileCreateDirectoryExpects' => $this->once(),
                'fileCopyExpects' => $this->once(),
            ],
            [
                'fileFileGetContentsExpects' => $this->exactly(2),
                'buildLogContent' => 'build log is applied',
                'deployLogContent' => 'some logs build log is applied some logs',
                'fileIsExistsWillReturn' => true,
                'fileFilePutContentsExpects' => $this->never(),
                'fileCreateDirectoryExpects' => $this->once(),
                'fileCopyExpects' => $this->once(),
            ],
            [
                'fileFileGetContentsExpects' => $this->exactly(2),
                'buildLogContent' => 'build log is not applied',
                'deployLogContent' => 'some logs build log is applied some logs',
                'fileIsExistsWillReturn' => true,
                'fileFilePutContentsExpects' => $this->once(),
                'fileCreateDirectoryExpects' => $this->never(),
                'fileCopyExpects' => $this->never(),
            ],
        ];
    }
}
