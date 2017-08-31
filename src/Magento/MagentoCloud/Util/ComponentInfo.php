<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Package\PackageInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;

class ComponentInfo
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @param Factory $composerFactory
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Factory $composerFactory,
        DirectoryList $directoryList
    ) {
        $this->composer = $composerFactory->createComposer(
            new BufferIO(),
            $directoryList->getMagentoRoot() . '/composer.json'
        );
    }

    /**
     * Returns info about versions of given components
     *
     * @param array $packages The array of packages names
     * @return string
     */
    public function get(array $packages = ['magento/ece-tools', 'magento/magento2-base']) : string
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
