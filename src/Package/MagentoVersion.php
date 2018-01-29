<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Package;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;

/**
 * Class MagentoVersion
 *
 * @package Magento\MagentoCloud\Package
 */
class MagentoVersion
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Comparator
     */
    private $comparator;

    /**
     * @var Semver
     */
    private $semver;

    /**
     * @param Manager $manager
     * @param Comparator $comparator
     * @param Semver $semver
     */
    public function __construct(Manager $manager, Comparator $comparator, Semver $semver)
    {
        $this->manager = $manager;
        $this->comparator = $comparator;
        $this->semver = $semver;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->manager->get('magento/magento2-base')->getVersion();
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    public function isGreaterOrEqual(string $version): bool
    {
        return $this->comparator::compare($this->getVersion(), '>=', $version);
    }

    /**
     * Compares version between 2 constraints.
     *
     * @param string $versionFrom
     * @param string $versionTo
     * @return bool
     */
    public function isBetween(string $versionFrom, string $versionTo): bool
    {
        return $this->comparator::compare($this->getVersion(), '>=', $versionFrom)
            && $this->comparator::compare($this->getVersion(), '<', $versionTo);
    }

    /**
     * Check the current Magento version against Composer-style constraints.
     *
     * @param string $constraints
     *
     * @return bool
     */
    public function satisfies(string $constraints): bool
    {
        return $this->semver::satisfies($this->getVersion(), $constraints);
    }
}
