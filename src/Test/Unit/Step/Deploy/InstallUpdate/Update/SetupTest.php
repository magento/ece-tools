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
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Step\StepException;
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
    public function testExecuteWhenSkipUpgradeIsFalse()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_VERBOSE_COMMANDS],
                [DeployInterface::VAR_SKIP_UPGRADE_UNLESS_REQUIRED]
            )
            ->willReturnOnConsecutiveCalls(
                '-v',
                'false'
            );

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


    /**
     * @throws StepException
     */
    public function testExecuteWhenSkipUpgradeIsForce()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_VERBOSE_COMMANDS],
                [DeployInterface::VAR_SKIP_UPGRADE_UNLESS_REQUIRED]
            )
            ->willReturnOnConsecutiveCalls(
                '-v',
                'force'
            );

        $this->flagManagerMock->expects($this->exactly(2))
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->shellMock->expects($this->exactly(0))
            ->method('execute');
        $this->loggerMock->expects($this->exactly(0))
            ->method('info');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWhenSkipUpgradeIsTrueAndUpgradeRequired()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_VERBOSE_COMMANDS],
                [DeployInterface::VAR_SKIP_UPGRADE_UNLESS_REQUIRED]
            )
            ->willReturnOnConsecutiveCalls(
                '-v',
                'true'
            );

        $this->flagManagerMock->expects($this->exactly(2))
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);

        $this->shellMock->expects($this->at(0))
            ->method('execute')
            ->willThrowException(
                new \RuntimeException(
                    '<info>Run \'setup:upgrade\' to update your DB schema and data.</info>',
                    Setup::EXIT_CODE_UPGRADE_REQUIRED
                )
            );

        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['php ./bin/magento setup:db:status --ansi --no-interaction '],
                ['echo \'Updating time: \'$(date) | tee -a ' . $installUpgradeLog],
                ['/bin/bash -c "set -o pipefail; php ./bin/magento setup:upgrade '
                 . '--keep-generated --ansi --no-interaction -v | tee -a '
                 . $installUpgradeLog . '"']
            );

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Checking if setup:upgrade is required'],
                ['Running setup upgrade.']
            );

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWhenSkipUpgradeIsTrueAndUpgradeNotRequired()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_VERBOSE_COMMANDS],
                [DeployInterface::VAR_SKIP_UPGRADE_UNLESS_REQUIRED]
            )
            ->willReturnOnConsecutiveCalls(
                '-v',
                'true'
            );

        $this->flagManagerMock->expects($this->exactly(2))
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);

        $this->shellMock->expects($this->exactly(1))
            ->method('execute')
            ->withConsecutive(
                ['php ./bin/magento setup:db:status --ansi --no-interaction ']
            );

        $this->loggerMock->expects($this->exactly(1))
            ->method('info')
            ->withConsecutive(
                ['Checking if setup:upgrade is required']
            );

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWhenSkipUpgradeIsTrueAndUpgradeCheckFailsUnexpectedly()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_VERBOSE_COMMANDS],
                [DeployInterface::VAR_SKIP_UPGRADE_UNLESS_REQUIRED]
            )
            ->willReturnOnConsecutiveCalls(
                '-v',
                'true'
            );

        // Only once as code will throw exception
        $this->flagManagerMock->expects($this->exactly(1))
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);

        $this->shellMock->expects($this->at(0))
            ->method('execute')
            ->willThrowException(
                new \RuntimeException(
                    'Unknown Error',
                    9999
                )
            );

        // Thrown by RuntimeException
        $this->expectException(StepException::class);

        $this->shellMock->expects($this->exactly(1))
            ->method('execute')
            ->withConsecutive(
                ['php ./bin/magento setup:db:status --ansi --no-interaction ']
            );

        $this->loggerMock->expects($this->exactly(1))
            ->method('info')
            ->withConsecutive(
                ['Checking if setup:upgrade is required']
            );

        $this->step->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Step\StepException
     * @expectedExceptionMessage Error during command execution
     */
    public function testExecuteWithException()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->shellMock->expects($this->at(0))
            ->method('execute')
            ->willThrowException(new \RuntimeException('Error during command execution'));

        $this->step->execute();
    }
}
