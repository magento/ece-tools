<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Config\Application\HookChecker;
use Magento\MagentoCloud\Step\Deploy\DeployCompletion;
use Magento\MagentoCloud\Step\StepException;
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
    private $step;

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
    private $stepMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->hookChecker = $this->createMock(HookChecker::class);
        $this->stepMock = $this->getMockForAbstractClass(StepInterface::class);

        $this->step = new DeployCompletion(
            $this->loggerMock,
            $this->hookChecker,
            [$this->stepMock]
        );
    }

    /**
     * @throws StepException
     */
    public function testExecuteHookEnabled()
    {
        $this->hookChecker->expects($this->once())
            ->method('isPostDeployHookEnabled')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info');
        $this->stepMock->expects($this->never())
            ->method('execute');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteHookNotConfigured()
    {
        $this->hookChecker->expects($this->once())
            ->method('isPostDeployHookEnabled')
            ->willReturn(false);
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->stepMock->expects($this->once())
            ->method('execute');

        $this->step->execute();
    }
}
