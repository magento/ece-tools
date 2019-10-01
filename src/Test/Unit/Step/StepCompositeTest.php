<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step;

use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Step\StepComposite;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class StepCompositeTest extends TestCase
{
    public function testExecute()
    {
        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $stepMock */
        $stepMock = $this->getMockBuilder(StepInterface::class)
            ->getMockForAbstractClass();
        $stepAMock = clone $stepMock;
        $stepBMock = clone $stepMock;
        $stepCMock = clone $stepMock;

        $stepPool = new StepComposite([
            $stepBMock,
            $stepAMock,
            $stepCMock,
        ]);

        $stepAMock->expects($this->once())
            ->method('execute');
        $stepBMock->expects($this->once())
            ->method('execute');
        $stepCMock->expects($this->once())
            ->method('execute');

        $stepPool->execute();
    }
}
