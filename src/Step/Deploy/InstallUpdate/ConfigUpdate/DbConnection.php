<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Database\MergedConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates DB connection configuration.
 */
class DbConnection implements StepInterface
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
     * @param ConfigWriter $configWriter
     * @param ConfigReader $configReader
     * @param ConfigMerger $configMerger
     * @param RelationshipConnectionFactory $connectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        DeployInterface $stageConfig,
        MergedConfig $mergedConfig,
        ConfigWriter $configWriter,
        ConfigReader $configReader,
        ConfigMerger $configMerger,
        RelationshipConnectionFactory $connectionFactory,
        LoggerInterface $logger
    )
    {
        $this->stageConfig = $stageConfig;
        $this->mergedConfig = $mergedConfig;
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
        $dbConfig = $this->mergedConfig->get();
        $config[MergedConfig::KEY_DB] = $dbConfig[MergedConfig::KEY_DB];
        $config[MergedConfig::KEY_RESOURCE] = $dbConfig[MergedConfig::KEY_RESOURCE];
        $this->addLoggingAboutSlaveConnection($config[MergedConfig::KEY_DB]);
        $this->configWriter->create($config);
    }

    /**
     * Adds logging about slave connection.
     * @param array $dbConfig
     */
    private function addLoggingAboutSlaveConnection(array $dbConfig)
    {
        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        $isUseSlave = $this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION);
        $isMergeRequired = !$this->configMerger->isEmpty($envDbConfig)
            && !$this->configMerger->isMergeRequired($envDbConfig);
        $connectionNames = array_keys($dbConfig[MergedConfig::KEY_CONNECTION]);
        foreach ($connectionNames as $connectionName) {
            $serviceConnectionName = MergedConfig::CONNECTION_MAP[MergedConfig::KEY_CONNECTION][$connectionName];
            $serviceConnectionData = $this->connectionFactory->create($serviceConnectionName);
            if (!$serviceConnectionData->getHost() || !$isUseSlave || $isMergeRequired) {
                continue;
            } elseif (!$this->mergedConfig->isDbConfigCompatibleWithSlaveConnection($connectionName)) {
                $this->logger->warning(sprintf(
                    'You have changed db configuration that not compatible with %s slave connection.',
                    $connectionName
                ));
            } elseif (!empty($config[MergedConfig::KEY_SLAVE_CONNECTION][$connectionName])) {
                $this->logger->info(sprintf('Set DB slave connection for %s connection.', $connectionName));
            } else {
                $this->logger->info(
                    'Enabling of the variable MYSQL_USE_SLAVE_CONNECTION had no effect ' .
                    'because slave connection is not configured on your environment.'
                );
            }
        }
    }
}
