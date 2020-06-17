<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CopySampleData implements StepInterface
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
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $magentoRoot = $this->directoryList->getMagentoRoot();
            $sampleDataDir = $magentoRoot . '/vendor/magento/sample-data-media';

            if (!$this->file->isExists($sampleDataDir)) {
                $this->logger->info('Sample data media was not found. Skipping.');

                return;
            }

            $this->logger->info('Sample data media found. Marshalling to pub/media.');
            $this->file->copyDirectory($sampleDataDir, $magentoRoot . '/pub/media');
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_FAILED_COPY_SAMPLE_DATA, $e);
        }
    }
}
