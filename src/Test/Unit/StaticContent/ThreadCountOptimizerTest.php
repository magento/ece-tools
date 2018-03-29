<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
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

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->optimizer = new ThreadCountOptimizer(
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    /**
     * @param bool $magentoVersionSatisfies
     * @param int $threadCount
     * @param string $strategy
     * @param int $expectedThreadCount
     * @dataProvider optimizeDataProvider
     */
    public function testOptimize(
        bool $magentoVersionSatisfies,
        int $threadCount,
        string $strategy,
        int $expectedThreadCount
    ) {
        $this->magentoVersionMock->expects($this->any())
            ->method('satisfies')
            ->willReturn($magentoVersionSatisfies);
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
                true,
                3,
                ThreadCountOptimizer::STRATEGY_COMPACT,
                1,
            ],
            [
                false,
                3,
                ThreadCountOptimizer::STRATEGY_COMPACT,
                3,
            ],
            [
                false,
                5,
                'SomeStrategy',
                5,
            ],
            [
                false,
                1,
                'SomeStrategy',
                1,
            ],
        ];
    }

    public function testOptimizeWithNotice()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Threads count was forced to 1 as compact strategy can\'t be run with more than one job');

        $this->assertEquals(
            1,
            $this->optimizer->optimize(3, ThreadCountOptimizer::STRATEGY_COMPACT)
        );
    }
}
