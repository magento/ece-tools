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
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ConnectionInterface $readConnection,
        DeployInterface $deployConfig,
        ConfigWriter $configWriter,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->readConnection = $readConnection;
        $this->deployConfig = $deployConfig;
        $this->configWriter = $configWriter;
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

        $config = [
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

        $config = array_replace_recursive($this->getSlaveConnection(), $config);

        $this->configWriter->update($config);
    }

    /**
     * Returns mysql read connection if MYSQL_READ_DISTRIBUTION is enabled otherwise returns empty array.
     * Connection data is nested to the array with the path which this data should have in env.php
     *
     * @return array
     */
    private function getSlaveConnection(): array
    {
        $config = [];
        if ($this->deployConfig->get(DeployInterface::VAR_MYSQL_READ_DISTRIBUTION)
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
            $config['db']['slave_connection']['default'] = $slaveConnection;
        }
        
        return $config;
    }
}
