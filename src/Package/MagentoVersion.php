<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Package;

use Composer\Composer;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\GlobalSection;

/**
 * Defines methods for comparing version constraints with base Magento package.
 */
class MagentoVersion
{
    /**
     * Minimum supported Magento version.
     */
    public const MIN_VERSION = '2.1.14';

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
     * @var GlobalSection
     */
    private $globalSection;

    /**
     * @var string
     */
    private $version;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * MagentoVersion class constructor.
     *
     * @param Manager $manager
     * @param Comparator $comparator
     * @param Semver $semver
     * @param GlobalSection $globalSection
     * @param Composer $composer
     */
    public function __construct(
        Manager $manager,
        Comparator $comparator,
        Semver $semver,
        GlobalSection $globalSection,
        Composer $composer
    ) {
        $this->manager = $manager;
        $this->comparator = $comparator;
        $this->semver = $semver;
        $this->globalSection = $globalSection;
        $this->composer = $composer;
    }

    /**
     * Extracts application version.
     *
     * @return string
     * @throws UndefinedPackageException
     */
    public function getVersion(): string
    {
        if (null !== $this->version) {
            return $this->version;
        }

        try {
            if ($this->globalSection->get(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)) {
                return $this->version = $this->normalizeVersion(
                    $this->globalSection->get(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)
                );
            }
        } catch (ConfigException $exception) {
            throw new UndefinedPackageException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($this->manager->has('magento/magento2-base')) {
            return $this->version = $this->normalizeVersion($this->manager->get('magento/magento2-base')->getVersion());
        }

        if ($version = $this->composer->getPackage()->getPrettyVersion()) {
            return $this->version = $this->normalizeVersion($version);
        }

        throw new UndefinedPackageException('Magento version cannot be resolved');
    }

    /**
     * Normalize version string by removing trailing .0 from patch version if it exists.
     * This is needed to ensure that versions like
     * 2.4.6.0 becomes 2.4.6, which is the format used in Composer constraints.
     *
     * @param string $version
     * @return string
     */
    private function normalizeVersion(string $version): string
    {
        return preg_replace('/^(\d+\.\d+\.\d+)\.0(-patch\d+)$/', '$1$2', $version) ?? $version;
    }

    /**
     * Check if Magento is installed from Git.
     *
     * @return bool
     * @throws ConfigException
     */
    public function isGitInstallation(): bool
    {
        if ($this->globalSection->get(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)) {
            return true;
        }

        if ($this->manager->has('magento/magento2-base')) {
            return false;
        }

        if ($this->composer->getPackage()->getPrettyVersion()) {
            return true;
        }

        throw new ConfigException('Version cannot be determined');
    }

    /**
     * Check if the current Magento version is greater than or equal to the specified version.
     *
     * @param string $version
     * @return bool
     * @throws UndefinedPackageException
     */
    public function isGreaterOrEqual(string $version): bool
    {
        return $this->comparator::compare($this->getVersion(), '>=', $version);
    }

    /**
     * Check the current Magento version against Composer-style constraints.
     *
     * @param string $constraints
     * @return bool
     * @throws UndefinedPackageException
     */
    public function satisfies(string $constraints): bool
    {
        return $this->semver::satisfies($this->getVersion(), $constraints);
    }
}
