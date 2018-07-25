<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Psr\Log\LoggerInterface;

/**
 */
class EnableCron implements ProcessInterface
{
    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param Writer $deployConfigWriter
     * @param Reader $reader
     */
    public function __construct(
        LoggerInterface $logger,
        Writer $deployConfigWriter,
        Reader $reader
    ) {
        $this->logger = $logger;
        $this->writer = $deployConfigWriter;
        $this->reader = $reader;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info("Enable cron");
        $config = $this->reader->read();
        unset($config['cron']);
        $this->writer->create($config);
    }
}
