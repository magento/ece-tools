<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\Deploy\DisableGoogleAnalytics;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Stage\Deploy as DeployConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;

/**
 * @inheritdoc
 */
class DisableGoogleAnalyticsTest extends TestCase
{
    /**
     * @var DisableGoogleAnalytics
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConnectionInterface|Mock
     */
    private $connectionMock;

    /**
     * @var DeployConfig
     */
    private $deployConfigMock;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->connectionMock = $this->getMockBuilder(ConnectionInterface::class)
            ->getMockForAbstractClass();
        $this->deployConfigMock = $this->createMock(DeployConfig::class);

        $this->process = new DisableGoogleAnalytics(
            $this->connectionMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->deployConfigMock
        );
    }

    public function testExecuteDisable()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(false);
        $this->deployConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_ENABLE_GOOGLE_ANALYTICS)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Disabling Google Analytics');
        $this->connectionMock->expects($this->once())
            ->method('affectingQuery')
            ->with("UPDATE `core_config_data` SET `value` = 0 WHERE `path` = 'google/analytics/active'");
        $this->connectionMock->expects($this->once())
            ->method('getTableName')
            ->with('core_config_data')
            ->willReturn('core_config_data');

        $this->process->execute();
    }

    public function testExecuteMaster()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(true);
        $this->deployConfigMock->expects($this->never())
            ->method('get')
            ->with(DeployInterface::VAR_ENABLE_GOOGLE_ANALYTICS);
        $this->connectionMock->expects($this->never())
            ->method('affectingQuery');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->process->execute();
    }

    public function testExecuteEnabled()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(false);
        $this->deployConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_ENABLE_GOOGLE_ANALYTICS)
            ->willReturn(true);
        $this->connectionMock->expects($this->never())
            ->method('affectingQuery');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->process->execute();
    }
}
