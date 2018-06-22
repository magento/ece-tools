<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\Config;

use Magento\MagentoCloud\Config\Log;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\Deploy\Config\Syslog;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SyslogTest extends TestCase
{
    /**
     * @var Syslog
     */
    private $process;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Log|MockObject
     */
    private $logConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->connectionMock = $this->getMockForAbstractClass(ConnectionInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->logConfigMock = $this->createMock(Log::class);

        $this->process = new Syslog(
            $this->connectionMock,
            $this->loggerMock,
            $this->logConfigMock
        );
    }

    public function testExecute()
    {
        $this->logConfigMock->expects($this->once())
            ->method('has')
            ->with(Log::HANDLER_SYSLOG)
            ->willReturn(true);
        $this->connectionMock->expects($this->once())
            ->method('affectingQuery')
            ->with(
                "UPDATE `core_config_data` SET `value` = 1 WHERE `path` = 'dev/syslog/syslog_logging'"
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Enabling syslog logging');

        $this->process->execute();
    }

    public function testExecuteToBeDisabled()
    {
        $this->logConfigMock->expects($this->once())
            ->method('has')
            ->with(Log::HANDLER_SYSLOG)
            ->willReturn(false);
        $this->connectionMock->expects($this->never())
            ->method('affectingQuery');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->process->execute();
    }
}
