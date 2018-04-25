<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Validator\Build\StageConfig;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class StageConfigTest extends TestCase
{
    /**
     * @var StageConfig
     */
    private $validator;

    /**
     * @var EnvironmentReader|Mock
     */
    private $environmentReaderMock;

    /**
     * @var Validator\ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @var Validator\SchemaValidator|Mock
     */
    private $schemaValidatorMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->resultFactoryMock = $this->createMock(Validator\ResultFactory::class);
        $this->schemaValidatorMock = $this->createMock(Validator\SchemaValidator::class);

        $this->validator = new StageConfig(
            $this->environmentReaderMock,
            $this->resultFactoryMock,
            $this->schemaValidatorMock
        );
    }

    public function testValidate()
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                StageConfigInterface::SECTION_STAGE => [
                    StageConfigInterface::STAGE_BUILD => [
                        StageConfigInterface::VAR_VERBOSE_COMMANDS => '-v',
                    ],
                    StageConfigInterface::STAGE_DEPLOY => null,
                ],
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
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                StageConfigInterface::SECTION_STAGE => [
                    StageConfigInterface::STAGE_BUILD => [
                        StageConfigInterface::VAR_VERBOSE_COMMANDS => 'error',
                    ],
                ],
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
