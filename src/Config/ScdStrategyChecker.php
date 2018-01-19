<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Psr\Log\LoggerInterface;
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
    /**
     * @var LoggerInterface
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
     * @param LoggerInterface $logger
     * @param MagentoVersion  $magentoVersion
     */
    public function __construct(
        LoggerInterface $logger,
        MagentoVersion $magentoVersion
    ) {
        $this->logger = $logger;
        $this->magentoVersion = $magentoVersion;

        $this->defaultStrategies = [
            '2.1.*' => ['standard'],
            '2.2.*' => ['standard', 'quick', 'compact'],
        ];
        $this->fallbackStrategies = ['standard'];
    }

    /**
     * Get allowed SCD strategies for the installed Magento version if possible.
     *
     * @return string[] List of SCD strategies allowed in the current version of Magento.
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
    private function getStrategiesByVersion(string $detectedVersion) {
        foreach ($this->defaultStrategies as $thisVersion => $theseStrategies) {
            // Testing strict equality on strpos() is preferred to regular expressions in this simple case.
            if (strpos($detectedVersion, $thisVersion) === 0) {
                return $theseStrategies;
            }
        }

        return false;
    }
}