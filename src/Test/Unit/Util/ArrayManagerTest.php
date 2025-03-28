<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Util;

use Magento\MagentoCloud\Util\ArrayManager;
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
     * @param array $value
     * @param string $prefix
     * @param array $expected
     * @dataProvider flattenDataProvider
     */
    public function testFlatten(array $value, string $prefix, array $expected)
    {
        $this->assertSame($expected, $this->manager->flatten($value, $prefix));
    }

    /**
     * @return array
     */
    public function flattenDataProvider(): array
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
     * @param array $value
     * @param string $pattern
     * @param bool $ending
     * @param array $expected
     * @dataProvider filterDataProvider
     */
    public function testFilter(array $value, string $pattern, bool $ending, array $expected)
    {
        $this->assertSame($expected, $this->manager->filter($value, $pattern, $ending));
    }

    /**
     * @return array
     */
    public function filterDataProvider(): array
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
     * @param array $expected
     * @param array $original
     * @param array $keys
     * @param string|int $val
     * @dataProvider nestDataProvider
     */
    public function testNest(array $expected, array $original, array $keys, $val)
    {
        $this->assertSame($expected, $this->manager->nest($original, $keys, $val));
    }

    /**
     * @return array
     */
    public function nestDataProvider(): array
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
