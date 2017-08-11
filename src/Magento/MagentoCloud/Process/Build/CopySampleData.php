<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CopySampleData implements ProcessInterface
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
     * @param LoggerInterface $logger
     * @param File $file
     */
    public function __construct(
        LoggerInterface $logger,
        File $file
    ) {
        $this->logger = $logger;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $sampleDataDir = MAGENTO_ROOT . 'vendor/magento/sample-data-media';

        if (!$this->file->isExists($sampleDataDir)) {
            return;
        }

        $this->logger->info("Sample data media found. Marshalling to pub/media.");
        $this->file->copyDirectory(
            $sampleDataDir,
            MAGENTO_ROOT . '/pub/media'
        );
    }
}
