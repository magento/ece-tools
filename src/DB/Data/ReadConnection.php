<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\DB\Data;

use Magento\MagentoCloud\Config\Environment;

/**
 * Data for read only connection to database.
 * Should be used for backup or other read operations.
 */
class ReadConnection implements ConnectionInterface
{
    /**
     * Resource of environment data
     * @var Environment
     */
    private $environment;

    /**
     * Array with necessary connection data
     * @var array
     */
    private $connectionData;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
        $this->connectionData = $this->getConnectionData();
    }

    /**
     * Retrieves read only connection data from 'database-slave' relationships if exists,
     * otherwise retrieves write connection (in case of integration environment)
     *
     * @return array
     */
    private function getConnectionData(): array
    {
        return $this->environment->getRelationship('database-slave')[0]
            ?? $this->environment->getRelationship('database')[0]
            ?? [];
    }

    /**
     * @inheritdoc
     */
    public function getHost()
    {
        return $this->connectionData['host'] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getPort()
    {
        return $this->connectionData['port'] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getDbName()
    {
        return $this->connectionData['path'] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getUser()
    {
        return $this->connectionData['username'] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getPassword()
    {
        return $this->connectionData['password'] ?? '';
    }
}
