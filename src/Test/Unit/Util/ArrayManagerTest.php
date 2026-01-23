<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Util\ArrayManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ArrayManagerTest extends TestCase
{
    /**
     * @var ArrayManager
     */
    private $manager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->manager = new ArrayManager();
    }

    /**
     * Test flatten method.
     *
     * @param array $value
     * @param string $prefix
     * @param array $expected
     * @dataProvider flattenDataProvider
     * @return void
     */
    #[DataProvider('flattenDataProvider')]
    public function testFlatten(array $value, string $prefix, array $expected): void
    {
        $this->assertSame($expected, $this->manager->flatten($value, $prefix));
    }

    /**
     * Data provider for flatten method.
     *
     * @return array
     */
    public static function flattenDataProvider(): array
    {
        return [
            [
                ['test'],
                '',
                ['test'],
            ],
            [
                [
                    'test' => [
                        'test2' => 'value2',
                    ],
                ],
                '#',
                [
                    '#test/test2' => 'value2',
                ],
            ],
            [
                [
                    'test' => [
                        'test2' => 'value2',
                    ],
                ],
                '',
                [
                    'test/test2' => 'value2',
                ],
            ],
            [
                [
                    'test' => [
                        'test2' => 'value2',
                    ],
                    'test-empty' => [
                        'test2' => [],
                    ]
                ],
                '#',
                [
                    '#test/test2' => 'value2',
                    '#test-empty/test2' => []
                ],
            ],
        ];
    }

    /**
     * Test filter method.
     *
     * @param array $value
     * @param string $pattern
     * @param bool $ending
     * @param array $expected
     * @dataProvider filterDataProvider
     * @return void
     */
    #[DataProvider('filterDataProvider')]
    public function testFilter(array $value, string $pattern, bool $ending, array $expected): void
    {
        $this->assertSame($expected, $this->manager->filter($value, $pattern, $ending));
    }

    /**
     * Data provider for filter method.
     *
     * @return array
     */
    public static function filterDataProvider(): array
    {
        return [
            [
                [
                    'some/admin_user/locale/code' => 'en_US',
                ],
                'admin_user/locale/code',
                false,
                [],
            ],
            [
                [
                    'admin_user/locale/code' => [],
                ],
                'admin_user/locale/code',
                false,
                [],
            ],
            [
                [
                    'admin_user/locale/code' => 'en_US',
                ],
                'admin_user/locale/code',
                false,
                ['en_US'],
            ],
            [
                [
                    'admin_user/locale/code' => 'en_US',
                ],
                'admin_user/locale/code',
                true,
                ['en_US'],
            ],
        ];
    }

    /**
     * Test nest method.
     *
     * @param array $expected
     * @param array $original
     * @param array $keys
     * @param string|int $val
     * @dataProvider nestDataProvider
     * @return void
     */
    #[DataProvider('nestDataProvider')]
    public function testNest(array $expected, array $original, array $keys, $val): void
    {
        $this->assertSame($expected, $this->manager->nest($original, $keys, $val));
    }

    /**
     * Data provider for nest method.
     *
     * @return array
     */
    public static function nestDataProvider(): array
    {
        return [
            'simple' => [
                ['test' => 'one'],
                [],
                ['test'],
                'one',
            ],
            'multiple' => [
                ['test' => ['test2' => 'one']],
                [],
                ['test', 'test2'],
                'one',
            ],
            'appending' => [
                ['test_old' => 'two', 'test' => ['test2' => 'one']],
                ['test_old' => 'two'],
                ['test', 'test2'],
                'one',
            ],
        ];
    }
}
