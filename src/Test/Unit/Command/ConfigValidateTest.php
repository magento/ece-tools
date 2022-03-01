<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Command\ConfigValidate;
use Magento\MagentoCloud\Config\Validator\Build\StageConfig;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\ValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class ConfigValidateTest extends TestCase
{
    /**
     * @var ConfigValidate
     */
    private $command;

    /**
     * @var StageConfig|MockObject
     */
    private $stageConfigMock;

    /**
     * @var InputInterface|MockObject
     */
    private $inputMock;

    /**
     * @var OutputInterface|MockObject
     */
    private $outputMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->stageConfigMock = $this->createMock(StageConfig::class);
        $this->inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $this->outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->command = new ConfigValidate($this->stageConfigMock);
    }

    public function testExecute()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Success::class));

        $this->assertEquals(
            Cli::SUCCESS,
            $this->command->execute($this->inputMock, $this->outputMock)
        );
    }

    public function testExecuteError()
    {
        $errorMock = $this->createMock(Error::class);
        $errorMock->expects($this->once())
            ->method('getError')
            ->willReturn('Error message');
        $errorMock->expects($this->once())
            ->method('getSuggestion')
            ->willReturn('Error suggestion');
        $this->stageConfigMock->expects($this->once())
            ->method('validate')
            ->willReturn($errorMock);
        $this->outputMock->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['Error message'],
                ['Error suggestion']
            );

        $this->assertEquals(
            Cli::FAILURE,
            $this->command->execute($this->inputMock, $this->outputMock)
        );
    }

    public function testExecuteWithException()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('validate')
            ->willThrowException(new ValidatorException('some error'));
        $this->outputMock->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['Command execution failed:'],
                ['some error']
            );

        $this->assertEquals(
            Cli::FAILURE,
            $this->command->execute($this->inputMock, $this->outputMock)
        );
    }
}
