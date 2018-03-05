<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Patch;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\WritableRepositoryInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides apply methods for patches.
 */
class Applier
{
    /**
     * @var WritableRepositoryInterface
     */
    private $repository;

    /**
     * @var ShellInterface
     */
    private $shell;

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
     * @param Composer $composer
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        Composer $composer,
        ShellInterface $shell,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->composer = $composer;
        $this->repository = $composer->getRepositoryManager()->getLocalRepository();
        $this->shell = $shell;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * Applies patch, using 'git apply' command.
     *
     * If the patch fails to apply, checks if it has already been applied which is considered ok.
     *
     * @param string $path Path to patch
     * @param string|null $name Name of patch
     * @param string|null $packageName Name of package to be patched
     * @param string|null $constraint Specific constraint of package to be fixed
     * @return void
     * @throws \RuntimeException
     */
    public function apply(string $path, string $name = null, string $packageName = null, $constraint = null)
    {
        /**
         * Support for relative paths.
         */
        if (!$this->file->isExists($path)) {
            $path = $this->directoryList->getPatches() . '/' . $path;
        }

        if ($packageName && !$this->matchConstraint($packageName, $constraint)) {
            return;
        }

        $name = $name ? sprintf('%s (%s)', $name, $path) : $path;
        $format = 'Applying patch ' . ($constraint ? '%s %s.' : '%s.');

        $this->logger->info(sprintf(
            $format,
            $name,
            $constraint
        ));

        try {
            $this->shell->execute('git apply ' . $path);
        } catch (\RuntimeException $applyException) {
            try {
                $this->shell->execute('git apply --check --reverse ' . $path);
            } catch (\RuntimeException $reverseException) {
                throw $applyException;
            }

            $this->logger->notice("Patch {$name} was already applied.");
        }

        $this->logger->info('Done.');
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
