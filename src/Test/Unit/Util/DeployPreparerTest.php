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
     * @param string $deployLogBasename
     * @param string $backupBuildLogBasename
     * @param bool $envIsChanged
     * @param $directoryListGetMagentoRootExpects
     * @param $environmentIsEnvironmentIdLabelExistWillReturn
     * @param $environmentSyncEnvironmentIdExpects
     * @param $fileFilePutContentsExpects
     * @param $fileCreateDirectoryExpects
     * @param $fileCopyExpects
     * @dataProvider prepareDataProvider
     */
    public function testPrepare(
        string $deployLogBasename,
        string $backupBuildLogBasename,
        bool $envIsChanged,
        $directoryListGetMagentoRootExpects,
        $environmentIsEnvironmentIdLabelExistWillReturn,
        $environmentSyncEnvironmentIdExpects,
        $fileFilePutContentsExpects,
        $fileCreateDirectoryExpects,
        $fileCopyExpects
    ) {
        $root = __DIR__ . '/_files';
        $deployLogPath = $root . '/var/log/' . $deployLogBasename;
        $backupBuildLogPath = $root . '/init/var/log/' . $backupBuildLogBasename;
        $this->loggerConfigMock->expects($this->once())
            ->method('getDeployLogPath')
            ->willReturn($deployLogPath);
        $this->loggerConfigMock->expects($this->once())
            ->method('getBackupBuildLogPath')
            ->willReturn($backupBuildLogPath);
        $this->environmentMock->expects($this->once())
            ->method('hasEnvironmentChanged')
            ->willReturn($envIsChanged);
        $this->directoryListMock->expects($directoryListGetMagentoRootExpects)
            ->method('getMagentoRoot')
            ->willReturn('/fake/path/to/var/log');
        $this->environmentMock->expects($this->once())
            ->method('isEnvironmentIdLabelExist')
            ->willReturn($environmentIsEnvironmentIdLabelExistWillReturn);
        $this->environmentMock->expects($environmentSyncEnvironmentIdExpects)
            ->method('syncEnvironmentId');
        $this->fileMock->expects($fileFilePutContentsExpects)
            ->method('filePutContents')
            ->with($deployLogPath, 'not applied logs', FILE_APPEND);
        $this->fileMock->expects($fileCreateDirectoryExpects)
            ->method('createDirectory')
            ->with(dirname($deployLogPath));
        $this->fileMock->expects($fileCopyExpects)
            ->method('copy')
            ->with($backupBuildLogPath, $deployLogPath);
        $this->deployPreparer->prepare();
    }

    public function prepareDataProvider()
    {
        return [
            [
                'deployLogBasename' => 'cloud.log',
                'backupBuildLogBasename' => 'cloud_applied.log',
                'envIsChanged' => true,
                'directoryListGetMagentoRootExpects' => $this->once(),
                'environmentIsEnvironmentIdLabelExistWillReturn' => true,
                'environmentSyncEnvironmentIdExpects' => $this->once(),
                'fileFilePutContentsExpects' => $this->never(),
                'fileCreateDirectoryExpects' => $this->never(),
                'fileCopyExpects' => $this->never()
            ],
            [
                'deployLogBasename' => 'cloud.log',
                'backupBuildLogBasename' => 'cloud_applied.log',
                'envIsChanged' => false,
                'directoryListGetMagentoRootExpects' => $this->never(),
                'environmentIsEnvironmentIdLabelExistWillReturn' => false,
                'environmentSyncEnvironmentIdExpects' => $this->once(),
                'fileFilePutContentsExpects' => $this->never(),
                'fileCreateDirectoryExpects' => $this->never(),
                'fileCopyExpects' => $this->never()
            ],
            [
                'deployLogBasename' => 'cloud.log',
                'backupBuildLogBasename' => 'cloud_not_applied.log',
                'envIsChanged' => false,
                'directoryListGetMagentoRootExpects' => $this->never(),
                'environmentIsEnvironmentIdLabelExistWillReturn' => true,
                'environmentSyncEnvironmentIdExpects' => $this->never(),
                'fileFilePutContentsExpects' => $this->once(),
                'fileCreateDirectoryExpects' => $this->never(),
                'fileCopyExpects' => $this->never()
            ],
            [
                'deployLogBasename' => 'wrong_cloud.log',
                'backupBuildLogBasename' => 'cloud_not_applied.log',
                'envIsChanged' => true,
                'directoryListGetMagentoRootExpects' => $this->once(),
                'environmentIsEnvironmentIdLabelExistWillReturn' => true,
                'environmentSyncEnvironmentIdExpects' => $this->once(),
                'fileFilePutContentsExpects' => $this->never(),
                'fileCreateDirectoryExpects' => $this->once(),
                'fileCopyExpects' => $this->once()
            ],
        ];
    }
}
