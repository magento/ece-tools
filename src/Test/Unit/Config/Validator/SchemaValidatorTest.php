<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\SchemaValidator;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class SchemaValidatorTest extends TestCase
{
    /**
     * @var SchemaValidator
     */
    private $validator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->validator = new SchemaValidator();
    }

    /**
     * @param string $key
     * @param $value
     * @param $expected
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $key, $value, $expected)
    {
        $this->assertSame(
            $expected,
            $this->validator->validate($key, $value)
        );
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            [StageConfigInterface::VAR_VERBOSE_COMMANDS, '-v', null],
            [StageConfigInterface::VAR_VERBOSE_COMMANDS, '-vv', null],
            [StageConfigInterface::VAR_VERBOSE_COMMANDS, '-vvv', null],
            [StageConfigInterface::VAR_VERBOSE_COMMANDS, '', null],
            [StageConfigInterface::VAR_VERBOSE_COMMANDS, 1, 'Item VERBOSE_COMMANDS has unexpected type integer'],
            [StageConfigInterface::VAR_VERBOSE_COMMANDS, '1', 'Item VERBOSE_COMMANDS has unexpected value 1'],
            [StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL, 0, null],
            [StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL, 10, null],
            [
                StageConfigInterface::VAR_SCD_COMPRESSION_LEVEL,
                '1',
                'Item SCD_COMPRESSION_LEVEL has unexpected type string',
            ],
        ];
    }
}
