<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\CleanViewPreprocessed;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CleanViewPreprocessedTest extends TestCase
{
    /**
     * @var CleanViewPreprocessed
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var DirectoryList|Mock
     */
    private $directoryListMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info'])
            ->getMockForAbstractClass();
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new CleanViewPreprocessed(
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->stageConfigMock
        );
    }

    public function testExecuteCopyingViewPreprocessedDir()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->directoryListMock->expects($this->never())
            ->method('getPath');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->process->execute();
    }

    public function testExecuteSkipCopyingViewPreprocessedDir()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Skip copying directory ./var/view_preprocessed.'],
                ['Clearing ./var/view_preprocessed']
            );
        $this->directoryListMock->expects($this->once())
            ->method('getPath')
            ->willReturn('magento_root/var/view_preprocessed');

        $this->fileMock->expects($this->once())
            ->method('backgroundClearDirectory')
        ->with('magento_root/var/view_preprocessed');

        $this->process->execute();
    }
}
