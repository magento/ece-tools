<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\Deploy\PreDeploy\CleanStaticContent;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CleanStaticContentTest extends TestCase
{
    /**
     * @var CleanStaticContent
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);

        $this->step = new CleanStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->fileMock,
            $this->directoryListMock,
            $this->flagManagerMock,
            $this->stageConfigMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->stageConfigMock->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, true]
            ]);
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->once())
            ->method('backgroundClearDirectory')
            ->with('magento_root/pub/static');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Static content deployment was performed during build hook, cleaning old content.'],
                ['Clearing pub/static']
            );
        $this->environmentMock->method('hasMount')
            ->with(Environment::MOUNT_PUB_STATIC)
            ->willReturn(true);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithoutDeployInBuild(): void
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(false);
        $this->stageConfigMock->expects($this->never())
            ->method('get');
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory')
            ->with('magento_root/pub/static');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithDeployInBuildNoClean(): void
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->stageConfigMock->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, false]
            ]);
        $this->directoryListMock->expects($this->never())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects($this->never())
            ->method('backgroundClearDirectory')
            ->with('magento_root/pub/static');
        $this->environmentMock->method('hasMount')
            ->with(Environment::MOUNT_PUB_STATIC)
            ->willReturn(true);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithFileSystemException(): void
    {
        $this->expectExceptionCode(Error::DEPLOY_SCD_CLEAN_FAILED);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->stageConfigMock->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, true]
            ]);
        $this->fileMock->expects($this->once())
            ->method('backgroundClearDirectory')
            ->willThrowException(new FileSystemException('some error'));
        $this->environmentMock->method('hasMount')
            ->with(Environment::MOUNT_PUB_STATIC)
            ->willReturn(true);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithGenericException(): void
    {
        $this->expectExceptionCode(10);
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');

        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->willThrowException(new ConfigException('some error', 10));

        $this->step->execute();
    }

    public function testExecuteWithDeployInBuildCleanNoMoveScd(): void
    {
        $this->flagManagerMock->expects(self::once())
            ->method('exists')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
            ->willReturn(true);
        $this->stageConfigMock->method('get')
            ->willReturnMap([
                [DeployInterface::VAR_CLEAN_STATIC_FILES, true]
            ]);
        $this->directoryListMock->expects(self::never())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileMock->expects(self::never())
            ->method('backgroundClearDirectory')
            ->with('magento_root/pub/static');
        $this->environmentMock->method('hasMount')
            ->with(Environment::MOUNT_PUB_STATIC)
            ->willReturn(false);

        $this->step->execute();
    }
}
