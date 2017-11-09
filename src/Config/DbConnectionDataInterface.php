<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

/**
 * Database connection data interface
 */
interface DbConnectionDataInterface
{
    /**
     * Returns DB host name or IP address
     *
     * @return string
     */
    public function getHost();

    /**
     * Returns TCP/IP port number to use for the connection
     *
     * @return int
     */
    public function getPort();

    /**
     * Returns DB name
     *
     * @return string
     */
    public function getDbName();

    /**
     * Returns user name for connecting to the server
     *
     * @return string
     */
    public function getUser();

    /**
     * Returns password to use when connecting to the server
     *
     * @return string|null
     */
    public function getPassword();
}
