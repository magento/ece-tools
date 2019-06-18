<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Service\RabbitMQ;

/**
 * Returns queue configuration.
 */
class Config
{
    /**
     * @var RabbitMQ
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
     * @param RabbitMQ $rabbitMQ
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        RabbitMQ $rabbitMQ,
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
        $mqConfig = $this->rabbitMQ->getConfiguration();

        if ($this->configMerger->isEmpty($envQueueConfig)) {
            return $mqConfig;
        }

        if ($this->configMerger->isMergeRequired($envQueueConfig)) {
            return $this->configMerger->mergeConfigs($mqConfig, $envQueueConfig);
        }

        return $this->configMerger->clear($envQueueConfig);
    }
}
