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
     * Retrieve read only connection data from 'database-slave' relationships if exists,
     * otherwise retrieve write connection (take place for integration environment)
     *
     * @return array
     */
    private function getConnectionData(): array
    {
        if ($this->environment->getRelationship('database-slave')) {
            return $this->connectionData = $this->environment->getRelationship('database-slave')[0] ?? [];
        }
        return $this->connectionData = $this->environment->getRelationship('database')[0] ?? [];
    }

    /**
     * @inheritdoc
     */
    public function getHost()
    {
        return $this->connectionData['host'] ?? '127.0.0.1';
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
