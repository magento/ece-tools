<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker;

use Illuminate\Contracts\Config\Repository;

/**
 * General Builder interface.
 */
interface ComposeInterface
{
    const PHP_VERSION = 'php.version';
    const NGINX_VERSION = 'nginx.version';
    const DB_VERSION = 'db.version';
    const REDIS_VERSION = 'redis.version';
    const ES_VERSION = 'es.version';
    const RABBIT_MQ_VERSION = 'rmq.version';
    const NODE_VERSION = 'node.version';

    /**
     * @param Repository $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function build(Repository $config): array;

    /**
     * @return string
     */
    public function getPath(): string;
}
