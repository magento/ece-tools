<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\CleanViewPreprocessed;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CleanViewPreprocessedTest extends TestCase
{
    /**
     * @var CleanViewPreprocessed
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
     * @var GlobalConfig|MockObject
     */
    private $globalConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->globalConfigMock = $this->createMock(GlobalConfig::class);

        $this->step = new CleanViewPreprocessed(
            $this->loggerMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->globalConfigMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecuteCopyingViewPreprocessedDir(): void
    {
        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->directoryListMock->expects($this->never())
            ->method('getPath');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteSkipCopyingViewPreprocessedDir(): void
    {
        $this->globalConfigMock->expects($this->once())
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithFileSystemException()
    {
        $this->expectExceptionCode(Error::DEPLOY_VIEW_PREPROCESSED_CLEAN_FAILED);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->willReturn(true);
        $this->fileMock->expects($this->once())
            ->method('backgroundClearDirectory')
            ->willThrowException(new FileSystemException('some error'));

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithGenericException()
    {
        $this->expectExceptionCode(Error::DEPLOY_CONFIG_NOT_DEFINED);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->globalConfigMock->expects($this->once())
            ->method('get')
            ->willThrowException(new ConfigException('some error', Error::DEPLOY_CONFIG_NOT_DEFINED));

        $this->step->execute();
    }
}
