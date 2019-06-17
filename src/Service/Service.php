<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Service;

/**
 * Generic service definition.
 */
class Service
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
     * @var
     */
    private $servicesVersion;

    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * @param ElasticSearch $elasticSearch
     */
    public function __construct(
        ElasticSearch $elasticSearch
    ) {
        $this->elasticSearch = $elasticSearch;
    }

    public function getInstalledVersion()
    {
        $services = [
            Service::NAME_PHP => PHP_VERSION
        ];

        if ($this->elasticSearch->getVersion()) {
            $services[Service::NAME_REDIS] = $this->elasticSearch->getVersion();
        }

        if ($redisInstalled) {
            $services[Service::NAME_RABBITMQ] = $this->elasticSearch->getVersion();
        }
        Service::NAME_REDIS => $this->elasticSearch->getVersion(),
            Service::NAME_RABBITMQ,
            Service::NAME_ELASTICSEARCH,
        ];
    }

    /**
     * @param string $serviceName
     * @return null
     */
    public function getVersion(string $serviceName): string
    {
        return $this->getInstalledVersion()[$serviceName] ?? null;
    }
}
