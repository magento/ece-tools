<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Application\HookChecker;
use Magento\MagentoCloud\Process\Deploy\BeforePostDeploy;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class BeforePostDeployTest extends TestCase
{
    /**
     * @var BeforePostDeploy
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var HookChecker|Mock
     */
    private $hookChecker;

    /**
     * @var ProcessInterface|Mock
     */
    private $processMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->hookChecker = $this->createMock(HookChecker::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);

        $this->process = new BeforePostDeploy(
            $this->loggerMock,
            $this->hookChecker,
            $this->processMock
        );
    }

    public function testExecuteHookEnabled()
    {
        $this->hookChecker->expects($this->once())
            ->method('isPostDeployHookEnabled')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info');
        $this->processMock->expects($this->never())
            ->method('execute');

        $this->process->execute();
    }

    public function testExecuteHookNotConfigured()
    {
        $this->hookChecker->expects($this->once())
            ->method('isPostDeployHookEnabled')
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }
}
