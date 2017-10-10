<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\PreDeploy;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Package\Manager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class PreDeployTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Manager|Mock
     */
    private $packageManagerMock;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * @var PreDeploy
     */
    private $process;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->packageManagerMock = $this->createMock(Manager::class);
        $this->processMock = $this->getMockBuilder(ProcessInterface::class)
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);

        $this->process = new PreDeploy(
            $this->loggerMock,
            $this->processMock,
            $this->packageManagerMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    /**
     * @param $fileMockFileGetContentsExpects
     * @param $buildPhaseLogContent
     * @param $deployLogContent
     * @param $deployLogFileExists
     * @param $fileMockFilePutContentsExpects
     * @param $fileMockCopyExpects
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $fileMockFileGetContentsExpects,
        $buildPhaseLogContent,
        $deployLogContent,
        $deployLogFileExists,
        $fileMockFilePutContentsExpects,
        $fileMockCopyExpects
    ) {
        $magento_root = 'magento_root';
        $deployLogPath = $magento_root . '/' . Logger::DEPLOY_LOG_PATH;
        $buildPhaseLogPath = $magento_root . '/' . Logger::BACKUP_BUILD_PHASE_LOG_PATH;
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn($magento_root);
        $this->fileMock->expects($fileMockFileGetContentsExpects)
            ->method('fileGetContents')
            ->withConsecutive(
                [$buildPhaseLogPath],
                [$deployLogPath]
            )
            ->willReturnOnConsecutiveCalls($buildPhaseLogContent, $deployLogContent);
        $this->fileMock->expects($this->once())
            ->method('isExists')
            ->with($deployLogPath)
            ->willReturn($deployLogFileExists);
        $this->fileMock->expects($fileMockFilePutContentsExpects)
            ->method('filePutContents')
            ->with($deployLogPath, $buildPhaseLogContent, FILE_APPEND);
        $this->fileMock->expects($fileMockCopyExpects)
            ->method('copy')
            ->with($buildPhaseLogPath, $deployLogPath);

        $this->packageManagerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('(components info)');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting deploy.'],
                ['Starting pre-deploy. (components info)']
            );
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }

    public function executeDataProvider()
    {
        return [
            [
                'fileMockFileGetContentsExpects' => $this->once(),
                'buildPhaseLogContent' => 'the build phase log was not applied',
                'deployLogContent' => null,
                'deployLogFileExists' => false,
                'fileMockFilePutContentsExpects' => $this->never(),
                'fileMockCopyExpects' => $this->once()
            ],
            [
                'fileMockFileGetContentsExpects' => $this->exactly(2),
                'buildPhaseLogContent' => 'the build phase log was applied',
                'deployLogContent' => 'some log the build phase log was applied some log',
                'deployLogFileExists' => true,
                'fileMockFilePutContentsExpects' => $this->never(),
                'fileMockCopyExpects' => $this->never()
            ],
            [
                'fileMockFileGetContentsExpects' => $this->exactly(2),
                'buildPhaseLogContent' => 'the build phase log was not applied',
                'deployLogContent' => 'some log the build phase log was applied some log',
                'deployLogFileExists' => true,
                'fileMockFilePutContentsExpects' => $this->once(),
                'fileMockCopyExpects' => $this->never()
            ]
        ];
    }
}
