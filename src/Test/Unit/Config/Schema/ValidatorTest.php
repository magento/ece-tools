<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Schema;

use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Schema\Validator;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Schema|MockObject
     */
    private $schemaMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var Validator\ValidatorFactory|MockObject
     */
    private $validatorFactoryMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->schemaMock = $this->createMock(Schema::class);
        $this->resultFactoryMock = $this->createTestProxy(ResultFactory::class, [$this->createMock(ErrorInfo::class)]);
        $this->validatorFactoryMock = $this->createMock(Validator\ValidatorFactory::class);

        $this->validator = new Validator(
            $this->schemaMock,
            $this->resultFactoryMock,
            $this->validatorFactoryMock
        );
    }

    /**
     * @param string $key
     * @param string|bool|int $value
     * @param ResultInterface $expected
     * @param string $stage
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        string $key,
        $value,
        $expected,
        string $stage = StageConfigInterface::STAGE_DEPLOY
    ): void {
        $schema = [
            'TEST_BOOLEAN' => [
                Schema::SCHEMA_TYPE => 'boolean',
                Schema::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL
                ],
                Schema::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => true,
                ],
            ],
            'TEST_STRING' => [
                Schema::SCHEMA_TYPE => 'string',
                Schema::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                Schema::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => 'test',
                ],
                Schema::SCHEMA_ALLOWED_VALUES => [
                    'test'
                ]
            ],
            'TEST_STRING_VALIDATOR' => [
                Schema::SCHEMA_TYPE => 'string',
                Schema::SCHEMA_STAGES => [
                    StageConfigInterface::STAGE_GLOBAL,
                    StageConfigInterface::STAGE_DEPLOY
                ],
                Schema::SCHEMA_DEFAULT_VALUE => [
                    StageConfigInterface::STAGE_GLOBAL => 'test',
                ],
                Schema::SCHEMA_VALUE_VALIDATORS => [[
                    'class' => 'ErrorValidator',
                    'arg1' => 'value1',
                    'arg2' => 'value2',
                ]]
            ],
        ];

        $mockValidatorError = $this->getMockForAbstractClass(Validator\ValidatorInterface::class);
        $mockValidatorError->method('validate')
            ->willReturn(new Error('Some error'));

        $this->validatorFactoryMock->method('create')
            ->with('ErrorValidator', ['value1', 'value2'])
            ->willReturn($mockValidatorError);
        $this->schemaMock->expects($this->once())
            ->method('getVariables')
            ->willReturn($schema);

        $this->assertEquals(
            $expected,
            $this->validator->validate($key, $stage, $value)
        );
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            [
                'TEST_BOOLEAN',
                2,
                new Error(
                    'The TEST_BOOLEAN variable contains an invalid value of type integer. ' .
                    'Use the following type: boolean.'
                )
            ],
            [
                'TEST_BOOLEAN',
                'test',
                new Error(
                    'The TEST_BOOLEAN variable contains an invalid value of type string. ' .
                    'Use the following type: boolean.'
                )
            ],
            [
                'TEST_BOOLEAN',
                true,
                new Error(
                    'The TEST_BOOLEAN variable is not supposed to be in stage deploy. ' .
                    'Move it to one of the possible stages: global.'
                )
            ],
            [
                'TEST_BOOLEAN',
                true,
                new Success(),
                'global'
            ],
            [
                'TEST_STRING',
                1,
                new Error(
                    'The TEST_STRING variable contains an invalid value of type integer. ' .
                    'Use the following type: string.'
                )
            ],
            [
                'TEST_STRING',
                'test_undefined',
                new Error(
                    'The TEST_STRING variable contains an invalid value test_undefined. '
                    . 'Use one of the available value options: test.'
                ),
            ],
            [
                'TEST_UNDEFINED',
                1,
                new Error(
                    'The TEST_UNDEFINED variable is not allowed in configuration.'
                )
            ],
            [
                'TEST_STRING_VALIDATOR',
                'test',
                new Error('Some error')
            ]
        ];
    }
}
