<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Scenario;

use Magento\MagentoCloud\OnFail\Action\ActionException;
use Magento\MagentoCloud\OnFail\Action\ActionInterface;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Step\StepException;
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
    protected function setUp(): void
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
     * @throws \ReflectionException
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

        $action = $this->getMockForAbstractClass(ActionInterface::class);
        $action->expects($this->never())
            ->method('execute');

        $this->packageManagerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('1.0.0');
        $this->mergerMock->expects($this->once())
            ->method('merge')
            ->with($scenarios)
            ->willReturn(['steps' => $steps, 'actions' => [$action]]);
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
     * @throws \ReflectionException
     */
    public function testExecuteWithStepException(): void
    {
        $this->expectException(ProcessorException::class);
        $this->expectExceptionMessage('Some error');

        $scenarios = [
            'some/scenario.xml'
        ];

        $step1 = $this->getMockForAbstractClass(StepInterface::class);

        $step1->expects($this->once())
            ->method('execute')
            ->willThrowException(new StepException('Some error', 201));

        $steps = [
            'step1' => $step1
        ];

        $action = $this->getMockForAbstractClass(ActionInterface::class);
        $action->expects($this->once())
            ->method('execute');

        $this->packageManagerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('1.0.0');
        $this->mergerMock->expects($this->once())
            ->method('merge')
            ->with($scenarios)
            ->willReturn(['steps' => $steps, 'actions' => ['on-fail' => $action]]);
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
                ['Running step: step1'],
                ['Running on fail action: on-fail'],
                ['On fail action "on-fail" finished']
            );
        $this->loggerMock->method('error')
            ->withConsecutive(['Some error']);

        $this->processor->execute($scenarios);
    }

    /**
     * @throws ProcessorException
     * @throws \ReflectionException
     */
    public function testExecuteWithStepAndActionException(): void
    {
        $this->expectException(ProcessorException::class);
        $this->expectExceptionMessage('Step error');
        $this->expectExceptionCode(11);

        $scenarios = [
            'some/scenario.xml'
        ];

        $step1 = $this->getMockForAbstractClass(StepInterface::class);

        $step1->expects($this->once())
            ->method('execute')
            ->willThrowException(new StepException('Step error', 11));

        $steps = [
            'step1' => $step1
        ];

        $action = $this->getMockForAbstractClass(ActionInterface::class);
        $action->expects($this->once())
            ->method('execute')
            ->willThrowException(new ActionException('Action error'));

        $this->packageManagerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('1.0.0');
        $this->mergerMock->expects($this->once())
            ->method('merge')
            ->with($scenarios)
            ->willReturn(['steps' => $steps, 'actions' => ['on-fail' => $action]]);
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
                ['Running step: step1'],
                ['Running on fail action: on-fail']
            );
        $this->loggerMock->method('error')
            ->withConsecutive(
                ['Action error'],
                ['Step error']
            );

        $this->processor->execute($scenarios);
    }

    /**
     * @throws ProcessorException
     * @throws \ReflectionException
     */
    public function testExecuteWithRuntimeException(): void
    {
        $this->expectException(ProcessorException::class);
        $this->expectExceptionMessage('Unhandled error: Some error');

        $scenarios = [
            'some/scenario.xml'
        ];

        $this->packageManagerMock->expects($this->once())
            ->method('getPrettyInfo')
            ->willReturn('1.0.0');
        $this->mergerMock->expects($this->once())
            ->method('merge')
            ->willThrowException(new \RuntimeException('Some error', 10));
        $this->loggerMock->method('info')
            ->withConsecutive(
                [
                    sprintf(
                        'Starting scenario(s): %s 1.0.0',
                        implode(', ', $scenarios)
                    )
                ]
            );

        $step1 = $this->getMockForAbstractClass(StepInterface::class);
        $step1->expects($this->never())
            ->method('execute');

        $action = $this->getMockForAbstractClass(ActionInterface::class);
        $action->expects($this->never())
            ->method('execute');

        $this->loggerMock->expects($this->never())
            ->method('debug');
        $this->loggerMock->method('error')
            ->withConsecutive(['Unhandled error: Some error']);

        $this->processor->execute($scenarios);
    }
}
