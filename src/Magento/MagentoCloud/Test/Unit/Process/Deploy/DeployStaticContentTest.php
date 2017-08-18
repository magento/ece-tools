<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Process\Deploy\DeployStaticContent;
use Psr\Log\LoggerInterface;

class DeployStaticContentTest extends TestCase
{
    /**
     * @var DeployStaticContent
     */
    private $process;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Adapter|Mock
     */
    private $adapterMock;

    /**
     * @var StaticContentCleaner|Mock
     */
    private $staticContentCleanerMock;


    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->adapterMock = $this->createMock(Adapter::class);
        $this->staticContentCleanerMock = $this->createMock(StaticContentCleaner::class);

        $this->process = new DeployStaticContent(
            $this->environmentMock,
            $this->shellMock,
            $this->loggerMock,
            $this->adapterMock,
            $this->staticContentCleanerMock
        );
    }

    public function testExecuteNonProductionMode()
    {
        $this->environmentMock->expects($this->once())
            ->method('getApplicationMode')
            ->willReturn('Developer');

        $this->environmentMock->expects($this->never())
            ->method('isDeployStaticContent');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Application mode is Developer');

        $this->process->execute();
    }
}
