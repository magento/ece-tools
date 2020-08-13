<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\EnvironmentDataInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\MageModeVariable;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
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
    protected function setUp()
    {
        $this->envDataMock = $this->createMock(EnvironmentDataInterface::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new MageModeVariable(
            $this->envDataMock,
            $this->resultFactoryMock
        );
    }

    /**
     * @param $mageMode string|null
     * @dataProvider validateSuccessDataProvider
     */
    public function testValidateSuccess($mageMode)
    {
        $this->envDataMock->expects($this->once())
            ->method('getMageMode')
            ->willReturn($mageMode);
        $this->resultFactoryMock->expects($this->once())
            ->method('success');
        $this->resultFactoryMock->expects($this->never())
            ->method('error');

        $this->validator->validate();
    }

    /**
     * Data provider for testValidateSuccess
     * @return array
     */
    public function validateSuccessDataProvider()
    {
        return [
            [null],
            [''],
            [MageModeVariable::PRODUCTION_MODE],
        ];
    }

    /**
     * @param $mageMode string
     * @dataProvider validateErrorDataProvider
     */
    public function testValidateError($mageMode)
    {
        $this->envDataMock->expects($this->once())
            ->method('getMageMode')
            ->willReturn($mageMode);
        $this->resultFactoryMock->expects($this->never())
            ->method('success');
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'Environment variable MAGE_MODE was found and the value is differ than "production".',
                'Magento Cloud does not support Magento modes other than "production". '
                . 'Remove this variable, or change the value to "%s", the only supported mode on Cloud projects.',
                Error::WARN_NOT_SUPPORTED_MAGE_MODE
            );

        $this->validator->validate();
    }

    /**
     * Data provider for testValidateError
     * @return array
     */
    public function validateErrorDataProvider()
    {
        return [
            ['developer'],
            ['default'],
            ['maintenance'],
        ];
    }
}
