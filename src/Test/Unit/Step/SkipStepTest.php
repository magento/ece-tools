<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step;

use Magento\MagentoCloud\Step\SkipStep;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class SkipStepTest extends TestCase
{

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * Test execution.
     */
    public function testExecute()
    {
        $stepName = 'stepname';

        $skipStep = new SkipStep(
            $this->loggerMock,
            $stepName
        );

        $message = sprintf('Step "%s" was skipped', $stepName);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($message);

        $this->loggerMock->expects($this->never())
            ->method('log');

        $skipStep->execute();
    }
}
