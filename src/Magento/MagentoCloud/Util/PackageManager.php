<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Semver\Comparator;

class PackageManager
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Comparator
     */
    private $comparator;

    /**
     * @param Composer $composer
     * @param Comparator $comparator
     */
    public function __construct(
        Composer $composer,
        Comparator $comparator
    ) {
        $this->composer = $composer;
        $this->comparator = $comparator;
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

    /**
     * @param string $packageName
     * @param string $version
     * @return PackageInterface
     * @throws \Exception
     */
    public function getPackage(string $packageName, string $version = '*')
    {
        $repository = $this->composer->getLocker()->getLockedRepository();
        $package = $repository->findPackage($packageName, $version);

        if (!$package instanceof PackageInterface) {
            throw new \Exception('Package was not found');
        }

        return $package;
    }

    /**
     * @param string $version
     * @param string $operator
     * @return bool
     */
    public function hasMagentoVersion(string $version, $operator = '>='): bool
    {
        $package = $this->getPackage('magento/magento2-base');

        return $this->comparator::compare($package->getVersion(), $operator, $version);
    }
}
