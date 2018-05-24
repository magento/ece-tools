<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp\Config as AmqpConfig;

/**
 * @inheritdoc
 */
class Amqp implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var AmqpConfig
     */
    private $amqpConfig;

    /**
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param AmqpConfig $amqpConfig
     */
    public function __construct(
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        AmqpConfig $amqpConfig
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->amqpConfig = $amqpConfig;
    }

    /**
     * Saves configuration for queue services.
     *
     * This method set queue configuration from environment variable QUEUE_CONFIGURATION.
     * If QUEUE_CONFIGURATION variable is not set then configuration gets from relationships.
     *
     * Removes old queue configuration from env.php if there is no any queue configuration in
     * relationships or environment variable.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $config = $this->configReader->read();
        $amqpConfig = $this->amqpConfig->get();

        if (count($amqpConfig)) {
            $this->logger->info('Updating env.php AMQP configuration.');
            $config['queue'] = $amqpConfig;
            $this->configWriter->create($config);
        } elseif (isset($config['queue'])) {
            $this->logger->info('Removing queue configuration from env.php.');
            unset($config['queue']);
            $this->configWriter->create($config);
        }
    }
}
