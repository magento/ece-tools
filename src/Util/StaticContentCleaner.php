<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Utility class for static content cleaning.
 */
class StaticContentCleaner
{
    /**
     * @param DirectoryList $directoryList
     * @param File $file
     * @param LoggerInterface $logger
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file,
        LoggerInterface $logger
    ) {
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->logger = $logger;
    }

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Cleans static files in the background
     */
    public function clean()
    {
        $this->logger->info('Clearing pub/static');
        $dirStaticPath = $this->directoryList->getPath(DirectoryList::DIR_STATIC);
        $this->file->backgroundClearDirectory($dirStaticPath, [$dirStaticPath . DIRECTORY_SEPARATOR . '.htaccess']);
        $this->logger->info('Clearing var/view_preprocessed');
        $this->file->backgroundClearDirectory($this->directoryList->getPath(DirectoryList::DIR_VIEW_PREPROCESSED));
    }
}
