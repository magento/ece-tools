<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Application\HookChecker;
use Magento\MagentoCloud\Step\Deploy\DeployCompletion;
use Magento\MagentoCloud\Step\ProcessException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritdoc
 */
class DeployCompletionTest extends TestCase
{
    /**
     * @var DeployCompletion
     */
    private $process;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var HookChecker|MockObject
     */
    private $hookChecker;

    /**
     * @var StepInterface|MockObject
     */
    private $processMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->hookChecker = $this->createMock(HookChecker::class);
        $this->processMock = $this->getMockForAbstractClass(StepInterface::class);

        $this->process = new DeployCompletion(
            $this->loggerMock,
            $this->hookChecker,
            [$this->processMock]
        );
    }

    /**
     * @throws ProcessException
     */
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

    /**
     * @throws ProcessException
     */
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
