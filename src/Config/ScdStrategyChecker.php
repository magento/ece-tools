<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Determine which SCD strategies are allowed in the installed version of Magento.
 *
 * SCD stands for Static Content Deployment.
 *
 * @package Magento\MagentoCloud\Config
 */
class ScdStrategyChecker
{
    const FALLBACK_OFFSET = 0;

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
    private $defaultStrategies;

    /**
     * @var array
     */
    private $fallbackStrategies;

    /**
     * @param Logger         $logger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        Logger $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->logger = $logger;
        $this->magentoVersion = $magentoVersion;

        // The first strategy (at array index 0) for a given version
        // is the one used if the user specifies an invalid strategy.
        $this->defaultStrategies = [
            '2.1.*' => ['standard'],
            '2.2.*' => ['standard', 'quick', 'compact'],
        ];
        $this->fallbackStrategies = ['standard'];
    }

    public function getStrategy(string $desiredStrategy, array $allowedStrategies): string
    {
        if (in_array($desiredStrategy, $allowedStrategies)) {
            return $desiredStrategy;
        }

        if (!array_key_exists(static::FALLBACK_OFFSET, $allowedStrategies)) {
            $usedStrategy = str($allowedStrategies[static::FALLBACK_OFFSET]);
        }

        $this->logger->warning(
            "The desired static content deployment strategy is not on the list of allowed strategies. "
            . "Make sure that the desired strategy is valid for this version of Magento. "
            . "The default strategy for this version of Magento will be used instead.",
            [
                "desiredStrategy"   => $desiredStrategy,
                "allowedStrategies" => $allowedStrategies,
                "usedStrategy"      => $usedStrategy,
            ]
        );

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
            return $this->getStrategiesByVersion($currentMatchingVersion);
        }

        return $this->fallbackStrategies;
    }

    /**
     * Get whichever key in $this->defaultStrategies that matches the installed Magento version.
     *
     * @return bool|string
     */
    private function getCurrentMatchingVersion()
    {
        foreach (array_keys($this->defaultStrategies) as $versionConstraint) {
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
    private function getStrategiesByVersion(string $detectedVersion)
    {
        foreach ($this->defaultStrategies as $thisVersion => $theseStrategies) {
            // Testing strict equality on strpos() is preferred to regular expressions in this simple case.
            if (strpos($detectedVersion, $thisVersion) === 0) {
                return $theseStrategies;
            }
        }

        return false;
    }
}