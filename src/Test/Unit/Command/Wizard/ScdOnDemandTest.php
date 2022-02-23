<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\ScdOnDemand;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

/**
 * @inheritdoc
 */
class ScdOnDemandTest extends TestCase
{
    /**
     * @var ScdOnDemand
     */
    private $command;

    /**
     * @var OutputFormatter|MockObject
     */
    private $outputFormatterMock;

    /**
     * @var GlobalSection|MockObject
     */
    private $globalStageMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->outputFormatterMock = $this->createMock(OutputFormatter::class);
        $this->globalStageMock = $this->createMock(GlobalSection::class);

        $this->command = new ScdOnDemand(
            $this->outputFormatterMock,
            $this->globalStageMock
        );
    }

    public function testExecute()
    {
        $inputMock = $this->getMockForAbstractClass(Input::class);
        $outputMock = $this->getMockForAbstractClass(Output::class);

        $this->globalStageMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, true, 'SCD on demand is enabled');

        $this->command->run($inputMock, $outputMock);
    }

    public function testExecuteToBeDisabled()
    {
        $inputMock = $this->getMockForAbstractClass(Input::class);
        $outputMock = $this->getMockForAbstractClass(Output::class);

        $this->globalStageMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, false, 'SCD on demand is disabled');

        $this->command->run($inputMock, $outputMock);
    }
}
