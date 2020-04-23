<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Step\Build\ApplyPatches;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ApplyPatchesTest extends TestCase
{
    /**
     * @var ApplyPatches
     */
    private $step;

    /**
     * @var Manager|MockObject
     */
    private $managerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->managerMock = $this->createMock(Manager::class);

        $this->step = new ApplyPatches($this->managerMock);
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->managerMock->expects($this->once())
            ->method('apply');

        $this->step->execute();
    }
}
