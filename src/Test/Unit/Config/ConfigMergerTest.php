<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\StageConfigInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ConfigMergerTest extends TestCase
{
    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->configMerger = new ConfigMerger();
    }

    /**
     * @param array $config
     * @param bool $expected
     * @dataProvider isEmptyDataProvider
     */
    public function testIsEmpty(array $config, bool $expected): void
    {
        $this->assertEquals($expected, $this->configMerger->isEmpty($config));
    }

    /**
     * @return array
     */
    public function isEmptyDataProvider(): array
    {
        return [
            [
                [],
                true,
            ],
            [
                [StageConfigInterface::OPTION_MERGE => true],
                true,
            ],
            [
                ['some_option' => 'value'],
                false,
            ],
            [
                [
                    'some_option1' => 'value',
                    'some_option2' => 'value',
                ],
                false,
            ],
        ];
    }

    public function testClear(): void
    {
        $this->assertSame(
            ['key' => 'value'],
            $this->configMerger->clear([
                'key' => 'value',
                StageConfigInterface::OPTION_MERGE => true
            ])
        );
    }

    /**
     * @param array $config
     * @param bool $expected
     * @dataProvider isMergeRequiredDataProvider
     */
    public function testIsMergeRequired(array $config, bool $expected): void
    {
        $this->assertEquals($expected, $this->configMerger->isMergeRequired($config));
    }

    /**
     * @return array
     */
    public function isMergeRequiredDataProvider(): array
    {
        return [
            [
                [],
                false,
            ],
            [
                [StageConfigInterface::OPTION_MERGE => true],
                false,
            ],
            [
                [StageConfigInterface::OPTION_MERGE => false],
                false,
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => false,
                    'key' => 'value'
                ],
                false,
            ],
            [
                [
                    StageConfigInterface::OPTION_MERGE => true,
                    'key' => 'value'
                ],
                true,
            ],
        ];
    }

    /**
     * @param array $baseConfig
     * @param array $configToMerge
     * @param array $expected
     * @dataProvider mergeDataProvider
     */
    public function testMerge(array $baseConfig, array $configToMerge, array $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->configMerger->merge($baseConfig, $configToMerge)
        );
    }

    /**
     * @return array
     */
    public function mergeDataProvider(): array
    {
        return [
            [
                [],
                [],
                [],
            ],
            [
                ['key' => 'value'],
                [],
                ['key' => 'value'],
            ],
            [
                ['key' => 'value'],
                ['key2' => 'value2'],
                [
                    'key' => 'value'
                ],
            ],
            [
                ['key' => 'value'],
                [
                    'key2' => 'value2',
                    StageConfigInterface::OPTION_MERGE => false,
                ],
                [
                    'key' => 'value',
                ],
            ],
            [
                ['key' => 'value'],
                [
                    'key2' => 'value2',
                    StageConfigInterface::OPTION_MERGE => true,
                ],
                [
                    'key' => 'value',
                    'key2' => 'value2',
                ],
            ],
            [
                [
                    'key' => 'value',
                    'key2' => 'value3',
                ],
                [
                    'key' => 'value2',
                    'key2' => 'value2',
                    StageConfigInterface::OPTION_MERGE => true,
                ],
                [
                    'key' => 'value2',
                    'key2' => 'value2',
                ],
            ],
        ];
    }
}
