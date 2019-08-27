<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Service\RabbitMq;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Returns queue configuration.
 */
class Config
{
    /**
     * @var RabbitMq
     */
    private $rabbitMQ;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param RabbitMq $rabbitMQ
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        RabbitMq $rabbitMQ,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger,
        MagentoVersion $magentoVersion
    ) {
        $this->rabbitMQ = $rabbitMQ;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Returns queue configuration
     *
     * @return array
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     */
    public function get(): array
    {
        $config = $this->getConfig();

        if ($this->magentoVersion->isGreaterOrEqual('2.2')) {
            $config['consumers_wait_for_messages'] = $this->stageConfig->get(
                DeployInterface::VAR_CONSUMERS_WAIT_FOR_MAX_MESSAGES
            ) ? 1 : 0;
        }

        return $config;
    }

    /**
     * Returns merged queue configuration
     *
     * @return array
     */
    private function getConfig(): array
    {
        $envQueueConfig = $this->stageConfig->get(DeployInterface::VAR_QUEUE_CONFIGURATION);
        $mqConfig = $this->getAmqpConfig();

        if ($this->configMerger->isEmpty($envQueueConfig)) {
            return $mqConfig;
        }

        if ($this->configMerger->isMergeRequired($envQueueConfig)) {
            return $this->configMerger->mergeConfigs($mqConfig, $envQueueConfig);
        }

        return $this->configMerger->clear($envQueueConfig);
    }

    /**
     * Convert amqp service configuration to magento format.
     *
     * @return array
     */
    private function getAmqpConfig(): array
    {
        if ($amqpConfig = $this->rabbitMQ->getConfiguration()) {
            return [
                'amqp' => [
                    'host' => $amqpConfig['host'],
                    'port' => $amqpConfig['port'],
                    'user' => $amqpConfig['username'],
                    'password' => $amqpConfig['password'],
                    'virtualhost' => isset($amqpConfig['vhost']) ? $amqpConfig['vhost'] : '/',
                ]
            ];
        }

        return [];
    }
}
