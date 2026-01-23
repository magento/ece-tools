<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\EnvironmentDataInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\MageModeVariable;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class MageModeVariableTest extends TestCase
{
    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var EnvironmentDataInterface|MockObject
     */
    private $envDataMock;

    /**
     * @var MageModeVariable
     */
    private $validator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->envDataMock = $this->createMock(EnvironmentDataInterface::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new MageModeVariable(
            $this->envDataMock,
            $this->resultFactoryMock
        );
    }

    /**
     * Test validate method.
     *
     * @param $mageMode string|null
     * @throws FileSystemException
     * @dataProvider validateSuccessDataProvider
     * @return void
     */
    #[DataProvider('validateSuccessDataProvider')]
    public function testValidateSuccess($mageMode): void
    {
        $this->envDataMock->expects($this->once())
            ->method('getMageMode')
            ->willReturn($mageMode);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');
        $this->resultFactoryMock->expects($this->never())
            ->method('errorByCode');

        $this->validator->validate();
    }

    /**
     * Data provider for testValidateSuccess.
     *
     * @return array
     */
    public static function validateSuccessDataProvider(): array
    {
        return [
            [null],
            [''],
            [MageModeVariable::PRODUCTION_MODE],
        ];
    }

    /**
     * Test validate method.
     *
     * @param $mageMode string
     * @throws FileSystemException
     * @dataProvider validateErrorDataProvider
     * @return void
     */
    #[DataProvider('validateErrorDataProvider')]
    public function testValidateError($mageMode): void
    {
        $this->envDataMock->expects($this->once())
            ->method('getMageMode')
            ->willReturn($mageMode);
        $this->resultFactoryMock->expects($this->never())
            ->method('success');
        $this->resultFactoryMock->expects($this->once())
            ->method('errorByCode');

        $this->validator->validate();
    }

    /**
     * Data provider for testValidateError.
     *
     * @return array
     */
    public static function validateErrorDataProvider(): array
    {
        return [
            ['developer'],
            ['default'],
            ['maintenance'],
        ];
    }
}
