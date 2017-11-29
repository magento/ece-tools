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
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Checks whether project is integration or not.
     *
     * @return bool Return true if project is integration, otherwise return false (for staging or production)
     */
    private function isIntegration()
    {
        //return empty($_ENV['REGISTRY']);
        //while $_ENV['REGISTRY'] is not approved by platform we check the DB host name
        //TODO: use method from Environment class which will be implemented in MAGECLOUD-1122
        return $this->environment->getDbHost() === 'database.internal';
    }

    /**
     * Returns the host name for backup.
     * Integration project has only one node and host should be used the same as retrieved from environment variables.
     * Production or staging projects have 3 nodes but for read operations we need to connect to localhost
     * with 3304 port and this connection will proxy to appropriate server.
     *
     * {@inheritdoc}
     */
    public function getHost()
    {
        return $this->isIntegration() ? $this->environment->getDbHost() : '127.0.0.1';
    }

    /**
     * Returns ports for DB connection for backup.
     * There are several available ports:
     *  - 3306 - talks to master DB
     *  - 3307 - talks to local node
     *  - 3304 - is used for read only operations
     * For production or staging server we cannot make such operations as backup from active master,
     * so we should always use 3304 for them for localhost, this connection will proxy to appropriate server.
     * For integration we have only one node and 3306 is always used.
     *
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->isIntegration() ? '3306' : '3304';
    }

    /**
     * @inheritdoc
     */
    public function getDbName()
    {
        return $this->environment->getDbName();
    }

    /**
     * @inheritdoc
     */
    public function getUser()
    {
        return $this->environment->getDbUser();
    }

    /**
     * @inheritdoc
     */
    public function getPassword()
    {
        return $this->environment->getDbPassword();
    }
}
