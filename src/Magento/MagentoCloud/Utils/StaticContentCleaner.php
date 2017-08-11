<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Utils;

use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

class StaticContentCleaner
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     */
    public function __construct(LoggerInterface $logger, ShellInterface $shell)
    {
        $this->logger = $logger;
        $this->shell = $shell;
    }

    public function clean()
    {
        // atomic move within pub/static directory
        $staticContentLocation = realpath(MAGENTO_ROOT . 'pub/static/') . '/';
        $timestamp = time();
        $oldStaticContentLocation = $staticContentLocation . 'old_static_content_' . $timestamp;

        $this->logger->info("Moving out old static content into $oldStaticContentLocation");

        if (!file_exists($oldStaticContentLocation)) {
            mkdir($oldStaticContentLocation);
        }

        $dir = new \DirectoryIterator($staticContentLocation);

        foreach ($dir as $fileInfo) {
            $fileName = $fileInfo->getFilename();
            if (!$fileInfo->isDot() && strpos($fileName, 'old_static_content_') !== 0) {
                $this->logger->info(
                    "Rename " . $staticContentLocation . $fileName
                    . " to " . $oldStaticContentLocation . '/' . $fileName
                );
                rename(
                    $staticContentLocation . '/' . $fileName,
                    $oldStaticContentLocation . '/' . $fileName
                );
            }
        }

        $this->logger->info("Removing $oldStaticContentLocation in the background");
        $this->shell->backgroundExecute("rm -rf $oldStaticContentLocation");

        $preprocessedLocation = realpath(MAGENTO_ROOT . 'var') . '/view_preprocessed';
        if (file_exists($preprocessedLocation)) {
            $oldPreprocessedLocation = $preprocessedLocation . '_old_' . $timestamp;
            $this->logger->info("Rename $preprocessedLocation  to $oldPreprocessedLocation");
            rename($preprocessedLocation, $oldPreprocessedLocation);
            $this->logger->info("Removing $oldPreprocessedLocation in the background");
            $this->shell->backgroundExecute("rm -rf $oldPreprocessedLocation");
        }
    }
}
