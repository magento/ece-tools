<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

/**
 * Data for database connection after merging with environment variables.
 */
class Connection implements ConnectionInterface
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
        $host = $this->connectionData['host'] ?? '';

        if (strpos($host, '/') === false && strpos($host, ':') !== false) {
            $host = explode(':', $host)[0];
        }

        return $host;
    }

    /**
     * @inheritdoc
     */
    public function getPort()
    {
        $port = $this->connectionData['port'] ?? '';

        if (empty($port)) {
            $host = $this->connectionData['host'] ?? '';

            if (strpos($host, '/') === false && strpos($host, ':') !== false) {
                $port = explode(':', $host)[1];
            }
        }

        return $port;
    }

    /**
     * @inheritdoc
     */
    public function getDbName()
    {
        return $this->connectionData['dbname'] ?? '';
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
