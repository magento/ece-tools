<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\OnFail\Action;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\OnFail\Action\SkipAction;

/**
 * @inheritDoc
 */
class SkipActionTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var SkipAction
     */
    private $action;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->action = new SkipAction($this->loggerMock, 'test-action');
    }

    /**
     * Tests the method execute of SkipAction class.
     *
     * @throws \Magento\MagentoCloud\OnFail\Action\ActionException
     */
    public function testExecute(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Action "test-action" was skipped');
        $this->action->execute();
    }
}
