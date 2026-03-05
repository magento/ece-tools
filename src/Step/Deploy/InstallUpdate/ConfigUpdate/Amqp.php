<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Amqp as AmqpConfig;
use Magento\MagentoCloud\Config\Stomp as StompConfig;

/**
 */
class Amqp implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ConfigWriter
     */
    private ConfigWriter $configWriter;

    /**
     * @var ConfigReader
     */
    private ConfigReader $configReader;

    /**
     * @var AmqpConfig
     */
    private AmqpConfig $amqpConfig;

    /**
     * @var StompConfig
     */
    private StompConfig $stompConfig;

    /**
     * @param ConfigReader    $configReader
     * @param ConfigWriter    $configWriter
     * @param LoggerInterface $logger
     * @param AmqpConfig      $amqpConfig
     * @param StompConfig     $stompConfig
     */
    public function __construct(
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        AmqpConfig $amqpConfig,
        StompConfig $stompConfig
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->amqpConfig = $amqpConfig;
        $this->stompConfig = $stompConfig;
    }

    /**
     * Saves configuration for queue services.
     *
     * This method set queue configuration from environment variable QUEUE_CONFIGURATION.
     * If QUEUE_CONFIGURATION variable is not set then configuration gets from relationships.
     *
     * Prioritizes STOMP configuration for ActiveMQ when STOMP is enabled, otherwise uses AMQP.
     *
     * Removes old queue configuration from env.php if there is no any queue configuration in
     * relationships or environment variable.
     *
     * {@inheritdoc}
     */
    public function execute(): void
    {
        try {
            $config = $this->configReader->read();
            
            // Priority 1: Check if ActiveMQ is available for STOMP
            if ($this->stompConfig->isStompEnabled()) {
                $queueConfig = $this->stompConfig->getConfig();
                $protocol = 'STOMP';
            } else {
                // Fallback: Use AMQP configuration (RabbitMQ or other AMQP brokers)
                $queueConfig = $this->amqpConfig->getConfig();
                $protocol = 'AMQP';
            }
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            if (count($queueConfig)) {
                $this->logger->info("Updating env.php {$protocol} queue configuration.");
                $config['queue'] = $queueConfig;
                $this->configWriter->create($config);
            } elseif (isset($config['queue'])) {
                $this->logger->info('Removing queue configuration from env.php.');
                unset($config['queue']);
                $this->configWriter->create($config);
            }
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE, $e);
        }
    }
}
