<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Util\Cpu;
use Psr\Log\LoggerInterface;

/**
 * Performs thread optimization according to the strategy.
 */
class ThreadCountOptimizer
{
    /**
     * @var string
     */
    const STRATEGY_COMPACT = 'compact';

    /**
     * Recommended tread count value for compact strategy before 2.2.4 version.
     */
    const THREAD_COUNT_COMPACT_STRATEGY_BEFORE_2_2_4 = 1;

    /**
     * Optimal value for job count if environment has enough cpu cores.
     */
    const THREAD_COUNT_OPTIMAL = 4;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var Cpu
     */
    private $cpu;

    /**
     * @param LoggerInterface $logger
     * @param MagentoVersion $magentoVersion
     * @param Cpu $cpu
     */
    public function __construct(LoggerInterface $logger, MagentoVersion $magentoVersion, Cpu $cpu)
    {
        $this->logger = $logger;
        $this->magentoVersion = $magentoVersion;
        $this->cpu = $cpu;
    }

    /**
     * Defines best thread count value based on deploy strategy name
     *
     * @param int $threads
     * @param string $strategy
     * @return int
     */
    public function optimize(int $threads, string $strategy): int
    {
        /**
         * Version of construction of Magento for which the number of threads will be forced to change
         * in the case of using a compact strategy of static content deployment
         */
        if ($strategy === self::STRATEGY_COMPACT && $this->magentoVersion->satisfies('<2.2.4')) {
            if ($threads !== self::THREAD_COUNT_COMPACT_STRATEGY_BEFORE_2_2_4) {
                $this->logger->notice(
                    'Threads count was forced to 1 as compact strategy can\'t be run with more than one job'
                );
            }

            return self::THREAD_COUNT_COMPACT_STRATEGY_BEFORE_2_2_4;
        }

        if ($threads === StageConfigInterface::VAR_SCD_THREADS_DEFAULT_VALUE) {
            $threads = $this->magentoVersion->satisfies('>2.1.10') ?
                min($this->cpu->getThreadsCount(), self::THREAD_COUNT_OPTIMAL) : 1;
        }

        return $threads;
    }
}
