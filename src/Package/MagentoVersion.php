<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Package;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;

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
     */
    public function __construct(Manager $manager, Comparator $comparator, Semver $semver)
    {
        $this->manager = $manager;
        $this->comparator = $comparator;
        $this->semver = $semver;
    }

    /**
     * @param string $version
     * @return bool
     */
    public function isGreaterOrEqual(string $version): bool
    {
        return $this->comparator::compare($this->getVersion(), '>=', $version);
    }
    
    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->manager->get('magento/magento2-base')->getVersion();
    }

    /**
     * Check if a given version matches a Composer constraint string.
     *
     * @param string $version
     * @param string $constraints
     *
     * @return bool
     */
    public function satisfies(string $version, string $constraints): bool {
        return $this->semver::satisfies($version, $constraints);
    }
}
