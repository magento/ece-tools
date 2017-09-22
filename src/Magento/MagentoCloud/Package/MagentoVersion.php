<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Package;

use Composer\Semver\Comparator;

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
     * @return bool
     */
    public function isGreaterOrEqual(string $version): bool
    {
        $package = $this->manager->getPackage('magento/magento2-base');

        return $this->comparator::compare($package->getVersion(), '>=', $version);
    }
}
