<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process;

use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Step\StepComposite;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ProcessCompositeTest extends TestCase
{
    public function testExecute()
    {
        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->getMockBuilder(StepInterface::class)
            ->getMockForAbstractClass();
        $processAMock = clone $processMock;
        $processBMock = clone $processMock;
        $processCMock = clone $processMock;

        $processPool = new StepComposite([
            $processBMock,
            $processAMock,
            $processCMock,
        ]);

        $processAMock->expects($this->once())
            ->method('execute');
        $processBMock->expects($this->once())
            ->method('execute');
        $processCMock->expects($this->once())
            ->method('execute');

        $processPool->execute();
    }
}
