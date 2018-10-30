<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker;

/**
 * General Builder interface.
 */
interface BuilderInterface
{
    const PHP_VERSIONS = ['7.0', '7.1', '7.2',];
    const DEFAULT_PHP_VERSION = '7.1';

    const ES_VERSIONS = ['1.7', '2.4', '5.2'];
    const DEFAULT_ES_VERSION = '2.4';

    const DEFAULT_NGINX_VERSION = 'latest';
    const DEFAULT_DB_VERSION = '10';

    const PHP_VERSION = 'php.version';
    const NGINX_VERSION = 'nginx.version';
    const DB_VERSION = 'db.version';
    const ES_VERSION = 'es.version';

    /**
     * @return array
     */
    public function build(): array;

    /**
     * @param string $version
     * @throws ConfigurationMismatchException
     */
    public function setPhpVersion(string $version);

    /**
     * @param string $version
     * @throws ConfigurationMismatchException
     */
    public function setNginxVersion(string $version);

    /**
     * @param string $version
     * @throws ConfigurationMismatchException
     */
    public function setDbVersion(string $version);

    /**
     * @param string $version
     * @throws ConfigurationMismatchException
     */
    public function setESVersion(string $version);
}
