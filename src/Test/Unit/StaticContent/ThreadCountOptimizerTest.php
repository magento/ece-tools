<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class ThreadCountOptimizerTest extends TestCase
{
    /**
     * @var ThreadCountOptimizer
     */
    private $optimizer;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->optimizer = new ThreadCountOptimizer($this->loggerMock);
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

    public function testOptimizeWithNotice()
    {
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Threads count was forced to 1 as compact strategy can\'t be run with more than one job');

        $this->assertEquals(
            1,
            $this->optimizer->optimize(3, ThreadCountOptimizer::STRATEGY_COMPACT)
        );
    }
}
