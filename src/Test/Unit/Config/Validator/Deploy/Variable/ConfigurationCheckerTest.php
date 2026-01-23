<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy\Variable;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\Variable\ConfigurationChecker;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigurationCheckerTest extends TestCase
{
    /**
     * @var ConfigurationChecker
     */
    private $checker;

    /**
     * @var EnvironmentReader|MockObject
     */
    private $environmentReaderMock;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);

        $this->checker = new ConfigurationChecker(
            $this->environmentMock,
            $this->environmentReaderMock
        );
    }

    /**
     * Test isConfigured method.
     *
     * @param bool $expectedResult
     * @param array $envVariables
     * @param array $stageConfig
     * @param string $variableName
     * @param bool $checkGlobal
     * @dataProvider isConfiguredDataProvider
     * @return void
     */
    #[DataProvider('isConfiguredDataProvider')]
    public function testIsConfigured(
        bool $expectedResult,
        array $envVariables,
        array $stageConfig,
        string $variableName,
        bool $checkGlobal = false
    ): void {
        $this->environmentMock->expects($this->once())
            ->method('getVariables')
            ->willReturn($envVariables);
        $this->environmentReaderMock->expects($this->any())
            ->method('read')
            ->willReturn($stageConfig);

        $this->assertEquals($expectedResult, $this->checker->isConfigured($variableName, $checkGlobal));
    }

    /**
     * Data provider for isConfigured method.
     *
     * @return array
     */
    public static function isConfiguredDataProvider(): array
    {
        return [
            [
                false,
                [],
                [],
                'SCD_STRATEGY'
            ],
            [
                false,
                ['key' => 'value'],
                [],
                'SCD_STRATEGY'
            ],
            [
                true,
                ['SCD_STRATEGY' => 'quick'],
                [],
                'SCD_STRATEGY'
            ],
            [
                false,
                [],
                [
                    StageConfigInterface::SECTION_STAGE => [
                        StageConfigInterface::STAGE_GLOBAL => [
                            'SCD_STRATEGY' => 'quick'
                        ]
                    ]
                ],
                'SCD_STRATEGY'
            ],
            [
                true,
                [],
                [
                    StageConfigInterface::SECTION_STAGE => [
                        StageConfigInterface::STAGE_DEPLOY => [
                            'SCD_STRATEGY' => 'quick'
                        ]
                    ]
                ],
                'SCD_STRATEGY'
            ],
            [
                true,
                [],
                [
                    StageConfigInterface::SECTION_STAGE => [
                        StageConfigInterface::STAGE_GLOBAL => [
                            'SCD_STRATEGY' => 'quick'
                        ]
                    ]
                ],
                'SCD_STRATEGY',
                true
            ],
        ];
    }

    /**
     * Test isConfigured method with exception.
     *
     * @return void
     */
    public function testIsConfiguredWithException()
    {
        $this->environmentMock->expects($this->once())
            ->method('getVariables')
            ->willReturn([]);
        $this->environmentReaderMock->expects($this->any())
            ->method('read')
            ->willThrowException(new FileSystemException('Some error'));

        $this->assertFalse($this->checker->isConfigured('test'));
    }
}
