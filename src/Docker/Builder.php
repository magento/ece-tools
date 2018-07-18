<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\Service\ServiceFactory;

/**
 * Docker configuration builder.
 */
class Builder
{
    const CONFIG_DEFAULT_PHP_VERSION = '7.1';
    const CONFIG_DEFAULT_NGINX_VERSION = 'latest';
    const CONFIG_DEFAULT_DB_VERSION = '10';

    /**
     * Supported service versions.
     */
    const SUPPORTED_PHP_VERSIONS = [
        '7.0',
        self::CONFIG_DEFAULT_PHP_VERSION,
    ];
    const SUPPORTED_NGINX_VERSIONS = [
        '1.9',
        self::CONFIG_DEFAULT_NGINX_VERSION,
    ];
    const SUPPORTED_DB_VERSIONS = [
        self::CONFIG_DEFAULT_DB_VERSION,
    ];

    /**
     * @var Repository
     */
    private $config;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @param RepositoryFactory $repositoryFactory
     * @param ServiceFactory $serviceFactory
     */
    public function __construct(RepositoryFactory $repositoryFactory, ServiceFactory $serviceFactory)
    {
        $this->config = $repositoryFactory->create();
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * @param string $version
     * @throws Exception
     */
    public function setPhpVersion(string $version)
    {
        $this->setVersion('php.version', $version, self::SUPPORTED_PHP_VERSIONS);
    }

    /**
     * @param string $version
     * @throws Exception
     */
    public function setNginxVersion(string $version)
    {
        $this->setVersion('nginx.version', $version, self::SUPPORTED_NGINX_VERSIONS);
    }

    /**
     * @param string $version
     * @throws Exception
     */
    public function setDbVersion(string $version)
    {
        $this->setVersion('db.version', $version, self::SUPPORTED_DB_VERSIONS);
    }

    /**
     * @param string $key
     * @param string $version
     * @param array $supportedVersions
     * @throws Exception
     */
    private function setVersion(string $key, string $version, array $supportedVersions)
    {
        $parts = explode('.', $key);
        $name = reset($parts);

        if (!\in_array($version, $supportedVersions, true)) {
            throw new Exception(sprintf(
                'Service %s:%s is not supported',
                $name,
                $version
            ));
        }

        $this->config->set($key, $version);
    }

    /**
     * @return array
     */
    public function build(): array
    {
        return [
            'version' => '2',
            'services' => [
                'varnish' => $this->serviceFactory->create(ServiceFactory::SERVICE_VARNISH)->get(),
                'fpm' => $this->getFpmService(),
                /** For backward compatibility. */
                'cli' => $this->getCliService(false),
                'build' => $this->getCliService(false),
                'deploy' => $this->getCliService(true),
                'db' => $this->getDbService(),
                'web' => $this->getWebService(),
                'appdata' => [
                    'image' => 'tianon/true',
                    'volumes' => [
                        '/var/www/magento/vendor',
                        '/var/www/magento/generated',
                        '/var/www/magento/pub',
                        '/var/www/magento/var',
                        '/var/www/magento/app/etc',
                    ],
                ],
                'dbdata' => [
                    'image' => 'tianon/true',
                    'volumes' => [
                        '/var/lib/mysql',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param bool $isReadOnly
     * @return string
     */
    private function getMagentoVolume(bool $isReadOnly): string
    {
        $volume = '.:/var/www/magento';

        return $isReadOnly
            ? $volume . ':ro'
            : $volume . ':rw';
    }

    /**
     * @return array
     */
    private function getFpmService(): array
    {
        return [
            'hostname' => 'fpm.magento2.docker',
            'image' => sprintf(
                'magento/magento-cloud-docker-php:%s-fpm',
                $this->config->get('php.version', self::CONFIG_DEFAULT_PHP_VERSION)
            ),
            'ports' => [
                9000,
            ],
            'links' => [
                'db',
            ],
            'volumes_from' => [
                'appdata',
            ],
            'volumes' => [
                $this->getMagentoVolume(false),
            ],
            'env_file' => [
                './docker/global.env',
                './docker/config.env',
            ],
        ];
    }

    /**
     * @param bool $isReadOnly
     * @return array
     */
    private function getCliService(bool $isReadOnly): array
    {
        return [
            'hostname' => 'cli.magento2.docker',
            'image' => sprintf(
                'magento/magento-cloud-docker-php:%s-cli',
                $this->config->get('php.version', self::CONFIG_DEFAULT_PHP_VERSION)
            ),
            'links' => [
                'db',
            ],
            'volumes' => [
                '~/.composer/cache:/root/.composer/cache',
                $this->getMagentoVolume($isReadOnly),
            ],
            'volumes_from' => [
                'appdata',
            ],
            'env_file' => [
                './docker/global.env',
                './docker/config.env',
            ],
            'environment' => [
                'M2_SAMPLE_DATA=false',
            ],
        ];
    }

    /**
     * @return array
     */
    private function getDbService(): array
    {
        return [
            'image' => sprintf(
                'mariadb:%s',
                $this->config->get('db.version', self::CONFIG_DEFAULT_DB_VERSION)
            ),
            'ports' => [
                3306,
            ],
            'volumes_from' => [
                'dbdata',
            ],
            'environment' => [
                'MYSQL_ROOT_PASSWORD=magento2',
                'MYSQL_DATABASE=magento2',
                'MYSQL_USER=magento2',
                'MYSQL_PASSWORD=magento2',
            ],
        ];
    }

    /**
     * @return array
     */
    private function getWebService(): array
    {
        return [
            'image' => sprintf(
                'magento/magento-cloud-docker-nginx:%s',
                $this->config->get('nginx.version', self::CONFIG_DEFAULT_NGINX_VERSION)
            ),
            'ports' => [
                '8080:80',
                '8082:443',
            ],
            'links' => [
                'fpm',
                'db',
            ],
            'volumes_from' => [
                'appdata',
            ],
            'volumes' => [
                $this->getMagentoVolume(false),
            ],
            'env_file' => [
                './docker/global.env',
                './docker/config.env',
            ],
        ];
    }
}
