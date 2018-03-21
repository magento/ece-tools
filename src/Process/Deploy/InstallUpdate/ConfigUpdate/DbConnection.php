<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * Updates DB connection configuration.
 */
class DbConnection implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

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
     * Data of read connection
     * @var ConnectionInterface
     */
    private $readConnection;

    /**
     * Configurations for deploy phase
     * @var DeployInterface
     */
    private $deployConfig;

    /**
     * @param Environment $environment
     * @param ConnectionInterface $readConnection
     * @param DeployInterface $deployConfig
     * @param ConfigWriter $configWriter
     * @param ConfigReader $configReader
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ConnectionInterface $readConnection,
        DeployInterface $deployConfig,
        ConfigWriter $configWriter,
        ConfigReader $configReader,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->readConnection = $readConnection;
        $this->deployConfig = $deployConfig;
        $this->configWriter = $configWriter;
        $this->configReader = $configReader;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating env.php DB connection configuration.');

        $mainConnectionData = [
            'username' => $this->environment->getDbUser(),
            'host' => $this->environment->getDbHost(),
            'dbname' => $this->environment->getDbName(),
            'password' => $this->environment->getDbPassword(),
        ];

        $DbConfig = [
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
        $config = array_replace_recursive($config, $DbConfig);

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
