<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\SplitDbConnection;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Psr\Log\LoggerInterface;

/**
 * Updates slave connections
 */
class SlaveConnection
{
    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @param DeployInterface $stageConfig
     * @param DbConfig $dbConfig
     * @param LoggerInterface $logger
     * @param ConfigReader $configReader
     * @param ConfigWriter $configWriter
     */
    public function __construct(
        DeployInterface $stageConfig,
        DbConfig $dbConfig,
        LoggerInterface $logger,
        ConfigReader $configReader,
        ConfigWriter $configWriter
    ) {
        $this->stageConfig = $stageConfig;
        $this->dbConfig = $dbConfig;
        $this->logger = $logger;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
    }

    /**
     * Updates slave connections
     *
     * @throws FileSystemException
     * @throws ConfigException
     */
    public function update()
    {
        if (!$this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION)) {
            return;
        }

        $mageConfig = $this->configReader->read();
        $dbConfig = $this->dbConfig->get();

        $mageSplitConnections = array_keys(array_intersect_key(
            $mageConfig[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION],
            array_flip(DbConfig::SPLIT_CONNECTIONS)
        ));

        if (empty($mageSplitConnections)) {
            return;
        }

        foreach ($mageSplitConnections as $mageConnectionName) {
            if (!isset($dbConfig[DbConfig::KEY_SLAVE_CONNECTION][$mageConnectionName])) {
                $this->logger->warning(
                    sprintf(
                        'Slave connection for \'%s\' connection not set. '
                        . 'Relationships do not have the configuration for this slave connection',
                        $mageConnectionName
                    ),
                    ['errorCode' => Error::WARN_SLAVE_CONNECTION_NOT_SET]
                );
                continue;
            }
            $connectionConfig = $dbConfig[DbConfig::KEY_SLAVE_CONNECTION][$mageConnectionName];
            $mageConfig[DbConfig::KEY_DB][DbConfig::KEY_SLAVE_CONNECTION][$mageConnectionName] = $connectionConfig;
            $this->logger->info(sprintf('Slave connection for \'%s\' connection was set', $mageConnectionName));
        }
        $this->configWriter->create($mageConfig);
    }
}
