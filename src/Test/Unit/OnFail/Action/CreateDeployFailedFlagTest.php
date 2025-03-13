<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\OnFail\Action;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\OnFail\Action\CreateDeployFailedFlag;
use Magento\MagentoCloud\OnFail\Action\ActionException;

/**
 * @inheritDoc
 */
class CreateDeployFailedFlagTest extends TestCase
{
    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var CreateDeployFailedFlag
     */
    private $action;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->action = new CreateDeployFailedFlag($this->flagManagerMock);
    }

    /**
     * Tests the method execute of SkipAction class.
     *
     * @throws ActionException
     */
    public function testExecute(): void
    {
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED)
            ->willReturn(true);
        $this->action->execute();
    }

    /**
     * Test exception
     */
    public function testExecuteWithException(): void
    {
        $this->expectException(\Magento\MagentoCloud\OnFail\Action\ActionException::class);
        $this->expectExceptionMessage('Test message');
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED)
            ->willThrowException(new \Exception('Test message'));
        $this->action->execute();
    }
}
