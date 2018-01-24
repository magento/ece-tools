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
     * @param string $path Path to patch
     * @param string|null $name Name of patch
     * @param string|null $packageName Name of package to be patched
     * @param string|null $constraint Specific constraint of package to be fixed
     * @return void
     * @throws \RuntimeException
     */
    public function apply(string $path, $name, $packageName, $constraint)
    {
        $name = $name ?: $path;

        /**
         * Support for relative paths.
         */
        if (!$this->file->isExists($path)) {
            $path = $this->directoryList->getRoot() . '/patches/' . $path;
        }

        $this->logger->info(sprintf(
            'Applying patch %s %s.',
            $name,
            $constraint
        ));

        if ($packageName && !$this->matchConstraint($packageName, $constraint)) {
            $this->logger->notice(sprintf(
                'Constraint %s %s was not found.',
                $packageName,
                $constraint
            ));

            return;
        }

        $this->shell->execute('git apply ' . $path);
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
