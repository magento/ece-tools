<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\Deploy\MagentoCloudVariables;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class MagentoCloudVariablesTest extends TestCase
{
    /**
     * @var MagentoCloudVariables
     */
    private $validator;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var Validator\ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->resultFactoryMock = $this->createMock(Validator\ResultFactory::class);

        $this->validator = new MagentoCloudVariables(
            $this->environmentMock,
            $this->resultFactoryMock
        );
    }

    /**
     * @param array $magentoCloudVariables
     * @param string $expectedResultType
     * @param string|null $suggestionMessage
     *
     * @dataProvider validateDataProvider
     */
    public function testValidate(
        array $magentoCloudVariables,
        string $expectedResultType,
        string $suggestionMessage = null
    ): void {
        $this->environmentMock->expects($this->once())
            ->method('getVariables')
            ->willReturn($magentoCloudVariables);
        $resultMock = $this->resultFactoryMock->expects($this->once())
            ->method(strtolower($expectedResultType));

        if ($suggestionMessage) {
            $resultMock->with('Environment configuration is not valid', $this->stringContains($suggestionMessage));
        }

        $this->validator->validate();
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateDataProvider(): array
    {
        return [
            [
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL => '3'],
                ResultInterface::SUCCESS
            ],
            [
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL => '3a'],
                ResultInterface::ERROR,
                'The variable SCD_COMPRESSION_LEVEL has wrong value "3a" and will be ignored, ' .
                'use only integer value from 0 to 9'
            ],
            [
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL => 25],
                ResultInterface::ERROR,
                'The variable SCD_COMPRESSION_LEVEL has wrong value "25" and will be ignored, ' .
                'use only integer value from 0 to 9'
            ],
            [
                [DeployInterface::VAR_SCD_COMPRESSION_LEVEL => '10'],
                ResultInterface::ERROR,
                'The variable SCD_COMPRESSION_LEVEL has wrong value "10" and will be ignored, ' .
                'use only integer value from 0 to 9'
            ],
            [
                [DeployInterface::VAR_SCD_THREADS => '3'],
                ResultInterface::SUCCESS
            ],
            [
                [DeployInterface::VAR_SCD_THREADS => 3],
                ResultInterface::SUCCESS
            ],
            [
                [DeployInterface::VAR_SCD_THREADS => '3a'],
                ResultInterface::ERROR,
                'The variable SCD_THREADS has wrong value "3a" and will be ignored, use only integer value'
            ],
            [
                [DeployInterface::VAR_VERBOSE_COMMANDS => '1'],
                ResultInterface::ERROR,
                'The variable VERBOSE_COMMANDS has wrong value "1" and will be ignored, use one of possible values:' .
                ' -v, -vv, -vvv'
            ],
            [
                [DeployInterface::VAR_VERBOSE_COMMANDS => 'true'],
                ResultInterface::ERROR,
                'The variable VERBOSE_COMMANDS has wrong value "true" and will be ignored,' .
                ' use one of possible values: -v, -vv, -vvv'
            ],
            [
                [DeployInterface::VAR_VERBOSE_COMMANDS => '-v'],
                ResultInterface::SUCCESS,
            ],
            [
                [DeployInterface::VAR_VERBOSE_COMMANDS => 'enabled'],
                ResultInterface::SUCCESS,
            ],
            [
                [DeployInterface::VAR_CLEAN_STATIC_FILES => '1'],
                ResultInterface::ERROR,
                'The variable CLEAN_STATIC_FILES has wrong value: "1" and will be ignored, use only disabled or enabled'
            ],
            [
                [DeployInterface::VAR_UPDATE_URLS => '1'],
                ResultInterface::ERROR,
                'The variable UPDATE_URLS has wrong value: "1" and will be ignored, use only disabled or enabled'
            ],
            [
                [DeployInterface::VAR_GENERATED_CODE_SYMLINK => '1'],
                ResultInterface::ERROR,
                'The variable GENERATED_CODE_SYMLINK has wrong value: "1" and will be ignored,' .
                ' use only disabled or enabled'
            ],
            [
                [DeployInterface::VAR_CLEAN_STATIC_FILES => 'enabled'],
                ResultInterface::SUCCESS,
            ],
            [
                [DeployInterface::VAR_UPDATE_URLS => 'enabled'],
                ResultInterface::SUCCESS,
            ],
            [
                [DeployInterface::VAR_GENERATED_CODE_SYMLINK => 'enabled'],
                ResultInterface::SUCCESS,
            ],
            [
                [
                    DeployInterface::VAR_CLEAN_STATIC_FILES => '1',
                    DeployInterface::VAR_SCD_COMPRESSION_LEVEL => '3a',
                    DeployInterface::VAR_VERBOSE_COMMANDS => '1'
                ],
                ResultInterface::ERROR,
                '  The variable SCD_COMPRESSION_LEVEL has wrong value "3a" and will be ignored, ' .
                'use only integer value from 0 to 9' . PHP_EOL .
                '  The variable CLEAN_STATIC_FILES has wrong value: "1" and will be ignored, ' .
                'use only disabled or enabled' . PHP_EOL .
                '  The variable VERBOSE_COMMANDS has wrong value "1" and will be ignored, ' .
                'use one of possible values: -v, -vv, -vvv'
            ],
        ];
    }
}
