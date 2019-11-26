<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
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
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     */
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
     * @inheritDoc
     */
    public function execute(): void
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();

        if (!$this->file->isExists($magentoRoot . '/pub/static.php')) {
            $this->logger->notice('File "static.php" was not found');

            return;
        }

        $this->file->copy(
            $magentoRoot . '/pub/static.php',
            $magentoRoot . '/pub/front-static.php'
        );
        $this->logger->info('File "static.php" was copied');
    }
}
