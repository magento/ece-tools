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
    const FORCE_CHANGE_THREAD_COUNT_COMPACT_STRATEGY_VERSION_CONSTRAINT = '<2.2.4';
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
        if ($strategy === self::STRATEGY_COMPACT
            && $this->magentoVersion->satisfies(self::FORCE_CHANGE_THREAD_COUNT_COMPACT_STRATEGY_VERSION_CONSTRAINT)
        ) {
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
