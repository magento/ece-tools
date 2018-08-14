<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\ConfigImport;
use Magento\MagentoCloud\Shell\ExecBinMagento;
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
     * @var ExecBinMagento|MockObject
     */
    private $shellMock;

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
        $this->shellMock = $this->createMock(ExecBinMagento::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->process = new ConfigImport(
            $this->shellMock,
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
        $this->shellMock->expects($this->once())
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
        $this->shellMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }
}
