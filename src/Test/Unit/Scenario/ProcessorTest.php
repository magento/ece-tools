<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Scenario;

use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Scenario\Exception\ProcessorException;
use Magento\MagentoCloud\Scenario\Merger;
use Magento\MagentoCloud\Scenario\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class ProcessorTest extends TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var Merger|MockObject
     */
    private $mergerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Manager|MockObject
     */
    private $packageManagerMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->mergerMock = $this->createMock(Merger::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->packageManagerMock = $this->createMock(Manager::class);

        $this->processor = new Processor(
            $this->mergerMock,
            $this->loggerMock,
            $this->packageManagerMock
        );
    }

    /**
     * @throws ProcessorException
     */
    public function testExecute(): void
    {
        $scenarios = [
            'some/scenario.xml'
        ];

        $step1 = $this->getMockForAbstractClass(StepInterface::class);
        $step2 = $this->getMockForAbstractClass(StepInterface::class);

        $step1->expects($this->once())
            ->method('execute');
        $step2->expects($this->once())
            ->method('execute');

        $steps = [
            'step1' => $step1,
            'step2' => $step2
        ];

        $this->packageManagerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('1.0.0');
        $this->mergerMock->expects($this->once())
            ->method('merge')
            ->with($scenarios)
            ->willReturn($steps);
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [
                    sprintf(
                        'Starting scenario(s): %s 1.0.0',
                        implode(', ', $scenarios)
                    )
                ],
                ['Scenario(s) finished']
            );
        $this->loggerMock->method('debug')
            ->withConsecutive(
                ['Running step: step1'],
                ['Step "step1" finished'],
                ['Running step: step2'],
                ['Step "step2" finished']
            );

        $this->processor->execute($scenarios);
    }

    /**
     * @throws ProcessorException
     */
    public function testExeceuteWithException(): void
    {
        $this->expectException(ProcessorException::class);
        $this->expectExceptionMessage('Some error');

        $scenarios = [
            'some/scenario.xml'
        ];

        $step1 = $this->getMockForAbstractClass(StepInterface::class);

        $step1->expects($this->once())
            ->method('execute')
            ->willThrowException(new ProcessorException('Some error'));

        $steps = [
            'step1' => $step1
        ];

        $this->packageManagerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('1.0.0');
        $this->mergerMock->expects($this->once())
            ->method('merge')
            ->with($scenarios)
            ->willReturn($steps);
        $this->loggerMock->method('info')
            ->withConsecutive(
                [
                    sprintf(
                        'Starting scenario(s): %s 1.0.0',
                        implode(', ', $scenarios)
                    )
                ]
            );
        $this->loggerMock->method('debug')
            ->withConsecutive(
                ['Running step: step1']
            );
        $this->loggerMock->method('error')
            ->withConsecutive(['Some error']);

        $this->processor->execute($scenarios);
    }
}
