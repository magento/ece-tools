<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Package\MagentoVersion;

// SCD stands for Static Content Deployment
class ScdStrategyChecker
{
    private $logger;

    private $magentoVersion;

    private $defaultStrategies;

    private $fallbackStrategies;

    public function __construct(
        LoggerInterface $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->logger = $logger;
        $this->magentoVersion = $magentoVersion;

        $this->defaultStrategies = [
            '2.1' => ['standard'],
            '2.2' => ['standard', 'quick', 'compact'],
        ];
        $this->fallbackStrategies = ['standard'];
    }

    /**
     * Get allowed SCD strategies for the installed Magento version if possible.
     *
     * @return string[]
     */
    public function getAllowedStrategies(): array {
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
    private function getCurrentMatchingVersion() {
        $matchCounts = [];

        // Assume the associative array is unsorted
        foreach (array_keys($this->defaultStrategies) as $searchVersion) {
            if (!array_key_exists($searchVersion, $matchCounts)) {
                $matchCounts[$searchVersion] = 0;
            }

            if ($this->magentoVersion->isGreaterOrEqual($searchVersion)) {
                $matchCounts[$searchVersion]++;
            }
        }

        $lowestCount = null;
        $lowestVersion = null;

        // Find the lowest version that still matches magentoVersion->isGreaterOrEqual()
        foreach ($matchCounts as $matchVersion => $count) {
            if ($count === 0) {
                continue;
            }

            if (is_null($lowestCount) || $lowestCount > $count) {
                $lowestCount = $count;
                $lowestVersion = $matchVersion;
            }
        }

        if ($lowestVersion) {
            return $lowestVersion;
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
    private function getStrategiesByVersion(string $detectedVersion) {
        foreach ($this->defaultStrategies as $thisVersion => $theseStrategies) {
            if (strpos($detectedVersion, $thisVersion) === 0) {
                return $theseStrategies;
            }
        }
        return false;
    }
}