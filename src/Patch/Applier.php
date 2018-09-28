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
use Magento\MagentoCloud\Shell\ShellException;
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
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param File $file
     * @param GlobalSection $globalSection
     */
    public function __construct(
        Composer $composer,
        ShellInterface $shell,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        File $file,
        GlobalSection $globalSection
    ) {
        $this->repository = $composer->getRepositoryManager()->getLocalRepository();
        $this->shell = $shell;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->globalSection = $globalSection;
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
     * @throws ShellException
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
        } catch (ShellException $applyException) {
            if ($this->globalSection->get(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)) {
                $this->logger->notice(sprintf(
                    'Patch %s was not applied. (%s)',
                    $name,
                    $applyException->getMessage()
                ));

                return;
            }

            try {
                $this->shell->execute('git apply --check --reverse ' . $path);
            } catch (ShellException $reverseException) {
                throw $reverseException;
            }

            $this->logger->notice("Patch {$name} was already applied.");
        }
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
