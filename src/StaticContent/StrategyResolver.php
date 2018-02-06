<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Determine which SCD strategies are allowed and should be used in the installed version of Magento.
 * SCD stands for Static Content Deployment.
 */
class StrategyResolver
{
    const DEFAULT_STRATEGY = 'standard';

    /**
     * Default strategies that are allowed by the selector.
     */
    const ALLOWED_STRATEGIES = [
        '2.1.*' => [self::DEFAULT_STRATEGY],
        '>=2.2' => [self::DEFAULT_STRATEGY, 'quick', 'compact'],
    ];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param Logger $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        Logger $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->logger = $logger;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Decide on a single SCD strategy, considering user preference and allowed strategies.
     *
     * @param string $desiredStrategy
     * @return string
     */
    public function getStrategy(string $desiredStrategy): string
    {
        foreach (self::ALLOWED_STRATEGIES as $constraint => $allowedStrategies) {
            if (!$this->magentoVersion->satisfies($constraint)) {
                continue;
            }

            return in_array($desiredStrategy, $allowedStrategies)
                ? $desiredStrategy
                : self::DEFAULT_STRATEGY;
        }

        return self::DEFAULT_STRATEGY;
    }
}
