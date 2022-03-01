<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

/**
 * Database connection data interface
 */
interface ConnectionInterface
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
     * @return string
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

    /**
     * Returns driver options
     *
     * @return array
     */
    public function getDriverOptions();
}
