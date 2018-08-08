<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Package;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;

/**
 * Composer packages repository manager.
 */
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
    public function getPrettyInfo(array $packages = ['magento/ece-tools', 'magento/magento2-base']): string
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
     * @throws UndefinedPackageException
     */
    public function get(string $packageName, string $version = '*'): PackageInterface
    {
        $package = $this->repository->findPackage($packageName, $version);

        if (!$package instanceof PackageInterface) {
            throw new UndefinedPackageException(sprintf(
                'Package %s:%s was not found',
                $packageName,
                $version
            ));
        }

        return $package;
    }

    /**
     * @param string $packageName
     * @param string $version
     * @return bool
     */
    public function has(string $packageName, string $version = '*'): bool
    {
        return $this->repository->findPackage($packageName, $version) instanceof PackageInterface;
    }

    /**
     * Retrieve required packages from composer.json
     *
     * @return string[]
     */
    public function getRequiredPackageNames(): array
    {
        $packages = [];
        foreach ($this->composer->getPackage()->getRequires() as $link) {
            $packages[] = $link->getTarget();
        }

        return $packages;
    }
}
