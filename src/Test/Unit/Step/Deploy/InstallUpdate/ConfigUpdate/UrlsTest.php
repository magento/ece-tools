<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Urls;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Step\StepInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class UrlsTest extends TestCase
{
    /**
     * @var Urls
     */
    private $step;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var StepInterface|Mock
     */
    private $stepMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var DeployInterface|Mock
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->stepMock = $this->getMockForAbstractClass(StepInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->step = new Urls(
            $this->environmentMock,
            $this->stepMock,
            $this->loggerMock,
            $this->stageConfigMock
        );
    }

    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(false);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_FORCE_UPDATE_URLS],
                [DeployInterface::VAR_UPDATE_URLS]
            )
            ->willReturnOnConsecutiveCalls(false, true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating secure and unsecure URLs');
        $this->stepMock->expects($this->once())
            ->method('execute');

        $this->step->execute();
    }

    public function testExecuteForceUpdate()
    {
        $this->environmentMock->expects($this->never())
            ->method('isMasterBranch');
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_FORCE_UPDATE_URLS)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating secure and unsecure URLs');
        $this->stepMock->expects($this->once())
            ->method('execute');

        $this->step->execute();
    }

    public function testExecuteSkippedIsMasterBranch()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(true);
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_FORCE_UPDATE_URLS)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Skipping URL updates because we are deploying to a Production or Staging'));
        $this->stepMock->expects($this->never())
            ->method('execute');

        $this->step->execute();
    }

    public function testExecuteSkippedUpdateUrlsIsFalse()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(false);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_FORCE_UPDATE_URLS],
                [DeployInterface::VAR_UPDATE_URLS]
            )
            ->willReturnOnConsecutiveCalls(false, false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Skipping URL updates because the URL_UPDATES variable is set to false.'));
        $this->stepMock->expects($this->never())
            ->method('execute');

        $this->step->execute();
    }
}
