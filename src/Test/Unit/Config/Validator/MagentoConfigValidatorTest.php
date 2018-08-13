<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator;

use Magento\MagentoCloud\Config\Validator\MagentoConfigValidator;
use Magento\MagentoCloud\Shell\ExecBinMagento;
use Magento\MagentoCloud\Shell\ShellException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class MagentoConfigValidatorTest extends TestCase
{
    /**
     * @var MagentoConfigValidator
     */
    private $configValidator;

    /**
     * @var ExecBinMagento|Mock
     */
    private $binMagentoMock;

    protected function setUp()
    {
        $this->binMagentoMock = $this->createMock(ExecBinMagento::class);

        $this->configValidator = new MagentoConfigValidator($this->binMagentoMock);
    }

    /**
     * @param string $mockValue
     * @param bool $expectedResult
     * @dataProvider valuesForValidate
     */
    public function testValidate(string $mockValue, bool $expectedResult)
    {
        $this->binMagentoMock->expects($this->once())
            ->method('execute')
            ->with('config:show', 'some/key')
            ->willReturn([$mockValue]);

        $this->assertSame($expectedResult, $this->configValidator->validate('some/key', 'expected value'));
    }

    /**
     * @param string $mockValue
     * @param bool $expectedResult
     * @dataProvider valuesForValidate
     */
    public function testValidateDefaultValue(string $mockValue, bool $expectedResult)
    {
        $this->binMagentoMock->expects($this->once())
            ->method('execute')
            ->with('config:show', 'some/key')
            ->willThrowException(new ShellException('Command bin/magento returned code 1', 1, ['no some/key']));

        $this->assertSame($expectedResult, $this->configValidator->validate('some/key', 'expected value', $mockValue));
    }

    public function valuesForValidate(): array
    {
        return [
            ['expected value', true],
            ['unexpected value', false],
        ];
    }
}
