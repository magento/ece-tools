<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Step\DisableMaintenanceMode;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class DisableMaintenanceModeTest extends TestCase
{
    /**
     * @var DisableMaintenanceMode
     */
    private $process;

    /**
     * @var MaintenanceModeSwitcher|MockObject
     */
    private $switcherMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->switcherMock = $this->createMock(MaintenanceModeSwitcher::class);

        $this->process = new DisableMaintenanceMode(
            $this->switcherMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->switcherMock->expects($this->once())
            ->method('disable');

        $this->process->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithException(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Some error');
        $this->expectExceptionCode(Error::DEPLOY_MAINTENANCE_MODE_DISABLING_FAILED);

        $this->switcherMock->expects($this->once())
            ->method('disable')
            ->willThrowException(new GenericException('Some error'));

        $this->process->execute();
    }
}
