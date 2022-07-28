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

/**
 * @inheritdoc
 */
class Amqp implements StepInterface
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
        try {
            $config = $this->configReader->read();
            $amqpConfig = $this->amqpConfig->getConfig();
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            if (count($amqpConfig)) {
                $this->logger->info('Updating env.php AMQP configuration.');
                $config['queue'] = $amqpConfig;
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
