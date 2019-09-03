<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Amqp;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Service\RabbitMq;

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
     * @param RabbitMq $rabbitMQ
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        RabbitMq $rabbitMQ,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger
    ) {
        $this->rabbitMQ = $rabbitMQ;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
    }

    /**
     * Returns queue configuration
     *
     * @return array
     */
    public function get(): array
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
