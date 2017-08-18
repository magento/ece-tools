<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Process\Deploy\DisableGoogleAnalytics;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

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
     * @var Adapter|Mock
     */
    private $adapterMock;

    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->adapterMock = $this->createMock(Adapter::class);

        $this->process = new DisableGoogleAnalytics(
            $this->adapterMock,
            $this->loggerMock,
            $this->environmentMock
        );
    }

    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Disabling Google Analytics');
        $this->adapterMock->expects($this->once())
            ->method('execute')
            ->with("update core_config_data set value = 0 where path = 'google/analytics/active';");

        $this->process->execute();
    }

    public function testExecuteMasterBranch()
    {
        $this->environmentMock->expects($this->once())
            ->method('isMasterBranch')
            ->willReturn(true);
        $this->adapterMock->expects($this->never())
            ->method('execute');
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->process->execute();
    }
}
