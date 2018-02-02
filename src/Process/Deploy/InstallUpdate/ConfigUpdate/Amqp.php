<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Amqp implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * Possible names for amqp relationship
     *
     * @var array
     */
    private $possibleRelationshipNames = ['rabbitmq', 'mq', 'amqp'];

    /**
     * @param Environment $environment
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        ConfigReader $configReader,
        ConfigWriter $configWriter,
        LoggerInterface $logger,
        DeployInterface $stageConfig
    ) {
        $this->environment = $environment;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
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
        $mqConfig = $this->getAmqpConfig();
        $envQueueConfig = $this->stageConfig->get(DeployInterface::VAR_QUEUE_CONFIGURATION);
        $config = $this->configReader->read();

        if (count($envQueueConfig)) {
            $this->logger->info('Updating env.php AMQP configuration.');
            $config['queue'] = $envQueueConfig;
        } elseif (count($mqConfig)) {
            $this->logger->info('Updating env.php AMQP configuration.');
            $amqpConfig = $mqConfig[0];
            $config['queue']['amqp'] = [
                'host' => $amqpConfig['host'],
                'port' => $amqpConfig['port'],
                'user' => $amqpConfig['username'],
                'password' => $amqpConfig['password'],
                'virtualhost' => isset($amqpConfig['vhost']) ? $amqpConfig['vhost'] : '/',
            ];
        } else {
            $config = $this->removeAmqpConfig($config);
        }

        $this->configWriter->write($config);
    }

    /**
     * Remove AMQP configuration from env.php
     *
     * @param array $config
     * @return array
     */
    private function removeAmqpConfig(array $config)
    {
        if (isset($config['queue']['amqp'])) {
            $this->logger->info('Removing AMQP configuration from env.php.');

            if (count($config['queue']) > 1) {
                unset($config['queue']['amqp']);
            } else {
                unset($config['queue']);
            }
        }

        return $config;
    }

    /**
     * Finds if configuration exists for one of possible amqp relationship names and return first match,
     * amqp relationship can have different name on different environment.
     *
     * @return array
     */
    private function getAmqpConfig(): array
    {
        foreach ($this->possibleRelationshipNames as $relationshipName) {
            $mqConfig = $this->environment->getRelationship($relationshipName);
            if (!empty($mqConfig)) {
                return $mqConfig;
            }
        }

        return [];
    }
}
