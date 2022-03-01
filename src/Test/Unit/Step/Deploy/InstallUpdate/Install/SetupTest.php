<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Shell\UtilityException;
use Magento\MagentoCloud\Shell\UtilityManager;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install\Setup;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install\Setup\InstallCommandFactory;
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
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var InstallCommandFactory|MockObject
     */
    private $installCommandFactoryMock;

    /**
     * @var UtilityManager|MockObject
     */
    private $utilityManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->installCommandFactoryMock = $this->createMock(InstallCommandFactory::class);
        $this->utilityManagerMock = $this->createMock(UtilityManager::class);

        $this->step = new Setup(
            $this->loggerMock,
            $this->shellMock,
            $this->fileListMock,
            $this->installCommandFactoryMock,
            $this->utilityManagerMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Installing Magento.');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->installCommandFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn('magento install command');
        $this->utilityManagerMock->expects($this->once())
            ->method('get')
            ->with(UtilityManager::UTILITY_SHELL)
            ->willReturn('/bin/bash');

        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['echo \'Installation time: \'$(date) | tee -a ' . $installUpgradeLog],
                ['/bin/bash -c "set -o pipefail; magento install command | tee -a /tmp/log.log"']
            );

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithShellException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('script error');
        $this->expectExceptionCode(Error::DEPLOY_INSTALL_COMMAND_FAILED);

        $installUpgradeLog = '/tmp/log.log';

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Installing Magento.');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->installCommandFactoryMock->expects($this->never())
            ->method('create');
        $this->shellMock->method('execute')
            ->willThrowException(new ShellException('script error'));

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithUtilityException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('script error');
        $this->expectExceptionCode(Error::DEPLOY_UTILITY_NOT_FOUND);

        $this->shellMock->method('execute')
            ->willThrowException(new UtilityException('script error'));

        $this->step->execute();
    }
}
