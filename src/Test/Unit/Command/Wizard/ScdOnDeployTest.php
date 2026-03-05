<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\ScdOnDeploy;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnDeploy as ScdOnDeployValidator;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class ScdOnDeployTest extends TestCase
{
    /**
     * @var ScdOnDeploy
     */
    private $command;

    /**
     * @var OutputFormatter|MockObject
     */
    private $outputFormatterMock;

    /**
     * @var ScdOnDeployValidator|MockObject
     */
    private $scdOnDeployValidatorMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->outputFormatterMock = $this->createMock(OutputFormatter::class);
        $this->scdOnDeployValidatorMock = $this->createMock(ScdOnDeployValidator::class);

        $this->command = new ScdOnDeploy(
            $this->outputFormatterMock,
            $this->scdOnDeployValidatorMock
        );
    }

    public function testExecute()
    {
        $inputMock = $this->createStub(Input::class);
        $outputMock = $this->createStub(Output::class);

        $this->scdOnDeployValidatorMock->expects($this->once())
            ->method('getErrors')
            ->willReturn([]);
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, true, 'SCD on deploy is enabled');

        $this->command->run($inputMock, $outputMock);
    }

    public function testExecuteWithErrors()
    {
        $inputMock = $this->createStub(Input::class);
        $outputMock = $this->createStub(Output::class);

        $errorMock = $this->createMock(Error::class);
        $errorMock->expects($this->any())
            ->method('getError')
            ->willReturn('Some error');

        $this->scdOnDeployValidatorMock->expects($this->once())
            ->method('getErrors')
            ->willReturn([
                $errorMock,
            ]);
        $this->outputFormatterMock->expects($this->once())
            ->method('writeItem')
            ->with($outputMock, 'Some error');
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, false, 'SCD on deploy is disabled');

        $this->command->run($inputMock, $outputMock);
    }
}
