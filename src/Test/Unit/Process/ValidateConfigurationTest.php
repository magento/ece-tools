<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process;

use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Process\ValidateConfiguration;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ValidateConfigurationTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    public function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    public function testExecuteWithoutValidators()
    {
        $process = new ValidateConfiguration(
            $this->loggerMock,
            []
        );

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating configuration'],
                ['End of validation']
            );
        $this->loggerMock->expects($this->never())
            ->method('critical');

        $process->execute();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Please fix configuration with given suggestions
     */
    public function testExecuteWithCriticalError()
    {
        $criticalValidator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $criticalResultMock = $this->createMock(Result\Error::class);
        $criticalResultMock->expects($this->once())
            ->method('getError')
            ->willReturn('some error');
        $criticalResultMock->expects($this->any())
            ->method('getSuggestion')
            ->willReturn('some suggestion');
        $criticalValidator->expects($this->once())
            ->method('validate')
            ->willReturn($criticalResultMock);
        $warningValidator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $warningValidator->expects($this->never())
            ->method('validate');
        $process = new ValidateConfiguration(
            $this->loggerMock,
            [
                ValidatorInterface::LEVEL_CRITICAL => [
                    $criticalValidator,
                ],
                ValidatorInterface::LEVEL_WARNING => [
                    $warningValidator,
                ],
            ]
        );

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Validating configuration');
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('critical', 'some error' . PHP_EOL . 'SUGGESTION:' . PHP_EOL . 'some suggestion');

        $process->execute();
    }

    public function testExecuteWithWarningMessage()
    {
        $warningValidator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $warningResultMock = $this->createMock(Result\Error::class);
        $warningResultMock->expects($this->once())
            ->method('getError')
            ->willReturn('some warning');
        $warningResultMock->expects($this->any())
            ->method('getSuggestion')
            ->willReturn('some warning suggestion');
        $warningValidator->expects($this->once())
            ->method('validate')
            ->willReturn($warningResultMock);
        $criticalValidator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $criticalValidator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Result\Success::class));
        $process = new ValidateConfiguration(
            $this->loggerMock,
            [
                ValidatorInterface::LEVEL_CRITICAL => [
                    $criticalValidator,
                ],
                ValidatorInterface::LEVEL_WARNING => [
                    $warningValidator,
                ],
            ]
        );

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating configuration'],
                ['End of validation']
            );
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with('warning', 'some warning' . PHP_EOL . 'SUGGESTION:' . PHP_EOL . 'some warning suggestion');

        $process->execute();
    }
}
