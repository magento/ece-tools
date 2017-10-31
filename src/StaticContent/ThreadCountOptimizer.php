<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

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
     * Defines best thread count value based on deploy strategy name
     *
     * @param int $threads
     * @param string $strategy
     * @return int
     */
    public function optimize(int $threads, string $strategy): int
    {
        if ($strategy === self::STRATEGY_COMPACT) {
            return self::THREAD_COUNT_COMPACT_STRATEGY;
        }

        return $threads;
    }
}
