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
    /** @var EnvironmentDataInterface */
    private $environment;

    /** @var LoggerInterface */
    private $logger;

    /** @var File */
    private $file;

    /** @var DirectoryList */
    private $directory;

    public function __construct(
        EnvironmentDataInterface $environment,
        LoggerInterface $logger,
        File $file,
        DirectoryList $directory
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->file = $file;
        $this->directory = $directory;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $appData = $this->environment->getApplication();

        $mountsFull = $appData['mounts'];

        // Remove the metadata and only return the paths.
        $mountsSlash = array_keys($mountsFull);

        // Change the mount path strings with a leading slash into absolute path strings.
        $mounts = array_map(function ($mount) {
            return $this->directory->getRoot() . $mount;
        }, $mountsSlash);
         

        foreach ($mountsSlash as $mount) {
            foreach ($this->file->scanDir($mount) as $file) {
                $this->logger->debug("$mount: $file");
            }
        }
    }
}
