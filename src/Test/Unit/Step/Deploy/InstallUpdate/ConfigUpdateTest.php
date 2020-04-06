<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate;

use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ConfigUpdateTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var StepInterface|MockObject
     */
    private $stepMock;

    /**
     * @var ConfigUpdate
     */
    private $step;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->stepMock = $this->getMockForAbstractClass(StepInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->step = new ConfigUpdate(
            $this->loggerMock,
            $this->stepMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating configuration from environment variables.');
        $this->stepMock->expects($this->once())
            ->method('execute');

        $this->step->execute();
    }
}
