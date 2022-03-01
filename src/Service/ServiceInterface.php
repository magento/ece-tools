<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

/**
 * Interface for installed services.
 */
interface ServiceInterface
{
    const NAME_PHP = 'php';
    const NAME_DB_MYSQL = 'mysql';
    const NAME_DB_MARIA = 'mariadb';
    const NAME_DB_AURORA = 'aurora';
    const NAME_NGINX = 'nginx';
    const NAME_REDIS = 'redis';
    const NAME_REDIS_SESSION = 'redis-session';
    const NAME_ELASTICSEARCH = 'elasticsearch';
    const NAME_OPENSEARCH = 'opensearch';
    const NAME_RABBITMQ = 'rabbitmq';
    const NAME_NODE = 'node';
    const NAME_VARNISH = 'varnish';

    /**
     * Returns service configuration.
     * Returns an empty array if service isn't configured
     *
     * @return array
     */
    public function getConfiguration(): array;

    /**
     * Returns version of the service.
     * Returns '0' in cases when can't retrieve service version.
     *
     * @return string
     *
     * @throws ServiceException
     */
    public function getVersion(): string;
}
