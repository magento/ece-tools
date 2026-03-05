<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step;

use Magento\MagentoCloud\App\Error;
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

    public function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
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
            // withConsecutive() alternative.
            ->willReturnCallback(function (string $axis) {
                static $series = [
                    'Validating configuration',
                    'End of validation'
                ];
                $this->assertSame(array_shift($series), $axis);
            });
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
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(127);

        $warningValidator = $this->createMock(ValidatorInterface::class);
        $warningValidator->expects($this->once())
            ->method('validate');

        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            // withConsecutive() alternative.
            ->willReturnCallback(function (string $axis) {
                static $series = [
                    'Validating configuration',
                    'Fix configuration with given suggestions:'
                ];
                $this->assertSame(array_shift($series), $axis);
            });
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(ValidatorInterface::LEVEL_CRITICAL, 'some error');

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
    public function testExecuteWithCriticalErrorAndEmptyErrorCode(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('some error');
        $this->expectExceptionCode(Error::DEFAULT_ERROR);

        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(ValidatorInterface::LEVEL_CRITICAL, 'some error');

        $step = new ValidateConfiguration(
            $this->loggerMock,
            [
                ValidatorInterface::LEVEL_CRITICAL => [
                    $this->createValidatorWithError('some error', 'some  suggestion'),
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
        $this->loggerMock->expects($this->exactly(3))
            ->method('notice')
            // withConsecutive() alternative.
            ->willReturnCallback(function (string $axis) {
                static $series = [
                    'Validating configuration',
                    'Fix configuration with given suggestions:',
                    'End of validation'
                ];
                $this->assertSame(array_shift($series), $axis);
            });
        $this->loggerMock->expects($this->once())
            ->method('log')
            ->with(
                ValidatorInterface::LEVEL_WARNING,
                'some warning',
                [
                    'suggestion' => 'some warning suggestion',
                    'errorCode' => 1001
                ]
            );

        $step = new ValidateConfiguration(
            $this->loggerMock,
            [
                ValidatorInterface::LEVEL_WARNING => [
                    $this->createValidatorWithError('some warning', 'some warning suggestion', 1001),
                ],
            ]
        );
        $step->execute();
    }

    /**
     * @throws StepException
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function testExecuteWithWarningAndCriticalMessage(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Critical error');
        $this->expectExceptionCode(1);

        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            // withConsecutive() alternative.
            ->willReturnCallback(function (string $axis) {
                static $series = [
                    'Validating configuration',
                    'Fix configuration with given suggestions:'
                ];
                $this->assertSame(array_shift($series), $axis);
            });
        $suggestion1 = [
            'suggestion' => 'some warning suggestion',
            'errorCode' => 2001
        ];
        $suggestion2 = [
            'suggestion' => 'some warning suggestion 2',
            'errorCode' => 2002
        ];
        $suggestion3 = [
            'suggestion' => 'some critical suggestion',
            'errorCode' => 1
        ];
        $this->loggerMock->expects($this->exactly(3))
            ->method('log')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($arg1, $arg2, $arg3) use ($suggestion1, $suggestion2, $suggestion3) {
                if ($arg1 == Logger::WARNING && $arg2 == 'some warning' && $arg3 == $suggestion1) {
                    return true;
                } elseif ($arg1 == Logger::WARNING && $arg2 == 'some warning 2' && $arg3 == $suggestion2) {
                    return true;
                } elseif ($arg1 == Logger::CRITICAL && $arg2 == 'Critical error' && $arg3 == $suggestion3) {
                    return true;
                }
            });

        $step = new ValidateConfiguration(
            $this->loggerMock,
            [
                ValidatorInterface::LEVEL_CRITICAL => [
                    $this->createValidatorWithError('Critical error', 'some critical suggestion', 1),
                ],
                ValidatorInterface::LEVEL_WARNING => [
                    $this->createValidatorWithError('some warning', 'some warning suggestion', 2001),
                    $this->createValidatorWithError('some warning 2', 'some warning suggestion 2', 2002),
                ],
            ]
        );

        $step->execute();
    }

    /**
     * @throws StepException
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function testExecuteTypeStringLevel(): void
    {
        $this->expectException(StepException::class);
        $this->expectExceptionMessage('Critical error');
        $this->expectExceptionCode(10);

        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            // withConsecutive() alternative.
            ->willReturnCallback(function (string $axis) {
                static $series = [
                    'Validating configuration',
                    'Fix configuration with given suggestions:'
                ];
                $this->assertSame(array_shift($series), $axis);
            });
        $series = [
            [
                Logger::NOTICE,
                'some notice',
                [
                    'suggestion' => 'some notice suggestion',
                    'errorCode' => null
                ]
            ],
            [
                Logger::WARNING,
                'some warning',
                [
                    'suggestion' => 'some warning suggestion',
                    'errorCode' => 1001
                ]
            ],
            [
                Logger::CRITICAL,
                'Critical error',
                [
                    'suggestion' => 'some critical suggestion',
                    'errorCode' => 10
                ]
            ]];
        $suggestion1 = [
            'suggestion' => 'some notice suggestion',
            'errorCode' => null
        ];
        $suggestion2 = [
            'suggestion' => 'some warning suggestion',
            'errorCode' => 1001
        ];
        $suggestion3 = [
            'suggestion' => 'some critical suggestion',
            'errorCode' => 10
        ];
        $this->loggerMock->expects($this->exactly(3))
            ->method('log')
            // withConsecutive() alternative.
            ->willReturnCallback(function ($arg1, $arg2, $arg3) use ($suggestion1, $suggestion2, $suggestion3) {
                if ($arg1 == Logger::WARNING && $arg2 == 'some notice' && $arg3 == $suggestion1) {
                    return true;
                } elseif ($arg1 == Logger::WARNING && $arg2 == 'some warning' && $arg3 == $suggestion2) {
                    return true;
                } elseif ($arg1 == Logger::CRITICAL && $arg2 == 'Critical error' && $arg3 == $suggestion3) {
                    return true;
                }
            });

        $step = new ValidateConfiguration(
            $this->loggerMock,
            [
                'critical' => [
                    $this->createValidatorWithError('Critical error', 'some critical suggestion', 10),
                ],
                'warning' => [
                    $this->createValidatorWithError('some warning', 'some warning suggestion', 1001),
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
    private function createValidatorWithError(
        string $error,
        string $suggestion,
        int | null $errorCode = null
    ): MockObject {
        $warningValidator = $this->createMock(ValidatorInterface::class);
        $warningResultMock = $this->createMock(Result\Error::class);

        $warningResultMock->expects($this->any())
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
