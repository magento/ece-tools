<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Service;

/**
 * Interface for installed services.
 */
interface ServiceInterface
{
    const NAME_PHP = 'php';
    const NAME_DB = 'mysql';
    const NAME_NGINX = 'nginx';
    const NAME_REDIS = 'redis';
    const NAME_ELASTICSEARCH = 'elasticsearch';
    const NAME_RABBITMQ = 'rabbitmq';
    const NAME_NODE = 'node';
    const NAME_VARNISH = 'varnish';

    /**
     * Checks if service is installed.
     *
     * @return bool
     */
    public function isInstalled(): bool;

    /**
     * Returns service configuration.
     *
     * @return array
     */
    public function getConfiguration(): array;

    /**
     * Returns version of the service.
     * Returns '0' in cases when can't retrieve service version.
     *
     * @return string
     */
    public function getVersion(): string;
}
