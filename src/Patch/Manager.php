<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Patch;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Shell\ShellException;
use Psr\Log\LoggerInterface;

/**
 * Wrapper form applying required patches.
 */
class Manager
{
    /**
     * Directory for hotfixes.
     */
    const HOTFIXES_DIR = 'm2-hotfixes';

    /**
     * @var Applier
     */
    private $applier;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param Applier $applier
     * @param LoggerInterface $logger
     * @param File $file
     * @param FileList $fileList
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Applier $applier,
        LoggerInterface $logger,
        File $file,
        FileList $fileList,
        DirectoryList $directoryList
    ) {
        $this->applier = $applier;
        $this->logger = $logger;
        $this->file = $file;
        $this->fileList = $fileList;
        $this->directoryList = $directoryList;
    }

    /**
     * Applies all needed patches.
     *
     * @throws ShellException
     * @throws FileSystemException
     */
    public function applyAll()
    {
        $this->copyStaticFile();
        $this->applyComposerPatches();
        $this->applyHotFixes();
    }

    /**
     * Copying static file endpoint.
     * This resolves issue MAGECLOUD-314
     *
     * @return void
     */
    private function copyStaticFile()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();

        if (!$this->file->isExists($magentoRoot . '/pub/static.php')) {
            $this->logger->notice('File static.php was not found.');

            return;
        }

        $this->file->copy($magentoRoot . '/pub/static.php', $magentoRoot . '/pub/front-static.php');
        $this->logger->info('File static.php was copied.');
    }

    /**
     * Applies patches from composer.json file.
     * Patches are applying from top to bottom of config list.
     *
     * ```
     *  "colinmollenhour/credis" : {
     *      "Fix Redis issue": {
     *          "1.6": "patches/redis-pipeline.patch"
     *      }
     *  }
     *
     * Each patch must have corresponding constraint of target package,
     * in one of the following format:
     * - 1.6
     * - 1.6.*
     * - ^1.6
     *
     * @return void
     * @throws ShellException
     * @throws FileSystemException
     */
    private function applyComposerPatches()
    {
        $patches = json_decode(
            $this->file->fileGetContents($this->fileList->getPatches()),
            true
        );

        if (!$patches) {
            $this->logger->notice('Patching skipped.');

            return;
        }

        foreach ($patches as $packageName => $patchesInfo) {
            foreach ($patchesInfo as $patchName => $packageInfo) {
                if (is_string($packageInfo)) {
                    $this->applier->apply($packageInfo, $patchName, $packageName, '*');
                } elseif (is_array($packageInfo)) {
                    foreach ($packageInfo as $constraint => $path) {
                        $this->applier->apply($path, $patchName, $packageName, $constraint);
                    }
                }
            }
        }
    }

    /**
     * Applies patches from root directory m2-hotfixes.
     *
     * @return void
     */
    private function applyHotFixes()
    {
        $hotFixesDir = $this->directoryList->getMagentoRoot() . '/' . static::HOTFIXES_DIR;

        if (!$this->file->isDirectory($hotFixesDir)) {
            $this->logger->notice('Hot-fixes directory was not found. Skipping.');

            return;
        }

        $this->logger->info('Applying hot-fixes.');

        $files = glob($hotFixesDir . '/*.patch');
        sort($files);

        foreach ($files as $file) {
            $this->applier->apply($file, null, null, null);
        }
    }
}
