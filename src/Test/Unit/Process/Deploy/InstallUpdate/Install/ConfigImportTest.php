<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\ConfigImport;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
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
    private $process;

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
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);

        $this->process = new ConfigImport(
            $shellFactoryMock,
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    public function testExecute()
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Run app:config:import command');
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('app:config:import');

        $this->process->execute();
    }

    public function testExecuteNotAvailable()
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->magentoShellMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
