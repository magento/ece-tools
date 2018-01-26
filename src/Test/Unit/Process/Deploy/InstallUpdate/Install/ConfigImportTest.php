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

class ConfigImportTest extends TestCase
{
    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigImport
     */
    private $configImport;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->configImport = new ConfigImport($this->shellMock, $this->loggerMock, $this->magentoVersionMock);
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

        $this->configImport->execute();
    }
    
    public function testSkipExecute()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);

        $this->magentoVersionMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.1.7');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Importing config is not supported in Magento 2.1.7, skipping.');
        $this->shellMock->expects($this->never())
            ->method('execute');
        
        $this->configImport->execute();
    }
}
