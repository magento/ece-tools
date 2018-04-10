<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\ScdOnBuild;
use Magento\MagentoCloud\Util\OutputFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild as ScdOnBuildValidator;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

/**
 * @inheritdoc
 */
class ScdOnBuildTest extends TestCase
{
    /**
     * @var ScdOnBuild
     */
    private $command;

    /**
     * @var OutputFormatter|MockObject
     */
    private $outputFormatterMock;

    /**
     * @var ScdOnBuildValidator|MockObject
     */
    private $scdOnBuildValidatorMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->outputFormatterMock = $this->createMock(OutputFormatter::class);
        $this->scdOnBuildValidatorMock = $this->createMock(ScdOnBuildValidator::class);

        $this->command = new ScdOnBuild(
            $this->outputFormatterMock,
            $this->scdOnBuildValidatorMock
        );
    }

    public function testExecute()
    {
        $inputMock = $this->getMockForAbstractClass(Input::class);
        $outputMock = $this->getMockForAbstractClass(Output::class);

        $this->scdOnBuildValidatorMock->expects($this->once())
            ->method('getErrors')
            ->willReturn([]);
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, true, 'SCD on build is enabled');

        $this->command->run($inputMock, $outputMock);
    }
}
