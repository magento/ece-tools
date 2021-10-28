<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\RunCommand;
use Magento\MagentoCloud\Scenario\Exception\ProcessorException;
use Magento\MagentoCloud\Scenario\Processor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class RunCommandTest extends TestCase
{
    /**
     * @var RunCommand
     */
    private $command;

    /**
     * @var Processor|MockObject
     */
    private $processorMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->processorMock = $this->createMock(Processor::class);

        $this->command = new RunCommand(
            $this->processorMock
        );
    }

    /**
     * @throws ProcessorException
     */
    public function testExecute(): void
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $scenarios = [
            'scenario/deploy.xml'
        ];

        $inputMock->expects($this->once())
            ->method('getArgument')
            ->with(RunCommand::ARG_SCENARIO)
            ->willReturn($scenarios);
        $this->processorMock->expects($this->once())
            ->method('execute')
            ->with($scenarios);

        $this->command->execute($inputMock, $outputMock);
    }
}
