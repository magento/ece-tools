<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\StageConfigInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * Data provider for testIsEmpty.
     *
     * @param array $config
     * @param bool $expected
     * @dataProvider isEmptyDataProvider
     */
    #[DataProvider('isEmptyDataProvider')]

    public function testIsEmpty(array $config, bool $expected): void
    {
        $this->assertEquals($expected, $this->configMerger->isEmpty($config));
    }

    /**
     * Data provider for testIsEmpty.
     *
     * @return array
     */
    public static function isEmptyDataProvider(): array
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

    /**
     * Test clear method.
     *
     * @return void
     */
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
     * Data provider for testIsMergeRequired.
     *
     * @param array $config
     * @param bool $expected
     * @dataProvider isMergeRequiredDataProvider
     */
    #[DataProvider('isMergeRequiredDataProvider')]
    public function testIsMergeRequired(array $config, bool $expected): void
    {
        $this->assertEquals($expected, $this->configMerger->isMergeRequired($config));
    }

    /**
     * Data provider for testIsMergeRequired.
     *
     * @return array
     */
    public static function isMergeRequiredDataProvider(): array
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
     * Data provider for testMerge.
     *
     * @param array $baseConfig
     * @param array $configToMerge
     * @param array $expected
     * @dataProvider mergeDataProvider
     */
    #[DataProvider('mergeDataProvider')]
    public function testMerge(array $baseConfig, array $configToMerge, array $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->configMerger->merge($baseConfig, $configToMerge)
        );
    }

    /**
     * Data provider for testMerge.
     *
     * @return array
     */
    public static function mergeDataProvider(): array
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
