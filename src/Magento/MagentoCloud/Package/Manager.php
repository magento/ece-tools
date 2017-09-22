<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Package;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;

class Manager
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @param Composer $composer
     */
    public function __construct(
        Composer $composer
    ) {
        $this->composer = $composer;
        $this->repository = $composer->getLocker()->getLockedRepository();
    }

    /**
     * Returns info about versions of given components
     *
     * @param array $packages The array of packages names
     * @return string
     */
    public function get(array $packages = ['magento/ece-tools', 'magento/magento2-base']): string
    {
        $versions = [];
        foreach ($packages as $packageName) {
            $package = $this->repository->findPackage($packageName, '*');
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
    public function getPackage(string $packageName, string $version = '*'): PackageInterface
    {
        $package = $this->repository->findPackage($packageName, $version);

        if (!$package instanceof PackageInterface) {
            throw new \Exception('Package was not found');
        }

        return $package;
    }
}
