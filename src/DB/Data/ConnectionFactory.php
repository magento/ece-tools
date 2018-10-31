<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB\Data;

use Magento\MagentoCloud\Config\Database\MergedConfig;

/**
 * Responsible for creating and configuring Magento\MagentoCloud\DB\Data\ConnectionInterface instances.
 */
class ConnectionFactory
{
    const CONNECTION_MAIN = 'main';
    const CONNECTION_SLAVE = 'slave';

    /**
     * @var MergedConfig
     */
    private $mergedConfig;

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
                $connectionData = $this->mergedConfig->get()['connection']['default'] ?? [];
                $connection = new Connection($connectionData);
                break;
            case self::CONNECTION_SLAVE:
                $connectionData = $this->mergedConfig->get()['slave_connection']['default']
                    ?? $this->mergedConfig->get()['connection']['default']
                    ?? [];
                $connection = new Connection($connectionData);
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Connection with type %s doesn\'t exist', $connectionType)
                );
        }

        return $connection;
    }
}
