<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Db;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Psr\Log\LoggerInterface;

/**
 * Returns database configuration.
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
     * @var ConnectionInterface
     */
    private $readConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SlaveConfig
     */
    private $slaveConfig;

    /**
     * @param Environment $environment
     * @param SlaveConfig $slaveConfig
     * @param DeployInterface $stageConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        SlaveConfig $slaveConfig,
        DeployInterface $stageConfig,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->slaveConfig = $slaveConfig;
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function get()
    {
        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        if (!empty($envDbConfig) &&
            empty($envDbConfig[StageConfigInterface::OPTION_MERGE])
        ) {
            return $envDbConfig;
        }

        $mainConnectionData = [
            'username' => $this->environment->getDbUser(),
            'host' => $this->environment->getDbHost(),
            'dbname' => $this->environment->getDbName(),
            'password' => $this->environment->getDbPassword(),
        ];

        $dbConfig = [
            'connection' => [
                'default' => $mainConnectionData,
                'indexer' => $mainConnectionData,
            ],
        ];

        if ($this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)) {
            $slaveConfiguration = $this->slaveConfig->get();

            if (!empty($slaveConfiguration)) {
                $dbConfig['slave_connection']['default'] = $slaveConfiguration;

                $this->logger->info('Set DB slave connection.');
            }
        }


        return $dbConfig;
    }
}
