<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install\ConfigImport;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ConfigImportTest extends TestCase
{
    /**
     * @var ConfigImport
     */
    private $step;

    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->stageConfigMock = $this->createMock(DeployInterface::class);

        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);

        $this->step = new ConfigImport(
            $shellFactoryMock,
            $this->loggerMock,
            $this->magentoVersionMock,
            $this->stageConfigMock
        );
    }

    public function testExecute()
    {
        $this->stageConfigMock->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Run app:config:import command');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('app:config:import', ['-vvv']);

        $this->step->execute();
    }

    public function testExecuteNotAvailable()
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->magentoShellMock->expects($this->never())
            ->method('execute');

        $this->step->execute();
    }

    public function testExecuteWithShellException()
    {
        $this->stageConfigMock->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-vvv');
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new ShellException('some error'));

        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_CONFIG_IMPORT_COMMAND_FAILED);
        $this->expectExceptionMessage('some error');

        $this->step->execute();
    }

    public function testExecuteWithConfigException()
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->stageConfigMock->method('get')
            ->willThrowException(new ConfigException('some error', 10));

        $this->expectException(StepException::class);
        $this->expectExceptionCode(10);
        $this->expectExceptionMessage('some error');

        $this->step->execute();
    }
}
