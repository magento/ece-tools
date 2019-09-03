<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Step\Deploy\PreDeploy;
use Magento\MagentoCloud\Step\ProcessException;
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
    private $process;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var StepInterface|MockObject
     */
    private $processMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->processMock = $this->getMockForAbstractClass(StepInterface::class);

        $this->process = new PreDeploy(
            $this->loggerMock,
            [$this->processMock]
        );
    }

    /**
     * @throws ProcessException
     */
    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Starting pre-deploy.'],
                ['End of pre-deploy.']
            );
        $this->processMock->expects($this->once())
            ->method('execute');

        $this->process->execute();
    }
}
