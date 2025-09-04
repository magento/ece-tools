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
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Returns STOMP queue configuration for ActiveMQ Artemis.
 */
class Stomp
{
    /**
     * @var ActiveMq
     */
    private ActiveMq $activeMQ;

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
     * @param DeployInterface $stageConfig
     * @param ConfigMerger    $configMerger
     * @param MagentoVersion  $magentoVersion
     */
    public function __construct(
        ActiveMq $activeMQ,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger,
        MagentoVersion $magentoVersion
    ) {
        $this->activeMQ = $activeMQ;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Returns STOMP queue configuration
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
     * Returns merged STOMP queue configuration
     *
     * @return array
     * @throws ConfigException
     */
    private function getMergedConfig(): array
    {
        $envQueueConfig = $this->stageConfig->get(DeployInterface::VAR_QUEUE_CONFIGURATION);
        $stompConfig = $this->getStompConfig();

        if ($this->configMerger->isEmpty($envQueueConfig)) {
            return $stompConfig;
        }

        if ($this->configMerger->isMergeRequired($envQueueConfig)) {
            return $this->configMerger->merge($stompConfig, $envQueueConfig);
        }

        return $this->configMerger->clear($envQueueConfig);
    }

    /**
     * Convert ActiveMQ service configuration to STOMP format for Magento.
     * Uses hardcoded connection details as specified by AC team.
     *
     * @return array
     */
    private function getStompConfig(): array
    {
        if ($this->activeMQ->getConfiguration()) {
            return [
                'stomp' => [
                    'host' => 'localhost',
                    'port' => '61613',
                    'user' => 'artemis',
                    'password' => 'artemis'
                ],
                'default_connection' => 'stomp'
            ];
        }

        return [];
    }

    /**
     * Check if ActiveMQ is available for STOMP protocol
     * Since we're using hardcoded STOMP values, just check if ActiveMQ is configured
     *
     * @return bool
     */
    public function isStompEnabled(): bool
    {
        $config = $this->activeMQ->getConfiguration();
        return !empty($config);
    }
}
