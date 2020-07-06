<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\ValidateConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ValidateConfigurationTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    public function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithoutValidators(): void
    {
        $step = new ValidateConfiguration(
            $this->loggerMock,
            []
        );

        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Validating configuration'],
                ['End of validation']
            );
        $this->loggerMock->expects($this->never())
            ->method('critical');

        $step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithCriticalError(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Fix configuration with given suggestions');
        $this->expectExceptionCode(127);

        $warningValidator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $warningValidator->expects($this->once())
            ->method('validate');

        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Validating configuration');
        $this->loggerMock->expects($this->never())
            ->method('log');

        $step = new ValidateConfiguration(
            $this->loggerMock,
            [
                ValidatorInterface::LEVEL_CRITICAL => [
                    $this->createValidatorWithError('some error', 'some  suggestion', 127),
                ],
                ValidatorInterface::LEVEL_WARNING => [
                    $warningValidator,
                ],
            ]
        );
        $step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithWarningMessage(): void
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
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

        $step = new ValidateConfiguration(
            $this->loggerMock,
            [
                ValidatorInterface::LEVEL_WARNING => [
                    $this->createValidatorWithError('some warning', 'some warning suggestion'),
                ],
            ]
        );
        $step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithWarningAndCriticalMessage(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Fix configuration with given suggestions');
        $this->expectExceptionCode(1);

        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Validating configuration');
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

        $step = new ValidateConfiguration(
            $this->loggerMock,
            [
                ValidatorInterface::LEVEL_CRITICAL => [
                    $this->createValidatorWithError('Critical error', 'some critical suggestion', 1),
                ],
                ValidatorInterface::LEVEL_WARNING => [
                    $this->createValidatorWithError('some warning', 'some warning suggestion'),
                    $this->createValidatorWithError('some warning 2', 'some warning suggestion 2'),
                ],
            ]
        );
        $step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteTypeStringLevel(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Fix configuration with given suggestions');
        $this->expectExceptionCode(10);

        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Validating configuration');
        $this->loggerMock->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                [
                    Logger::NOTICE,
                    'Fix configuration with given suggestions:'
                    . PHP_EOL . '- some notice'
                    . PHP_EOL . '  some notice suggestion'
                ],
                [
                    Logger::WARNING,
                    'Fix configuration with given suggestions:'
                    . PHP_EOL . '- some warning'
                    . PHP_EOL . '  some warning suggestion'
                ]
            );

        $step = new ValidateConfiguration(
            $this->loggerMock,
            [
                'critical' => [
                    $this->createValidatorWithError('Critical error', 'some critical suggestion', 10),
                ],
                'warning' => [
                    $this->createValidatorWithError('some warning', 'some warning suggestion'),
                ],
                'notice' => [
                    $this->createValidatorWithError('some notice', 'some notice suggestion'),
                ],
            ]
        );
        $step->execute();
    }

    /**
     * @param string $error
     * @param string $suggestion
     * @param int|null $errorCode
     * @return MockObject|ValidatorInterface
     * @throws \ReflectionException
     */
    private function createValidatorWithError(string $error, string $suggestion, int $errorCode = null): MockObject
    {
        $warningValidator = $this->getMockForAbstractClass(ValidatorInterface::class);
        $warningResultMock = $this->createMock(Result\Error::class);

        $warningResultMock->expects($this->once())
            ->method('getError')
            ->willReturn($error);
        $warningResultMock->expects($this->once())
            ->method('getSuggestion')
            ->willReturn($suggestion);
        if ($errorCode !== null) {
            $warningResultMock->expects($this->any())
                ->method('getErrorCode')
                ->willReturn($errorCode);
        }
        $warningValidator->expects($this->once())
            ->method('validate')
            ->willReturn($warningResultMock);

        return $warningValidator;
    }
}
