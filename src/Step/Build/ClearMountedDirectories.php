<?php
/************************************************************************
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
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

    /** @var DirectoryList */
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
        foreach ($this->directoryList->getMountPoints() as $mount) {
            if ($this->file->isExists($mount) === false) {
                continue;
            }
            $this->logger->info(sprintf('Clearing the %s path of all files and folders', $mount));
            $this->file->clearDirectory($mount);
        }
    }
}
