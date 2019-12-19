<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Update\Setup;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetupTest extends TestCase
{
    /**
     * @var Setup
     */
    private $step;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->step = new Setup(
            $this->loggerMock,
            $this->environmentMock,
            $this->shellMock,
            $this->directoryListMock,
            $this->fileListMock,
            $this->flagManagerMock,
            $this->stageConfigMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->flagManagerMock->expects($this->exactly(2))
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['echo \'Updating time: \'$(date) | tee -a ' . $installUpgradeLog],
                ['/bin/bash -c "set -o pipefail; php ./bin/magento setup:upgrade '
                . '--keep-generated --ansi --no-interaction -v | tee -a '
                . $installUpgradeLog . '"']
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Running setup upgrade.');

        $this->step->execute();
    }

    public function testExecuteWithException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Error during command execution');

        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->shellMock->expects($this->at(0))
            ->method('execute')
            ->willThrowException(new \RuntimeException('Error during command execution'));

        $this->step->execute();
    }
}
