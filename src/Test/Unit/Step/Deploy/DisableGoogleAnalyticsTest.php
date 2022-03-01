<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Step\Deploy\DisableGoogleAnalytics;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
    private $step;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConnectionInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var DeployConfig|MockObject
     */
    private $deployConfigMock;

    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->connectionMock = $this->getMockBuilder(ConnectionInterface::class)
            ->getMockForAbstractClass();
        $this->deployConfigMock = $this->createMock(DeployConfig::class);

        $this->step = new DisableGoogleAnalytics(
            $this->connectionMock,
            $this->loggerMock,
            $this->environmentMock,
            $this->deployConfigMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecuteDisable(): void
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteMaster(): void
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteEnabled(): void
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

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithConfigException()
    {
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_CONFIG_NOT_DEFINED);
        $this->expectExceptionMessage('some error');

        $this->deployConfigMock->expects($this->once())
            ->method('get')
            ->willThrowException(new ConfigException('some error', Error::DEPLOY_CONFIG_NOT_DEFINED));

        $this->step->execute();
    }
}
