<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Service\RabbitMq;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Returns queue configuration.
 */
class Amqp
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
    public function getConfig(): array
    {
        $config = $this->getMergedConfig();

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
    private function getMergedConfig(): array
    {
        $envQueueConfig = $this->stageConfig->get(DeployInterface::VAR_QUEUE_CONFIGURATION);
        $mqConfig = $this->getAmqpConfig();

        if ($this->configMerger->isEmpty($envQueueConfig)) {
            return $mqConfig;
        }

        if ($this->configMerger->isMergeRequired($envQueueConfig)) {
            return $this->configMerger->merge($mqConfig, $envQueueConfig);
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
