<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\StaticContent;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;
use Magento\MagentoCloud\Util\Cpu;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
#[AllowMockObjectsWithoutExpectations]
class ThreadCountOptimizerTest extends TestCase
{
    /**
     * @var ThreadCountOptimizer
     */
    private $optimizer;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var Cpu|MockObject
     */
    private $cpuMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->cpuMock = $this->createMock(Cpu::class);

        $this->optimizer = new ThreadCountOptimizer(
            $this->loggerMock,
            $this->magentoVersionMock,
            $this->cpuMock
        );
    }

    /**
     * Test optimize method.
     *
     * @param bool $magentoVersionSatisfies
     * @param int $threadCount
     * @param string $strategy
     * @param int $expectedThreadCount
     * @dataProvider optimizeDataProvider
     * @return void
     * @throws \ReflectionException
     */
    #[DataProvider('optimizeDataProvider')]
    public function testOptimize(
        bool $magentoVersionSatisfies,
        int $threadCount,
        string $strategy,
        int $expectedThreadCount
    ): void {
        $this->magentoVersionMock->expects($this->any())
            ->method('satisfies')
            ->willReturn($magentoVersionSatisfies);
        $this->assertEquals(
            $expectedThreadCount,
            $this->optimizer->optimize($threadCount, $strategy)
        );
    }

    /**
     * Data provider for optimize method.
     *
     * @return array
     */
    public static function optimizeDataProvider(): array
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

    /**
     * Test optimize with notice.
     *
     * @return void
     */
    public function testOptimizeWithNotice(): void
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

    /**
     * Test optimize with optimal value.
     *
     * @return void
     */
    public function testOptimizeWithOptimalValue(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>2.1.10')
            ->willReturn(true);
        $this->cpuMock->expects($this->once())
            ->method('getThreadsCount')
            ->willReturn(8);

        $this->assertEquals(
            ThreadCountOptimizer::THREAD_COUNT_OPTIMAL,
            $this->optimizer->optimize(StageConfigInterface::VAR_SCD_THREADS_DEFAULT_VALUE, 'quick')
        );
    }

    /**
     * Test optimize with CPU threads count lower optimal value.
     *
     * @return void
     */
    public function testOptimizeWithCpuThreadsCountLowerOptimalValue(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('satisfies')
            ->with('>2.1.10')
            ->willReturn(true);
        $this->cpuMock->expects($this->once())
            ->method('getThreadsCount')
            ->willReturn(2);

        $this->assertEquals(
            2,
            $this->optimizer->optimize(StageConfigInterface::VAR_SCD_THREADS_DEFAULT_VALUE, 'quick')
        );
    }
}
