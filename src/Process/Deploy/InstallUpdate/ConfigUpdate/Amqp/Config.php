<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;

/**
 * Returns queue configuration.
 */
class Config
{
    /**
     * @var Environment
     */
    private $environment;

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
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @param Environment $environment
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     */
    public function __construct(
        Environment $environment,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger
    ) {
        $this->environment = $environment;
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
     * Finds if configuration exists for one of possible amqp relationship names and return first match,
     * amqp relationship can have different name on different environment.
     *
     * @return array
     */
    private function getAmqpConfig(): array
    {
        foreach ($this->possibleRelationshipNames as $relationshipName) {
            $mqConfig = $this->environment->getRelationship($relationshipName);
            if (count($mqConfig)) {
                $amqpConfig = $mqConfig[0];
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
        }

        return [];
    }
}
