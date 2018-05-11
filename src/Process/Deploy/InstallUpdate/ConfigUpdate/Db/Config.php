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
     * @param Environment $environment
     * @param ConnectionInterface $readConnection
     * @param DeployInterface $stageConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ConnectionInterface $readConnection,
        DeployInterface $stageConfig,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->readConnection = $readConnection;
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $dbConfig = [
            'db' => [
            ],
            'resource' => [
                'default_setup' => [
                    'connection' => 'default',
                ],
            ],
        ];

        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        if (!empty($envDbConfig) &&
            !isset($envDbConfig[StageConfigInterface::OPTION_MERGE])
            || !$envDbConfig[StageConfigInterface::OPTION_MERGE]
        ) {
            $dbConfig['db'] = $envDbConfig;

            return $dbConfig;
        }



        $this->logger->info('Updating env.php DB connection configuration.');

        $mainConnectionData = [
            'username' => $this->environment->getDbUser(),
            'host' => $this->environment->getDbHost(),
            'dbname' => $this->environment->getDbName(),
            'password' => $this->environment->getDbPassword(),
        ];

        $dbConfig = [
            'db' => [
                'connection' => [
                    'default' => $mainConnectionData,
                    'indexer' => $mainConnectionData,
                ],
            ],
            'resource' => [
                'default_setup' => [
                    'connection' => 'default',
                ],
            ],
        ];

        $config = $this->configReader->read();
        $config = array_replace_recursive($config, $dbConfig);

        $slaveConnectionData = $this->getSlaveConnection();

        if (!$slaveConnectionData) {
            unset($config['db']['slave_connection']);
        } else {
            $config['db']['slave_connection']['default'] = $slaveConnectionData;
        }

        $this->configWriter->create($config);
    }

    /**
     * Returns mysql read connection if MYSQL_USE_SLAVE_CONNECTION is enabled otherwise returns empty array.
     *
     * @return array
     */
    private function getSlaveConnection(): array
    {
        $slaveConnection = [];
        if ($this->deployConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)
            && $this->readConnection->getHost()
        ) {
            $this->logger->info('Set DB slave connection.');

            $slaveConnection = [
                'host' => $this->readConnection->getHost() . ':' . $this->readConnection->getPort(),
                'username' => $this->readConnection->getUser(),
                'dbname' => $this->readConnection->getDBName(),
                'password' => $this->readConnection->getPassword(),
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
            ];
        }

        return $slaveConnection;
    }
}
