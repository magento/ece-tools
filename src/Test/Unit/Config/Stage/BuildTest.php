<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config\Stage;

use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\Build;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class BuildTest extends TestCase
{
    /**
     * @var Build
     */
    private $config;

    /**
     * @var EnvironmentReader|Mock
     */
    private $environmentReaderMock;

    /**
     * @var Schema|Mock
     */
    private $schemaMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentReaderMock = $this->createMock(EnvironmentReader::class);
        $this->schemaMock = $this->createMock(Schema::class);
        $this->schemaMock->expects($this->any())
            ->method('getDefaults')
            ->with(StageConfigInterface::STAGE_BUILD)
            ->willReturn([
                BuildInterface::VAR_SCD_STRATEGY => '',
                BuildInterface::VAR_SKIP_SCD => false,
                BuildInterface::VAR_SCD_COMPRESSION_LEVEL => 6,
                BuildInterface::VAR_SCD_THREADS => 1,
                BuildInterface::VAR_SCD_EXCLUDE_THEMES => '',
                BuildInterface::VAR_VERBOSE_COMMANDS => '',
                BuildInterface::VAR_SCD_MATRIX => [],
            ]);

        $this->config = new Build(
            $this->environmentReaderMock,
            $this->schemaMock
        );
    }

    /**
     * @param string $name
     * @param array $envConfig
     * @param mixed $expectedValue
     * @dataProvider getDataProvider
     */
    public function testGet(string $name, array $envConfig, $expectedValue)
    {
        $this->environmentReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([Build::SECTION_STAGE => $envConfig]);

        $this->assertSame($expectedValue, $this->config->get($name));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDataProvider(): array
    {
        return [
            'default strategy' => [
                Build::VAR_SCD_STRATEGY,
                [],
                '',
            ],
            'env configured strategy' => [
                Build::VAR_SCD_STRATEGY,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [
                        Build::VAR_SCD_STRATEGY => 'simple',
                    ],
                ],
                'simple',
            ],
            'global env strategy' => [
                Build::VAR_SCD_STRATEGY,
                [
                    StageConfigInterface::STAGE_GLOBAL => [
                        Build::VAR_SCD_STRATEGY => 'simple',
                    ],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                'simple',
            ],
            'default strategy with parameter' => [
                Build::VAR_SCD_STRATEGY,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                '',
            ],
            'default exclude_themes' => [
                Build::VAR_SCD_EXCLUDE_THEMES,
                [],
                '',
            ],
            'env configured exclude_themes' => [
                Build::VAR_SCD_EXCLUDE_THEMES,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [
                        Build::VAR_SCD_EXCLUDE_THEMES => 'luma',
                    ],
                ],
                'luma',
            ],
            'global env exclude_themes' => [
                Build::VAR_SCD_EXCLUDE_THEMES,
                [
                    StageConfigInterface::STAGE_GLOBAL => [
                        Build::VAR_SCD_EXCLUDE_THEMES => 'luma',
                    ],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                'luma',
            ],
            'default exclude_themes with parameter' => [
                Build::VAR_SCD_EXCLUDE_THEMES,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                '',
            ],
            'default compress level' => [
                Build::VAR_SCD_COMPRESSION_LEVEL,
                [],
                6,
            ],
            'env configured compress level' => [
                Build::VAR_SCD_COMPRESSION_LEVEL,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [
                        Build::VAR_SCD_COMPRESSION_LEVEL => 5,
                    ],
                ],
                5,
            ],
            'global env compress level' => [
                Build::VAR_SCD_COMPRESSION_LEVEL,
                [
                    StageConfigInterface::STAGE_GLOBAL => [
                        Build::VAR_SCD_COMPRESSION_LEVEL => 5,
                    ],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                5,
            ],
            'default compress level with parameter' => [
                Build::VAR_SCD_COMPRESSION_LEVEL,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                6,
            ],
            'default scd_threads' => [
                Build::VAR_SCD_THREADS,
                [],
                1,
            ],
            'env configured scd_threads' => [
                Build::VAR_SCD_THREADS,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [
                        Build::VAR_SCD_THREADS => 5,
                    ],
                ],
                5,
            ],
            'global env scd_threads' => [
                Build::VAR_SCD_THREADS,
                [
                    StageConfigInterface::STAGE_GLOBAL => [
                        Build::VAR_SCD_THREADS => 5,
                    ],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                5,
            ],
            'default scd_threads with parameter' => [
                Build::VAR_SCD_THREADS,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                1,
            ],
            'default skip_scd' => [
                Build::VAR_SKIP_SCD,
                [],
                false,
            ],
            'env configured skip_scd' => [
                Build::VAR_SKIP_SCD,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [
                        Build::VAR_SKIP_SCD => true,
                    ],
                ],
                true,
            ],
            'global env skip_scd' => [
                Build::VAR_SKIP_SCD,
                [
                    StageConfigInterface::STAGE_GLOBAL => [
                        Build::VAR_SKIP_SCD => true,
                    ],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                true,
            ],
            'default skip_scd with parameter' => [
                Build::VAR_SKIP_SCD,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                false,
            ],
            'default verbose commands' => [
                Build::VAR_VERBOSE_COMMANDS,
                [],
                '',
            ],
            'env configured verbose commands' => [
                Build::VAR_VERBOSE_COMMANDS,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [
                        Build::VAR_VERBOSE_COMMANDS => '-v',
                    ],
                ],
                '-v',
            ],
            'global env verbose commands' => [
                Build::VAR_VERBOSE_COMMANDS,
                [
                    StageConfigInterface::STAGE_GLOBAL => [
                        Build::VAR_VERBOSE_COMMANDS => '-v',
                    ],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                '-v',
            ],
            'default verbose commands with parameter' => [
                Build::VAR_VERBOSE_COMMANDS,
                [
                    StageConfigInterface::STAGE_GLOBAL => [],
                    StageConfigInterface::STAGE_BUILD => [],
                ],
                '',
            ],
            'scd strategy default' => [
                Build::VAR_SCD_STRATEGY,
                [],
                ''
            ]
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Config NOT_EXISTS_VALUE was not defined.
     */
    public function testNotExists()
    {
        $this->environmentReaderMock->expects($this->any())
            ->method('read')
            ->willReturn([]);

        $this->config->get('NOT_EXISTS_VALUE');
    }
}
