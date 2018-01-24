<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Determine which SCD strategies are allowed and should be used in the installed version of Magento.
 *
 * SCD stands for Static Content Deployment.
 *
 * @package Magento\MagentoCloud\Config
 */
class ScdStrategyChecker
{
    /**
     * Default index for the allowed strategies arrays.
     */
    const FALLBACK_OFFSET = 0;

    /**
     * Default strategies that are allowed by the selector.
     */
    const DEFAULT_ALLOWED_STRATEGIES = [
        '2.1.*' => ['standard'],
        '>=2.2' => ['standard', 'quick', 'compact'],
    ];

    /**
     * Default fallback used if the current version doesn't match any in the default allowed list.
     */
    const FALLBACK_ALLOWED_STRATEGIES = ['standard'];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var array
     */
    private $defaultAllowedStrategies;

    /**
     * @var array
     */
    private $fallbackAllowedStrategies;

    /**
     * @param Logger $logger
     * @param MagentoVersion $magentoVersion
     * @param array $defaultAllowedStrategies
     * @param array $fallbackAllowedStrategies
     */
    public function __construct(
        Logger $logger,
        MagentoVersion $magentoVersion,
        array $defaultAllowedStrategies = self::DEFAULT_ALLOWED_STRATEGIES,
        array $fallbackAllowedStrategies = self::FALLBACK_ALLOWED_STRATEGIES
    ) {
        $this->logger = $logger;
        $this->magentoVersion = $magentoVersion;

        // The first strategy (at array index self::FALLBACK_OFFSET) for a given version
        // is the one used if the user specifies an invalid strategy.
        $this->defaultAllowedStrategies = $defaultAllowedStrategies;
        $this->fallbackAllowedStrategies = $fallbackAllowedStrategies;
    }

    /**
     * Decide on a single SCD strategy, considering user preference and allowed strategies.
     *
     * @param string $desiredStrategy
     * @param array $allowedStrategies
     *
     * @return string
     */
    public function getStrategy(string $desiredStrategy, array $allowedStrategies): string
    {
        if (in_array($desiredStrategy, $allowedStrategies)) {
            return $desiredStrategy;
        }

        if (!array_key_exists(static::FALLBACK_OFFSET, $allowedStrategies)) {
            throw new \OutOfRangeException(
                "Tried to access an allowed strategy that doesn't exist. "
                . "Ensure that there's at least one allowed strategy."
            );
        }

        $usedStrategy = (string) $allowedStrategies[static::FALLBACK_OFFSET];

        if ($desiredStrategy) {
            // Only log a warning if the user has an SCD strategy preference.
            $this->logger->warning(
                "The desired static content deployment strategy is not on the list of allowed strategies . "
                . "Make sure that the desired strategy is valid for this version of Magento . "
                . "The default strategy for this version of Magento will be used instead . ",
                [
                    "desiredStrategy"   => $desiredStrategy,
                    "allowedStrategies" => $allowedStrategies,
                    "usedStrategy"      => $usedStrategy,
                ]
            );
        }

        return $usedStrategy;
    }

    /**
     * Get allowed SCD strategies for the installed Magento version if possible.
     *
     * @return string[] List of SCD strategies allowed in the current version of Magento.
     */
    public function getAllowedStrategies(): array
    {
        $currentMatchingVersion = $this->getCurrentMatchingVersion();
        if ($currentMatchingVersion) {
            return $this->getAllowedStrategiesByVersion($currentMatchingVersion);
        }

        return $this->fallbackAllowedStrategies;
    }

    /**
     * Get whichever key in $this->defaultStrategies that matches the installed Magento version.
     *
     * @return bool|string
     */
    private function getCurrentMatchingVersion()
    {
        foreach (array_keys($this->defaultAllowedStrategies) as $versionConstraint) {
            if ($this->magentoVersion->satisfies($versionConstraint)) {
                return $versionConstraint;
            }
        }

        return false;
    }

    /**
     * Get SCD strategies belonging to a matching version string.
     *
     * @param string $detectedVersion
     *
     * @return bool|string[]
     */
    private function getAllowedStrategiesByVersion(string $detectedVersion)
    {
        if (array_key_exists($detectedVersion, $this->defaultAllowedStrategies)) {
            return $this->defaultAllowedStrategies[$detectedVersion];
        }

        return false;
    }
}
