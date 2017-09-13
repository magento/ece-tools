<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
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
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    public function clean()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();
        // atomic move within pub/static directory
        $staticContentLocation = $this->file->getRealPath($magentoRoot . '/pub/static') . '/';
        $timestamp = time();
        $oldStaticContentLocation = $staticContentLocation . 'old_static_content_' . $timestamp . '/';

        $this->logger->info('Moving out old static content into ' . $oldStaticContentLocation);

        if (!$this->file->isExists($oldStaticContentLocation)) {
            $this->file->createDirectory($oldStaticContentLocation);
        }

        foreach ($this->file->readDirectory($staticContentLocation) as $fileName) {
            if (strpos($fileName, 'old_static_content_') === false) {
                $newFileName = str_replace($staticContentLocation, $oldStaticContentLocation, $fileName);
                $this->logger->info('Rename ' . $fileName . ' to ' . $newFileName);
                $this->file->rename($fileName, $newFileName);
            }
        }

        $this->logger->info('Removing ' . $oldStaticContentLocation . ' in the background');
        $this->shell->backgroundExecute('rm -rf ' . $oldStaticContentLocation);

        // Clean view_preprocessed directory
        $preprocessedLocation = $this->file->getRealPath($magentoRoot . '/var') . '/view_preprocessed';
        if ($this->file->isExists($preprocessedLocation)) {
            $oldPreprocessedLocation = $preprocessedLocation . '_old_' . $timestamp;
            $this->logger->info('Rename ' . $preprocessedLocation . ' to ' . $oldPreprocessedLocation);
            $this->file->rename($preprocessedLocation, $oldPreprocessedLocation);
            $this->logger->info('Removing '.  $oldPreprocessedLocation . ' in the background');
            $this->shell->backgroundExecute('rm -rf ' . $oldPreprocessedLocation);
        }
    }
}
