<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\PostDeploy;

use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Enables running Magento cron
 */
class EnableCron implements StepInterface
{
    /**
     * Magento environment config writer
     *
     * @var WriterInterface
     */
    private $writer;

    /**
     * Magento environment config reader
     *
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param WriterInterface $writer
     * @param ReaderInterface $reader
     */
    public function __construct(
        LoggerInterface $logger,
        WriterInterface $writer,
        ReaderInterface $reader
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
