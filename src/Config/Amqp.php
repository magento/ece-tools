<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ActiveMq;
use Magento\MagentoCloud\Service\RabbitMq;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Returns queue configuration.
 */
class Amqp
{
    /**
     * @var ActiveMq
     */
    private ActiveMq $activeMQ;

    /**
     * @var RabbitMq
     */
    private RabbitMq $rabbitMQ;

    /**
     * @var DeployInterface
     */
    private DeployInterface $stageConfig;

    /**
     * @var ConfigMerger
     */
    private ConfigMerger $configMerger;

    /**
     * @var MagentoVersion
     */
    private MagentoVersion $magentoVersion;

    /**
     * @param ActiveMq        $activeMQ
     * @param RabbitMq        $rabbitMQ
     * @param DeployInterface $stageConfig
     * @param ConfigMerger    $configMerger
     * @param MagentoVersion  $magentoVersion
     */
    public function __construct(
        ActiveMq $activeMQ,
        RabbitMq $rabbitMQ,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger,
        MagentoVersion $magentoVersion
    ) {
        $this->activeMQ = $activeMQ;
        $this->rabbitMQ = $rabbitMQ;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Returns queue configuration
     *
     * @return array
     * @throws UndefinedPackageException|ConfigException
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
     * @throws ConfigException
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
     * Prioritizes ActiveMQ first, then falls back to RabbitMQ.
     *
     * @return array
     */
    private function getAmqpConfig(): array
    {
        // First priority: ActiveMQ
        if ($amqpConfig = $this->activeMQ->getConfiguration()) {
            return [
                'amqp' => [
                    'host' => $amqpConfig['host'],
                    'port' => $amqpConfig['port'],
                    'user' => $amqpConfig['username'] ?? $amqpConfig['user'] ?? '',
                    'password' => $amqpConfig['password'],
                    'virtualhost' => $amqpConfig['vhost'] ?? '/',
                ]
            ];
        }

        // Fallback: RabbitMQ
        if ($amqpConfig = $this->rabbitMQ->getConfiguration()) {
            return [
                'amqp' => [
                    'host' => $amqpConfig['host'],
                    'port' => $amqpConfig['port'],
                    'user' => $amqpConfig['username'],
                    'password' => $amqpConfig['password'],
                    'virtualhost' => $amqpConfig['vhost'] ?? '/',
                ]
            ];
        }

        return [];
    }
}
