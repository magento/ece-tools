<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Application\HookChecker;
use Magento\MagentoCloud\Process\Deploy\DeployCompletion;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
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
     * @var ProcessInterface|MockObject
     */
    private $processMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->hookChecker = $this->createMock(HookChecker::class);
        $this->processMock = $this->getMockForAbstractClass(ProcessInterface::class);

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
