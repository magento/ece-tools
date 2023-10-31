<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\Config\EnvironmentDataInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Clear the mounted directories on the local filesystem before mounting the remote filesystem.
 *
 * This prevents warning messages that the mounted directories are not empty.
 *
 * {@inheritdoc}
 */
class ClearMountedDirectories implements StepInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var File */
    private $file;

    /**
     * To get absolute path to the app directory.
     * 
     * @var DirectoryList
     */
    private $directoryList;

    public function __construct(
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $cleanMountedDir = $this->getCleanMountedDir(); 
        foreach ($cleanMountedDir as $mount) {
            if ($this->file->isExists($mount) === false) {
                continue;
            }
            $this->logger->log(sprintf('Clearing the %s path of all files and folders', $mount));
            $this->file->backgroundClearDirectory($mount);
        }
    }

    /**
     * Returns the path to the cleared directories
     *
     * @return array
     */
    private function getCleanMountedDir(): array
    {
        return [
            $this->directoryList->getPath(DirectoryList::DIR_MEDIA),
            $this->directoryList->getPath(DirectoryList::DIR_VAR),
            $this->directoryList->getPath(DirectoryList::DIR_ETC),
            $this->directoryList->getPath(DirectoryList::DIR_STATIC)
        ];
    }
}
