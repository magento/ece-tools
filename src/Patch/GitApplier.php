<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Patch;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides apply methods for patches.
 */
class GitApplier implements ApplierInterface
{

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
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param File $file
     * @param GlobalSection $globalSection
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        File $file,
        GlobalSection $globalSection
    ) {
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
     * @param string[] $paths Paths to patch
     * @return void
     * @throws \RuntimeException
     */
    public function applyPatches(array $patches)
    {
        /** @var string $path */
        foreach ($patches as $patch) {
            $path = $patch['path'];
            $name = $patch['name'] ?? null;
            /**
             * Support for relative paths.
             */
            if (!$this->file->isExists($path)) {
                $path = $this->directoryList->getPatches() . '/' . $path;
            }
            $name = $name ? sprintf('%s (%s)', $name, $path) : $path;
            $format = 'Applying patch %s.';
            $this->logger->info(sprintf(
                $format,
                $name
            ));
            try {
                $this->shell->execute('git apply ' . $path);
            } catch (\RuntimeException $applyException) {
                if ($this->globalSection->get(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)) {
                    $this->logger->notice("Patch {$name} wasn't applied.");
                    return;
                }
                try {
                    $this->shell->execute('git apply --check --reverse ' . $path);
                } catch (\RuntimeException $reverseException) {
                    throw $applyException;
                }
                $this->logger->notice("Patch {$name} was already applied.");
            }
            $this->logger->info('Done.');
        }
    }

    public function unapplyAllPatches(bool $force = false)
    {
        /* Note: We don't have a way to do this in this Applier since we don't keep track of what is applied and in what
         order. */
        $this->logger->info(
            'Git applier does not support unapplying patches.  If you need this feature, install quilt.'
        );
    }

    /**
     * @return void
     */
    public function showAppliedPatches()
    {
        $this->logger->info(
            'Git applier does not support showing applied patches.  If you need this feature, install quilt.'
        );
    }

    public function supportsUnapplyAllPatches()
    {
        return false;
    }

    public function supportsShowAppliedPatches()
    {
        return false;
    }
}
