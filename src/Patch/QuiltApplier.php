<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Patch;

use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Shell\ShellInterface as Shell;
use Magento\MagentoCloud\Filesystem\DirectoryList;

/**
 * Provides apply methods for patches.
 * This implementation uses Quilt.
 * This is good for non-cloud environments because it keeps track of which patches are applied.
 * It also makes it a lot easier for editing patches.
 */
class QuiltApplier implements ApplierInterface
{

    /**
     * @var Shell
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
     * @param Shell $shell
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     */
    public function __construct(Shell $shell, LoggerInterface $logger, DirectoryList $directoryList)
    {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
    }

    /**
     * Applies a list of patches in order , using 'quilt' command.
     *
     * @param array $paths Paths to patch
     *
     * @return void
     */
    public function applyPatches(array $patches)
    {
        $patchesDirectory = $this->directoryList->getPatches() ;
        $patchesDirectoryWithSlash = $patchesDirectory . '/' ;
        $seriesPath = $patchesDirectoryWithSlash . 'series';
        if (file_exists($seriesPath)) {
            $this->unapplyAllPatches();
        }
        $patchesDirectoryWithSlashLength = strlen($patchesDirectoryWithSlash);
        $seriesData = '';
        foreach ($patches as $patch) {
            $path = $patch['path'];
            if (0 === strncmp($path, $patchesDirectoryWithSlash, $patchesDirectoryWithSlashLength)) {
                $path = substr($path, $patchesDirectoryWithSlashLength);
            }
            $seriesData .= $path . "\n";
        }
        $success = file_put_contents($seriesPath, $seriesData);
        if ($success === false) {
            throw new \Exception("Failed to write to $seriesPath!");
        }
        $this->logger->info("* Running quilt started.");
        $output = $this->shell->execute('QUILT_PATCHES=' . $patchesDirectory . ' quilt push -a ;'
            . ' EXIT_CODE=$? ; if { [ 0 -eq "$EXIT_CODE" ] || [ 2 -eq "$EXIT_CODE" ]; }; then true; else false ; fi');
        /** @var string $line */
        foreach ($output as $line) {
            $this->logger->info($line);
        }
        $this->logger->info("* Running quilt finished.");
    }

    /**
     * Unapplies all patches, using 'quilt' command.
     *
     * @param bool $force Forces the patches to be unapplied even if they don't seem to be applied
     * @return void
     */
    public function unapplyAllPatches(bool $force = false)
    {
        $forceArgument = '';
        if ($force) {
            $forceArgument = ' -f';
        }
        $this->logger->info('Unapplying patches started.');
        $this->shell->execute('QUILT_PATCHES=' . $this->directoryList->getPatches()
            . ' quilt pop' . $forceArgument . ' -a ;'
            . ' EXIT_CODE=$? ; if { [ 0 -eq "$EXIT_CODE" ] || [ 2 -eq "$EXIT_CODE" ]; }; then true; else false ; fi');
        $this->logger->info('Unapplying patches finished.');
    }

    /**
     * Shows applied patches, using 'quilt' command.
     *
     * @return void
     */
    public function showAppliedPatches()
    {
        $this->shell->execute('QUILT_PATCHES=' . $this->directoryList->getPatches() . ' quilt applied ;');
    }
}
