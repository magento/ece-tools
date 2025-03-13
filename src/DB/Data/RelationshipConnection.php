<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

/**
 * Data for connection to database from 'database*' relationship options.
 *
 * This connection can be overwritten by environment variable or in .magento.env.yaml.
 *
 * @see \Magento\MagentoCloud\DB\Data\Connection
 */
class RelationshipConnection implements ConnectionInterface
{
    /**
     * @var array
     */
    private $connectionData;

    /**
     * @param array $connectionData
     */
    public function __construct(array $connectionData)
    {
        $this->connectionData = $connectionData;
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

    /**
     * @inheritDoc
     */
    public function getDriverOptions()
    {
        return $this->connectionData['driver_options'] ?? [];
    }
}
