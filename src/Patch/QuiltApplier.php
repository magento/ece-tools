<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Patch;

use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Shell\ShellInterface as Shell;

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
     * @param Shell $shell
     * @param LoggerInterface $logger
     */
    public function __construct(Shell $shell, LoggerInterface $logger)
    {
        $this->shell = $shell;
        $this->logger = $logger;
    }

    /**
     * Applies a list of patches in order , using 'quilt' command.
     *
     * @param array $paths Paths to patch
     *
     * @return void
     */
    public function applyPatches(array $paths)
    {
        $seriesData = implode("\n", $paths);
        $seriesData .= "\n";
        $seriesPath = 'vendor/magento/ece-patches/patches/series';
        $success = file_put_contents($seriesPath, $seriesData);
        if ($success === false) {
            throw new \Exception("Failed to write to $seriesPath!");
        }
        $this->logger->info("* Running quilt started.");
        $output = $this->shell->execute('QUILT_PATCHES=vendor/magento/ece-patches/patches quilt push -a ;'
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
     * @return void
     */
    public function unapplyAllPatches()
    {
        $this->logger->info('Unapplying patches started.');
        $this->shell->execute('QUILT_PATCHES=vendor/magento/ece-patches/patches quilt pop -a ;'
            . ' EXIT_CODE=$? ; if { [ 0 -eq "$EXIT_CODE" ] || [ 2 -eq "$EXIT_CODE" ]; }; then true; else false ; fi');
        $this->logger->info('Unapplying patches finished.');
    }
}
