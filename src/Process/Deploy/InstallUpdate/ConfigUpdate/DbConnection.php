<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Database\MergedConfig;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates DB connection configuration.
 */
class DbConnection implements ProcessInterface
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
     * @var MergedConfig
     */
    private $mergedConfig;

    /**
     * @var ResourceConfig
     */
    private $resourceConfig;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @var RelationshipConnectionFactory
     */
    private $connectionFactory;

    /**
     * @param DeployInterface $stageConfig
     * @param MergedConfig $mergedConfig
     * @param ResourceConfig $resourceConfig
     * @param ConfigWriter $configWriter
     * @param ConfigReader $configReader
     * @param ConfigMerger $configMerger
     * @param RelationshipConnectionFactory $connectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        DeployInterface $stageConfig,
        MergedConfig $mergedConfig,
        ResourceConfig $resourceConfig,
        ConfigWriter $configWriter,
        ConfigReader $configReader,
        ConfigMerger $configMerger,
        RelationshipConnectionFactory $connectionFactory,
        LoggerInterface $logger
    ) {
        $this->stageConfig = $stageConfig;
        $this->mergedConfig = $mergedConfig;
        $this->resourceConfig = $resourceConfig;
        $this->configWriter = $configWriter;
        $this->configReader = $configReader;
        $this->configMerger = $configMerger;
        $this->logger = $logger;
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $config = $this->configReader->read();

        $this->logger->info('Updating env.php DB connection configuration.');
        $config['db'] = $this->mergedConfig->get();
        $config['resource'] = $this->resourceConfig->get();

        $this->addLoggingAboutSlaveConnection();
        $this->configWriter->create($config);
    }

    /**
     * Adds logging about slave connection.
     */
    private function addLoggingAboutSlaveConnection()
    {
        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        $connectionData = $this->connectionFactory->create(RelationshipConnectionFactory::CONNECTION_MAIN);

        if (!$connectionData->getHost()
            || !$this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            || (!$this->configMerger->isEmpty($envDbConfig) && !$this->configMerger->isMergeRequired($envDbConfig))
        ) {
            return;
        }

        if (!$this->mergedConfig->isDbConfigurationCompatibleWithSlaveConnection()) {
            $this->logger->warning(
                'You have changed db configuration that not compatible with default slave connection.'
            );
        } else {
            $dbConfig = $this->mergedConfig->get();

            if (!empty($dbConfig['slave_connection']['default'])) {
                $this->logger->info('Set DB slave connection.');
            } else {
                $this->logger->info(
                    'Enabling of the variable MYSQL_USE_SLAVE_CONNECTION had no effect ' .
                    'because slave connection is not configured on your environment.'
                );
            }
        }
    }
}
