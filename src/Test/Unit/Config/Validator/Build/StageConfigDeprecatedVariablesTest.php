<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Build;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Build\StageConfigDeprecatedVariables;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;

/**
 * @inheritdoc
 */
class StageConfigDeprecatedVariablesTest extends TestCase
{
    /**
     * @var StageConfigDeprecatedVariables
     */
    private $validator;

    /**
     * @var EnvironmentReader|MockObject
     */
    private $environmentReaderMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->validator = new StageConfigDeprecatedVariables(
            $this->environmentReaderMock,
            $this->resultFactoryMock,
            new Schema()
        );
    }

    public function testValidateSuccess()
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with('success', [])
            ->willReturn($this->createMock(Success::class));

        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    public function testValidateScdExcludeThemesIsDeprecated()
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                StageConfigInterface::SECTION_STAGE => [
                    StageConfigInterface::STAGE_DEPLOY => [
                        StageConfigInterface::VAR_SCD_EXCLUDE_THEMES => 'theme1'
                    ],
                ],
            ]);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with('error', [
                'error' => 'Some configurations in your .magento.env.yaml file is deprecated.',
                'suggestion' => 'The SCD_EXCLUDE_THEMES variable is deprecated. Use SCD_MATRIX instead.'
            ])
            ->willReturn($this->createMock(Error::class));

        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }
}
