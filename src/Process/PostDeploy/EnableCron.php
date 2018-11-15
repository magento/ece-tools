<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Enables running Magento cron
 */
class EnableCron implements ProcessInterface
{
    /**
     * Deploy Config Writer
     *
     * @var Writer
     */
    private $writer;

    /**
     * Deploy Config Reader
     *
     * @var Reader
     */
    private $reader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param Writer $writer Deploy Config Writer
     * @param Reader $reader Deploy Config Reader
     */
    public function __construct(
        LoggerInterface $logger,
        Writer $writer,
        Reader $reader
    ) {
        $this->logger = $logger;
        $this->writer = $writer;
        $this->reader = $reader;
    }

    /**
     * Removes cron enabled flag from Magento configuration file.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->logger->info('Enable cron');
        $config = $this->reader->read();
        unset($config['cron']['enabled']);
        $this->writer->create($config);
    }
}
