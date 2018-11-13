<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Service;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;

/**
 * Create instance of Docker service configuration.
 */
class ServiceFactory
{
    const SERVICE_CLI = 'php-cli';
    const SERVICE_FPM = 'php-fpm';
    const SERVICE_REDIS = 'redis';
    const SERVICE_DB = 'db';
    const SERVICE_NGINX = 'nginx';
    const SERVICE_VARNISH = 'varnish';
    const SERVICE_ELASTICSEARCH = 'elasticsearch';
    const SERVICE_RABBIT_MQ = 'rabbitmq';

    const CONFIG = [
        self::SERVICE_CLI => [
            'image' => 'magento/magento-cloud-docker-php:%s-cli',
            'versions' => ['7.0', '7.1', '7.2']
        ],
        self::SERVICE_FPM => [
            'image' => 'magento/magento-cloud-docker-php:%s-fpm',
            'versions' => ['7.0', '7.1', '7.2']
        ],
        self::SERVICE_DB => [
            'image' => 'mariadb:%s',
            'versions' => ['10.0', '10.1', '10.2']
        ],
        self::SERVICE_NGINX => [
            'image' => 'magento/magento-cloud-docker-nginx:%s',
            'versions' => ['1.9', 'latest']
        ],
        self::SERVICE_VARNISH => [
            'image' => 'magento/magento-cloud-docker-varnish:%s',
            'versions' => ['latest'],
            'config' => [
                'environment' => [
                    'VIRTUAL_HOST' => 'magento2.docker',
                    'VIRTUAL_PORT' => 80,
                    'HTTPS_METHOD' => 'noredirect',
                ],
                'ports' => [
                    '80:80',
                ],
            ]
        ],
        self::SERVICE_REDIS => [
            'image' => 'redis:%s',
            'versions' => ['3.0', '3.2', '4.0'],
            'config' => [
                'volumes' => [
                    '/data',
                ],
                'ports' => [
                    6379,
                ],
            ]
        ],
        self::SERVICE_ELASTICSEARCH => [
            'image' => 'magento/magento-cloud-docker-elasticsearch:%s',
            'versions' => ['1.7', '2.4', '5.2']
        ],
        self::SERVICE_RABBIT_MQ => [
            'image' => 'rabbitmq:%s',
            'versions' => ['3.5', '3.7']
        ],
    ];

    /**
     * @param string $name
     * @param string $version
     * @param array $extendedConfig
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function create(string $name, string $version, array $extendedConfig = []): array
    {
        if (!array_key_exists($name, self::CONFIG)) {
            throw new ConfigurationMismatchException(sprintf(
                'Service "%s" is not supported',
                $name
            ));
        }

        $metaConfig = self::CONFIG[$name];
        $defaultConfig = $metaConfig['config'] ?? [];

        if (!in_array($version, $metaConfig['versions'], true)) {
            throw new ConfigurationMismatchException(sprintf(
                'Service "%s" does not support version "%s"',
                $name,
                $version
            ));
        }

        return array_replace(
            ['image' => sprintf($metaConfig['image'], $version)],
            $defaultConfig,
            $extendedConfig
        );
    }
}
