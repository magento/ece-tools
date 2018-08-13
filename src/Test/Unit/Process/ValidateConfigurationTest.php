<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process;

use Magento\MagentoCloud\App\Logger;
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
     * @expectedExceptionMessage Fix configuration with given suggestions
     */
    public function testExecuteWithCriticalError()
    {
        $criticalValidator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $criticalResultMock = $this->createMock(Result\Error::class);
        $warningValidator = $this->getMockForAbstractClass(ValidatorInterface::class);

        $criticalResultMock->expects($this->once())
            ->method('getError')
            ->willReturn('some error');
        $criticalResultMock->expects($this->any())
            ->method('getSuggestion')
            ->willReturn('some suggestion');
        $criticalValidator->expects($this->once())
            ->method('validate')
            ->willReturn($criticalResultMock);
        $warningValidator->expects($this->once())
            ->method('validate');

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating configuration'],
                ['End of validation']
            );
        $this->loggerMock->expects($this->never())
            ->method('log');

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

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating configuration'],
                ['End of validation']
            );
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                ValidatorInterface::LEVEL_WARNING,
                'Fix configuration with given suggestions:'
                . PHP_EOL . "- some warning\n  some warning suggestion"
            );

        $process = new ValidateConfiguration(
            $this->loggerMock,
            [
                ValidatorInterface::LEVEL_WARNING => [
                    $warningValidator,
                ],
            ]
        );
        $process->execute();
    }

    public function testExecuteWithWarningAndCriticalMessage()
    {
        $warningValidator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $warningResultMock = $this->createMock(Result\Error::class);
        $warningValidator2 = $this->getMockForAbstractClass(ValidatorInterface::class);
        $warningResultMock2 = $this->createMock(Result\Error::class);
        $criticalValidator = $this->getMockForAbstractClass(ValidatorInterface::class);

        $warningResultMock->expects($this->once())
            ->method('getError')
            ->willReturn('some warning');
        $warningResultMock->expects($this->any())
            ->method('getSuggestion')
            ->willReturn('some warning suggestion');
        $warningValidator->expects($this->once())
            ->method('validate')
            ->willReturn($warningResultMock);
        $warningResultMock2->expects($this->once())
            ->method('getError')
            ->willReturn('some warning 2');
        $warningResultMock2->expects($this->any())
            ->method('getSuggestion')
            ->willReturn('some warning suggestion 2');
        $warningValidator2->expects($this->once())
            ->method('validate')
            ->willReturn($warningResultMock2);
        $criticalValidator->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMock(Result\Success::class));

        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Validating configuration'],
                ['End of validation']
            );
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                Logger::WARNING,
                'Fix configuration with given suggestions:'
                . PHP_EOL . '- some warning'
                . PHP_EOL . '  some warning suggestion'
                . PHP_EOL . '- some warning 2'
                . PHP_EOL . '  some warning suggestion 2'
            );

        $process = new ValidateConfiguration(
            $this->loggerMock,
            [
                ValidatorInterface::LEVEL_CRITICAL => [
                    $criticalValidator,
                ],
                ValidatorInterface::LEVEL_WARNING => [
                    $warningValidator,
                    $warningValidator2,
                ],
            ]
        );
        $process->execute();
    }
}
