<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\CloudPatches\Config\Config;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Session;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class SessionTest extends TestCase
{
    /**
     * @var Session
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|Mock
     */
    private $configReaderMock;

    /**
     * @var Session\Config|Mock
     */
    private $sessionConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->sessionConfigMock = $this->createMock(Session\Config::class);

        $this->process = new Session(
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->sessionConfigMock
        );
    }

    public function testExecute()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->sessionConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'save' => 'redis',
                'redis' => [
                    'host' => 'redis_host'
                ]
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['session' => [
                'save' => 'redis',
                'redis' => [
                    'host' => 'redis_host'
                ]
            ]]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating session configuration.');

        $this->process->execute();
    }

    public function testExecuteEmptyConfig()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['session' => ['save' => 'redis']]);
        $this->sessionConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['session' => ['save' => 'db']]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Removing session configuration from env.php.');

        $this->process->execute();
    }
}
