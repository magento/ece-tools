<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Build\BuildOptionsIni;
use Magento\MagentoCloud\Config\Build\Reader as BuildReader;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\Validator\SchemaValidator;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class BuildOptionsIniTest extends TestCase
{
    /**
     * @var BuildOptionsIni
     */
    private $validator;

    /**
     * @var ResultFactory|Mock
     */
    private $resultFactoryMock;

    /**
     * @var SchemaValidator|Mock
     */
    private $schemaValidatorMock;

    /**
     * @var BuildReader|Mock
     */
    private $buildReaderMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->schemaValidatorMock = $this->createMock(SchemaValidator::class);
        $this->buildReaderMock = $this->createMock(BuildReader::class);

        $this->validator = new BuildOptionsIni(
            $this->resultFactoryMock,
            $this->schemaValidatorMock,
            $this->buildReaderMock
        );
    }

    public function testValidateWithError()
    {
        $this->buildReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'scd_strategy' => 'quik',
                'scd_threads' => 'two',
                'exclude_themes' => 'some_theme',
                'some_wrong_option' => 'someValue'
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::ERROR, [
                'error' => 'The build_options.ini file contains an unexpected value',
                'suggestion' => 'scd_strategy error1' . PHP_EOL .
                    'scd_threads error2' . PHP_EOL .
                    'exclude_themes error3' . PHP_EOL .
                    'Option some_wrong_option is not allowed'
            ]);
        $this->schemaValidatorMock->expects($this->exactly(3))
            ->method('validate')
            ->withConsecutive(
                [StageConfigInterface::VAR_SCD_STRATEGY, StageConfigInterface::STAGE_BUILD, 'quik'],
                [StageConfigInterface::VAR_SCD_THREADS, StageConfigInterface::STAGE_BUILD, 'two'],
                [StageConfigInterface::VAR_SCD_EXCLUDE_THEMES, StageConfigInterface::STAGE_BUILD, 'some_theme']
            )->willReturnOnConsecutiveCalls(
                StageConfigInterface::VAR_SCD_STRATEGY . ' error1',
                StageConfigInterface::VAR_SCD_THREADS . ' error2',
                StageConfigInterface::VAR_SCD_EXCLUDE_THEMES . ' error3'
            );

        $this->validator->validate();
    }

    public function testValidateEmptyBuildOptionsIni()
    {
        $this->buildReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultInterface::SUCCESS);

        $this->validator->validate();
    }
}
