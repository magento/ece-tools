<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Copy "static.php" to "front-static.php"
 */
class CopyPubStatic implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     * @param FileList $fileList
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList,
        FileList $fileList
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->fileList = $fileList;
    }

    /**
     * @inheritDoc
     */
    public function execute(): void
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();

        $this->file->copy(
            $this->fileList->getFrontStaticDist(),
            $magentoRoot . '/pub/front-static.php'
        );
        $this->logger->info('File "front-static.php" was copied');
    }
}
