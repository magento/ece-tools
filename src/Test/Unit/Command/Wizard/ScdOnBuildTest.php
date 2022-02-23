<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\ScdOnBuild;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Validator\Result\Error;
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
    protected function setUp(): void
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

    public function testExecuteWithErrors()
    {
        $inputMock = $this->getMockForAbstractClass(Input::class);
        $outputMock = $this->getMockForAbstractClass(Output::class);

        $errorMock = $this->createMock(Error::class);
        $errorMock->expects($this->any())
            ->method('getError')
            ->willReturn('Some error');

        $this->scdOnBuildValidatorMock->expects($this->once())
            ->method('getErrors')
            ->willReturn([
                $errorMock,
            ]);
        $this->outputFormatterMock->expects($this->once())
            ->method('writeItem')
            ->with($outputMock, 'Some error');
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, false, 'SCD on build is disabled');

        $this->command->run($inputMock, $outputMock);
    }
}
