<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\IdealState;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Validator\IdealState as IdealStateValidator;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class IdealStateTest extends TestCase
{
    /**
     * @var IdealState
     */
    private $command;

    /**
     * @var OutputFormatter|MockObject
     */
    private $outputFormatterMock;

    /**
     * @var IdealStateValidator|MockObject
     */
    private $validatorMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->outputFormatterMock = $this->createMock(OutputFormatter::class);
        $this->validatorMock = $this->createMock(IdealStateValidator::class);

        $this->command = new IdealState($this->outputFormatterMock, $this->validatorMock);
    }

    public function testExecute()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Success());
        $this->validatorMock->method('getErrors')
            ->willReturn([]);
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, true, 'The configured state is ideal');
        $this->outputFormatterMock->expects($this->never())
            ->method('writeItem');

        $this->assertSame(0, $this->command->run($inputMock, $outputMock));
    }

    public function testExecuteWithErrors()
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $error1 = new Error('First error');
        $error2 = new Error('Second error');

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Error('State is not ideal'));
        $this->validatorMock->method('getErrors')
            ->willReturn([
                $error1,
                $error2,
            ]);
        $this->outputFormatterMock->expects($this->exactly(2))
            ->method('writeItem')
            ->withConsecutive(
                [$outputMock, $error1],
                [$outputMock, $error2]
            );
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, false, 'State is not ideal');

        $this->assertSame(2, $this->command->run($inputMock, $outputMock));
    }
}
