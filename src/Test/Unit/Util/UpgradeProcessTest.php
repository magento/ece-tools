<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Shell\UtilityManager;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Update\Setup;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Util\UpgradeProcess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class UpgradeProcessTest extends TestCase
{

    /**
     * @var Setup
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var UtilityManager|MockObject
     */
    private $utilityManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->shellMock = $this->createMock(ShellInterface::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);
        $this->utilityManagerMock = $this->createMock(UtilityManager::class);

        $this->step = new UpgradeProcess(
            $this->loggerMock,
            $this->shellMock,
            $this->fileListMock,
            $this->stageConfigMock,
            $this->utilityManagerMock
        );
    }

    /**
     * @throws StepException
     * @throws ConfigException
     * @throws ConfigurationMismatchException
     * @throws UndefinedPackageException
     */
    public function testExecute()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Running setup upgrade.');
        $this->utilityManagerMock->expects($this->once())
            ->method('get')
            ->with(UtilityManager::UTILITY_SHELL)
            ->willReturn('/bin/bash');
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            // withConsecutive() alternative.
            ->with(self::callback(function (string $message) use ($installUpgradeLog) {
                static $i = 0;
                return match (++$i) {
                    1 => $message === 'echo \'Updating time: \'$(date) | tee -a ' . $installUpgradeLog,
                    2 => $message === '/bin/bash -c "set -o pipefail; php ./bin/magento setup:upgrade '
                    . '--keep-generated --ansi --no-interaction -v | tee -a '
                    . $installUpgradeLog . '"',
                };
            }));

        $this->step->execute();
    }
}
