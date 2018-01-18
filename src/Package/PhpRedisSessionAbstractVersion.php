<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Package;

use Composer\Semver\Comparator;

/**
 * Operates with versions of colinmollenhour/php-redis-session-abstract package.
 */
class PhpRedisSessionAbstractVersion
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
     * Checks that version of package is greater than given version.
     *
     * @param string $version
     * @return bool
     * @throws \Exception If package not found
     */
    public function isGreaterThan(string $version): bool
    {
        $package = $this->manager->get('colinmollenhour/php-redis-session-abstract');

        return $this->comparator::compare($package->getVersion(), '>', $version);
    }
}
