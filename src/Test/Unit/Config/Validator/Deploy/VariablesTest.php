<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\Variables;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\SchemaValidator;
use Magento\MagentoCloud\Config\Validator;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class VariablesTest extends TestCase
{
    /**
     * @var Variables
     */
    private $validator;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var Validator\ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @var SchemaValidator|Mock
     */
    private $schemaValidatorMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->resultFactoryMock = $this->createMock(Validator\ResultFactory::class);
        $this->schemaValidatorMock = $this->createMock(Validator\SchemaValidator::class);

        $this->validator = new Variables(
            $this->environmentMock,
            $this->schemaValidatorMock,
            $this->resultFactoryMock
        );
    }

    public function testValidate()
    {
        $this->environmentMock->expects($this->once())
            ->method('getVariables')
            ->willReturn([
                StageConfigInterface::VAR_VERBOSE_COMMANDS => '-v',
            ]);
        $this->schemaValidatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(null);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(Validator\Result\Success::SUCCESS)
            ->willReturn(new Validator\Result\Success());

        $this->assertInstanceOf(Validator\Result\Success::class, $this->validator->validate());
    }

    public function testValidateWithError()
    {
        $this->environmentMock->expects($this->once())
            ->method('getVariables')
            ->willReturn([
                StageConfigInterface::VAR_VERBOSE_COMMANDS => 'error',
            ]);
        $this->schemaValidatorMock->expects($this->once())
            ->method('validate')
            ->willReturn('Some error');
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(Validator\Result\Error::ERROR)
            ->willReturn(new Validator\Result\Error('Some error'));

        $this->assertInstanceOf(Validator\Result\Error::class, $this->validator->validate());
    }
}
