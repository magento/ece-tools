<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Step\Deploy\PreDeploy;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PreDeployTest extends TestCase
{
    /**
     * @var PreDeploy
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var StepInterface|MockObject
     */
    private $stepMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stepMock = $this->getMockForAbstractClass(StepInterface::class);

        $this->step = new PreDeploy(
            $this->loggerMock,
            [$this->stepMock]
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Starting pre-deploy.'],
                ['End of pre-deploy.']
            );
        $this->stepMock->expects($this->once())
            ->method('execute');

        $this->step->execute();
    }
}
