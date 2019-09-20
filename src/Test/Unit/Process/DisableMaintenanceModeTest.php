<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Process;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Process\DisableMaintenanceMode;
use Magento\MagentoCloud\Process\ProcessException;
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
    protected function setUp()
    {
        $this->switcherMock = $this->createMock(MaintenanceModeSwitcher::class);

        $this->process = new DisableMaintenanceMode(
            $this->switcherMock
        );
    }

    /**
     * @throws ProcessException
     */
    public function testExecute(): void
    {
        $this->switcherMock->expects($this->once())
            ->method('disable');

        $this->process->execute();
    }

    /**
     * @throws ProcessException
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage Some error
     */
    public function testExecuteWithException(): void
    {
        $this->switcherMock->expects($this->once())
            ->method('disable')
            ->willThrowException(new GenericException('Some error'));

        $this->process->execute();
    }
}
