<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker;

use Illuminate\Contracts\Config\Repository;

interface BuilderInterface
{
    const DEFAULT_PHP_VERSION = '7.1';
    const DEFAULT_NGINX_VERSION = 'latest';
    const DEFAULT_DB_VERSION = '10';

    const PHP_VERSION = 'php.version';
    const NGINX_VERSION = 'nginx.version';
    const DB_VERSION = 'db.version';

    /**
     * @return array
     */
    public function build(): array;


    /**
     * @param string $version
     * @throws Exception
     */
    public function setPhpVersion(string $version);

    /**
     * @param string $version
     * @throws Exception
     */
    public function setNginxVersion(string $version);

    /**
     * @param string $version
     * @throws Exception
     */
    public function setDbVersion(string $version);
}
