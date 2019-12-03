<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

use Magento\MagentoCloud\Config\Database\MergedConfig;

/**
 * Responsible for creating and configuring Magento\MagentoCloud\DB\Data\ConnectionInterface instances.
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * @var MergedConfig
     */
    private $mergedConfig;

    /**
     * @var array
     */
    private $config;

    /**
     * @param MergedConfig $mergedConfig
     */
    public function __construct(MergedConfig $mergedConfig)
    {
        $this->mergedConfig = $mergedConfig;
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
                return $this->getConnectionData(MergedConfig::CONNECTION_DEFAULT);
            case self::CONNECTION_SLAVE:
                return $this->getConnectionData(MergedConfig::CONNECTION_DEFAULT, false);
            case self::CONNECTION_QUOTE_MAIN:
                return $this->getConnectionData(MergedConfig::CONNECTION_CHECKOUT);
            case self::CONNECTION_QUOTE_SLAVE:
                return $this->getConnectionData(MergedConfig::CONNECTION_CHECKOUT, false);
            case self::CONNECTION_SALES_MAIN:
                return $this->getConnectionData(MergedConfig::CONNECTION_SALES);
            case self::CONNECTION_SALES_SLAVE:
                return $this->getConnectionData(MergedConfig::CONNECTION_SALES, false);
            default:
                throw new \RuntimeException(
                    sprintf('Connection with type %s doesn\'t exist', $connectionType)
                );
        }
    }

    private function getConnectionData($name, $isMain = true): ConnectionInterface
    {
        if ($isMain) {
            $connectionConfig = $this->getConfig()[MergedConfig::CONNECTION][$name] ?? [];
        } else {
            $connectionConfig = $this->getConfig()[MergedConfig::SLAVE_CONNECTION][$name]
                ?? $this->getConfig()[MergedConfig::CONNECTION][$name]
                ?? [];
        }
        return new Connection($connectionConfig);
    }

    private function getConfig(): array
    {
        if (null === $this->config) {
            $this->config = $this->mergedConfig->get()[MergedConfig::DB];
        }
        return $this->config;
    }
}
