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
     * @param Manager $manager
     * @param Comparator $comparator
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
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
        return Comparator::compare($this->getVersion(), '>=', $version);
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
