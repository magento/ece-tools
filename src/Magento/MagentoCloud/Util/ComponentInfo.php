<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Composer\Composer;
use Composer\Package\PackageInterface;

class ComponentInfo
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @param Composer $composer
     */
    public function __construct(
        \Composer\Composer $composer
    ) {
        $this->composer = $composer;
    }

    /**
     * Returns info about versions of given components
     *
     * @param array $packages The array of packages names
     * @return string
     */
    public function get(array $packages = ['magento/ece-tools', 'magento/magento2-base']): string
    {
        $repository = $this->composer->getLocker()->getLockedRepository();

        $versions = [];
        foreach ($packages as $packageName) {
            $package = $repository->findPackage($packageName, '*');
            if ($package instanceof PackageInterface) {
                $versions[] = sprintf(
                    '%s version: %s',
                    $package->getPrettyName(),
                    $package->getPrettyVersion()
                );
            }
        }

        return '(' . implode(', ', $versions) . ')';
    }
}
