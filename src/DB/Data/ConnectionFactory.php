<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use RuntimeException;

/**
 * Responsible for creating and configuring Magento\MagentoCloud\DB\Data\ConnectionInterface instances.
 */
class ConnectionFactory
{
    const CONNECTION_MAIN = 'main';
    const CONNECTION_SLAVE = 'slave';

    const CONNECTION_QUOTE_MAIN = 'quote-main';
    const CONNECTION_QUOTE_SLAVE = 'quote-slave';

    const CONNECTION_SALES_MAIN = 'sales-main';
    const CONNECTION_SALES_SLAVE = 'sales-slave';

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @var array
     */
    private $config;

    /**
     * @param DbConfig $dbConfig
     */
    public function __construct(DbConfig $dbConfig)
    {
        $this->dbConfig = $dbConfig;
    }

    /**
     * Creates and configures instance for final connections after merging with env variables.
     *
     * @param string $connectionType
     * @return ConnectionInterface
     * @throws RuntimeException
     * @throws ConfigException
     */
    public function create(string $connectionType): ConnectionInterface
    {
        switch ($connectionType) {
            case self::CONNECTION_MAIN:
                return $this->getConnectionData(DbConfig::CONNECTION_DEFAULT);
            case self::CONNECTION_SLAVE:
                return $this->getConnectionData(DbConfig::CONNECTION_DEFAULT, false);
            case self::CONNECTION_QUOTE_MAIN:
                return $this->getConnectionData(DbConfig::CONNECTION_CHECKOUT);
            case self::CONNECTION_QUOTE_SLAVE:
                return $this->getConnectionData(DbConfig::CONNECTION_CHECKOUT, false);
            case self::CONNECTION_SALES_MAIN:
                return $this->getConnectionData(DbConfig::CONNECTION_SALES);
            case self::CONNECTION_SALES_SLAVE:
                return $this->getConnectionData(DbConfig::CONNECTION_SALES, false);
            default:
                throw new RuntimeException(
                    sprintf('Connection with type %s does not exist', $connectionType)
                );
        }
    }

    /**
     * Returns connection data by name
     *
     * @param string $name
     * @param bool $isMain
     *
     * @return ConnectionInterface
     * @throws ConfigException
     */
    private function getConnectionData(string $name, bool $isMain = true): ConnectionInterface
    {
        if ($isMain) {
            $connectionConfig = $this->getConfig()[DbConfig::KEY_CONNECTION][$name] ?? [];
        } else {
            $connectionConfig = $this->getConfig()[DbConfig::KEY_SLAVE_CONNECTION][$name]
                ?? $this->getConfig()[DbConfig::KEY_CONNECTION][$name]
                ?? [];
        }
        return new Connection($connectionConfig);
    }

    /**
     * Returns database configuration
     *
     * @return array
     * @throws ConfigException
     */
    private function getConfig(): array
    {
        if (null === $this->config) {
            $this->config = $this->dbConfig->get();
        }
        return $this->config;
    }
}
