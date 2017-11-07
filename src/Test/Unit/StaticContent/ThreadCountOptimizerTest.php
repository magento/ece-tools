<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;
use PHPUnit\Framework\TestCase;

class ThreadCountOptimizerTest extends TestCase
{
    /**
     * @var ThreadCountOptimizer
     */
    private $optimizer;

    protected function setUp()
    {
        $this->optimizer = new ThreadCountOptimizer();
    }

    /**
     * @param int $threadCount
     * @param string $strategy
     * @param int $expectedThreadCount
     * @dataProvider optimizeDataProvider
     */
    public function testOptimize(int $threadCount, string $strategy, int $expectedThreadCount)
    {
        $this->assertEquals(
            $expectedThreadCount,
            $this->optimizer->optimize($threadCount, $strategy)
        );
    }

    /**
     * @return array
     */
    public function optimizeDataProvider(): array
    {
        return [
            [
                3,
                ThreadCountOptimizer::STRATEGY_COMPACT,
                1
            ],
            [
                5,
                'SomeStrategy',
                5
            ],
            [
                1,
                'SomeStrategy',
                1
            ]
        ];
    }
}
