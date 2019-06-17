<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Service;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;

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
    const SERVICE_TLS = 'tls';
    const SERVICE_NODE = 'node';

    const CONFIG = [
        self::SERVICE_CLI => [
            'image' => 'magento/magento-cloud-docker-php:%s-cli'
        ],
        self::SERVICE_FPM => [
            'image' => 'magento/magento-cloud-docker-php:%s-fpm'
        ],
        self::SERVICE_DB => [
            'image' => 'mariadb:%s'
        ],
        self::SERVICE_NGINX => [
            'image' => 'magento/magento-cloud-docker-nginx:%s'
        ],
        self::SERVICE_VARNISH => [
            'image' => 'magento/magento-cloud-docker-varnish:%s',
            'config' => [
                'environment' => [
                    'VIRTUAL_HOST=magento2.docker',
                    'VIRTUAL_PORT=80',
                    'HTTPS_METHOD=noredirect',
                ],
                'ports' => [
                    '80:80'
                ],
            ]
        ],
        self::SERVICE_TLS => [
            'image' => 'magento/magento-cloud-docker-tls:%s',
            'versions' => ['latest'],
            'config' => [
                'ports' => [
                    '443:443'
                ],
                'external_links' => [
                    'varnish:varnish'
                ]
            ]
        ],
        self::SERVICE_REDIS => [
            'image' => 'redis:%s',
            'config' => [
                'volumes' => [
                    '/data',
                ],
                'ports' => [6379],
            ]
        ],
        self::SERVICE_ELASTICSEARCH => [
            'image' => 'magento/magento-cloud-docker-elasticsearch:%s'
        ],
        self::SERVICE_RABBIT_MQ => [
            'image' => 'rabbitmq:%s',
        ],
        self::SERVICE_NODE => [
            'image' => 'node:%s',
        ],
    ];

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(File $file, DirectoryList $directoryList)
    {
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @param string $name
     * @param string $version
     * @param array $extendedConfig
     * @return array
     * @throws ConfigurationMismatchException
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
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
        $extendedConfig = $this->prepareServiceConfig($name, $version, $extendedConfig);

        return array_replace(
            ['image' => sprintf($metaConfig['image'], $version)],
            $defaultConfig,
            $extendedConfig
        );
    }

    /**
     * @param string $name
     * @param string $version
     * @param array $extendedConfig
     * @return array
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    private function prepareServiceConfig(string $name, string $version, array $extendedConfig): array
    {
        $config = $extendedConfig;
        if ($name == self::SERVICE_ELASTICSEARCH && $extendedConfig['plugins']) {
            $config = [
                'build' => [
                    'context' => 'docker/elasticsearch'
                ]
            ];
            // create docker/elasticsearch/Dockerfile file
            $pluginInstall = [];
            foreach ($extendedConfig['plugins'] as $pluginName) {
                $pluginInstall[] = 'bin/elasticsearch-plugin install ' . $pluginName;
            }
            $dockerFile = 'FROM ' . sprintf(self::CONFIG[self::SERVICE_ELASTICSEARCH]['image'], $version) . "\n\n"
                . 'RUN ' . implode($pluginInstall, "&& \\ \n" ) . "\n";

            $this->file->filePutContents(
                $this->directoryList->getMagentoRoot() . '/docker/elasticsearch/Dockerfile',
                $dockerFile
            );
        }

        return $config;
    }
}
