<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\ConfigImport;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
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
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->process = new ConfigImport(
            $this->shellMock,
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    /**
     * return void
     */
    public function testExecute()
    {
        $this->magentoVersionMock->method('isGreaterOrEqual')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Run app:config:import command');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento app:config:import -n');

        $this->process->execute();
    }

    public function testIsAvailable()
    {
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);

        $this->assertSame(
            true,
            $this->process->isAvailable()
        );
    }

    public function testIsNotAvailable()
    {
        $this->loggerMock->expects($this->once())
            ->method('info');

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);

        $this->assertSame(
            false,
            $this->process->isAvailable()
        );
    }
}
