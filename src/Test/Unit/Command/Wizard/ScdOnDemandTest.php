<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\ScdOnDemand;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\GlobalSection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->outputFormatterMock = $this->createMock(OutputFormatter::class);
        $this->globalStageMock = $this->createMock(GlobalSection::class);
        $this->environmentMock = $this->createMock(Environment::class);

        $this->command = new ScdOnDemand(
            $this->outputFormatterMock,
            $this->globalStageMock,
            $this->environmentMock
        );
    }

    public function testExecuteConfigEnabled()
    {
        $inputMock = $this->getMockForAbstractClass(Input::class);
        $outputMock = $this->getMockForAbstractClass(Output::class);

        $this->globalStageMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getVariable')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND, Environment::VAL_ENABLED)
            ->willReturn('enabled');
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, true, 'SCD on demand is enabled');

        $this->assertSame(0, $this->command->run($inputMock, $outputMock));
    }

    public function testExecuteEnvironmentEnabled()
    {
        $inputMock = $this->getMockForAbstractClass(Input::class);
        $outputMock = $this->getMockForAbstractClass(Output::class);

        $this->globalStageMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->environmentMock->expects($this->once())
            ->method('getVariable')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND, Environment::VAL_DISABLED)
            ->willReturn('enabled');
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, true, 'SCD on demand is enabled');

        $this->assertSame(0, $this->command->run($inputMock, $outputMock));
    }

    public function testExecuteToBeDisabled()
    {
        $inputMock = $this->getMockForAbstractClass(Input::class);
        $outputMock = $this->getMockForAbstractClass(Output::class);

        $this->globalStageMock->expects($this->once())
            ->method('get')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND)
            ->willReturn(false);
        $this->environmentMock->expects($this->once())
            ->method('getVariable')
            ->with(GlobalSection::VAR_SCD_ON_DEMAND, Environment::VAL_DISABLED)
            ->willReturn('disabled');
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, false, 'SCD on demand is disabled');

        $this->assertSame(1, $this->command->run($inputMock, $outputMock));
    }
}
