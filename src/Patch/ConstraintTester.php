<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Patch;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\WritableRepositoryInterface;
use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Provides apply methods for patches.
 */
class ConstraintTester
{
    /**
     * @var WritableRepositoryInterface
     */
    private $repository;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var GlobalSection
     */
    private $globalSection;

    /**
     * @param Composer $composer
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param File $file
     * @param GlobalSection $globalSection
     */
    public function __construct(
        Composer $composer,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        File $file,
        GlobalSection $globalSection
    ) {
        $this->composer = $composer;
        $this->repository = $composer->getRepositoryManager()->getLocalRepository();
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->globalSection = $globalSection;
    }

    /**
     * Tests to see whether or not constraint should apply.
     *
     * @param string $path Path to patch
     * @param string|null $packageName Name of package to be patched
     * @param string|null $constraint Specific constraint of package to be fixed
     * @return string|null
     * @throws \RuntimeException
     */
    public function testConstraint(string $path, string $packageName = null, string $constraint = null)
    {
        /**
         * Support for relative paths.
         */
        if (!$this->file->isExists($path)) {
            $path = $this->directoryList->getPatches() . '/' . $path;
        }
        if ($packageName && !$this->matchConstraint($packageName, $constraint)) {
            return null;
        }
        return $path;
    }

    /**
     * Checks whether package with specific constraint exists in the system.
     *
     * @param string $packageName
     * @param string $constraint
     * @return bool True if patch with provided constraint exists, false otherwise.
     */
    private function matchConstraint(string $packageName, string $constraint): bool
    {
        return $this->repository->findPackage($packageName, $constraint) instanceof PackageInterface;
    }
}
