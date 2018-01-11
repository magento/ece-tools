<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

use Psr\Log\LoggerInterface;

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
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        if ($strategy === self::STRATEGY_COMPACT) {
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
