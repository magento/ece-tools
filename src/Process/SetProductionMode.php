<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Psr\Log\LoggerInterface;

/**
 * Switching magento to production mode.
 * This is making for compatibility magento with cloud environment read-only file system.
 * As magento contains logic that skips checking on read-only file system only in production mode.
 */
class SetProductionMode implements ProcessInterface
{
    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param Writer $deployConfigWriter
     */
    public function __construct(
        LoggerInterface $logger,
        Writer $deployConfigWriter
    ) {
        $this->logger = $logger;
        $this->writer = $deployConfigWriter;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Set Magento application mode to \'production\'');

        try {
            $this->writer->update(['MAGE_MODE' => 'production']);
        } catch (FileSystemException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
