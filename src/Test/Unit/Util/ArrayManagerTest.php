<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
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
    protected function setUp()
    {
        $this->manager = new ArrayManager();
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
