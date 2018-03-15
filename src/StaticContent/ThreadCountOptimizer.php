<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

use Magento\MagentoCloud\Package\MagentoVersion;
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
     * Recommended tread count value for compact strategy
     *
     * @var int
     */
    const THREAD_COUNT_COMPACT_STRATEGY = 1;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param LoggerInterface $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(LoggerInterface $logger, MagentoVersion $magentoVersion)
    {
        $this->logger = $logger;
        $this->magentoVersion = $magentoVersion;
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
            if ($threads !== self::THREAD_COUNT_COMPACT_STRATEGY) {
                $this->logger->notice(
                    'Threads count was forced to 1 as compact strategy can\'t be run with more than one job'
                );
            }

            return self::THREAD_COUNT_COMPACT_STRATEGY;
        }

        return $threads;
    }
}
