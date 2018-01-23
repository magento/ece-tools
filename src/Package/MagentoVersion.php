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
     * @param Manager $manager
     * @param Comparator $comparator
     */
    public function __construct(Manager $manager, Comparator $comparator)
    {
        $this->manager = $manager;
        $this->comparator = $comparator;
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
     * @return string
     */
    public function getVersion(): string
    {
        return $this->manager->get('magento/magento2-base')->getVersion();
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
        return Semver::satisfies($this->getVersion(), $constraints);
    }
}
