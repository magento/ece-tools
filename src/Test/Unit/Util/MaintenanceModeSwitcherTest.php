<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class MaintenanceModeSwitcherTest extends TestCase
{
    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var MaintenanceModeSwitcher
     */
    private $maintenanceModeSwitcher;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->fileMock = $this->createMock(File::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);

        $this->maintenanceModeSwitcher = new MaintenanceModeSwitcher(
            $shellFactoryMock,
            $this->loggerMock,
            $this->stageConfigMock,
            $this->fileMock,
            $this->directoryListMock
        );
    }

    /**
     * @throws GenericException
     */
    public function testEnable()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Enabling Maintenance mode');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('maintenance:enable', ['-v']);
        $this->loggerMock->expects($this->never())
            ->method('warning');
        $this->fileMock->expects($this->never())
            ->method('touch');
        $this->directoryListMock->expects($this->never())
            ->method('getVar');

        $this->maintenanceModeSwitcher->enable();
    }

    /**
     * @throws GenericException
     */
    public function testEnableCommandException()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Enabling Maintenance mode');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('maintenance:enable', ['-v'])
            ->willThrowException(new ShellException('command error'));
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Command maintenance:enable finished with an error. Creating a maintenance flag file manually.');
        $this->directoryListMock->expects($this->once())
            ->method('getVar')
            ->willReturn('/var');
        $this->fileMock->expects($this->once())
            ->method('touch')
            ->with('/var/.maintenance.flag');

        $this->maintenanceModeSwitcher->enable();
    }

    /**
     * @throws GenericException
     */
    public function testDisable()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Maintenance mode is disabled.');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('maintenance:disable', ['-v']);

        $this->maintenanceModeSwitcher->disable();
    }

    public function testDisableCommandException()
    {
        $this->expectException(GenericException::class);
        $this->expectExceptionMessage('command error');

        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
        $this->loggerMock->expects($this->never())
            ->method('notice');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('maintenance:disable', ['-v'])
            ->willThrowException(new ShellException('command error'));

        $this->maintenanceModeSwitcher->disable();
    }
}
