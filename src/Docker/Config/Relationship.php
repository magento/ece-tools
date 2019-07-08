<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker\Config;

use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Service\ServiceInterface;

/**
 * Generates relationship data for current configuration
 * based on services in .magento/service.yaml and relationships in .magento.app.yaml
 */
class Relationship
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Default relationships configuration
     *
     * @var array
     */
    private $defaultConfiguration = [
        'database' => [
            [
                'host' => 'db',
                'path' => 'magento2',
                'password' => 'magento2',
                'username' => 'magento2',
                'port' => '3306'
            ],
        ],
        'redis' => [
            [
                'host' => 'redis',
                'port' => '6379'
            ]
        ],
        'elasticsearch' => [
            [
                'host' => 'elasticsearch',
                'port' => '9200',
            ],
        ],
        'rabbitmq' => [
            [
                'host' => 'rabbitmq',
                'port' => '5672',
                'username' => 'guest',
                'password' => 'guest',
            ]
        ],
    ];

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Generates relationship data for current configuration
     *
     * @throws ConfigurationMismatchException
     */
    public function get(): array
    {
        $relationships = [];
        foreach ($this->defaultConfiguration as $serviceName => $serviceConfig) {
            if ($this->config->getServiceVersion($this->convertServiceName($serviceName))) {
                $relationships[$serviceName] = $serviceConfig;
            }
        }

        return $relationships;
    }

    /**
     * Convert services names for compatibility with `getServiceVersion` method.
     *
     * @param string $serviceName
     * @return string
     */
    private function convertServiceName(string $serviceName): string
    {
        $map = [
            'database' => ServiceInterface::NAME_DB
        ];

        return $map[$serviceName] ?? $serviceName;
    }
}
