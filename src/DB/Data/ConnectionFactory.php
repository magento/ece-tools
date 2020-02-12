<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

use Magento\MagentoCloud\Config\Database\DbConfig;

/**
 * Responsible for creating and configuring Magento\MagentoCloud\DB\Data\ConnectionInterface instances.
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
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
     * @throws \RuntimeException
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
                throw new \RuntimeException(
                    sprintf('Connection with type %s doesn\'t exist', $connectionType)
                );
        }
    }

    private function getConnectionData($name, $isMain = true): ConnectionInterface
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

    private function getConfig(): array
    {
        if (null === $this->config) {
            $this->config = $this->dbConfig->get();
        }
        return $this->config;
    }
}
