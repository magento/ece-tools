<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;

class Builder
{
    const CONFIG_DEFAULT_PHP_VERSION = 'latest';
    const CONFIG_DEFAULT_NGINX_VERSION = 'latest';
    const CONFIG_DEFAULT_DB_VERSION = '10';

    /**
     * @var Repository
     */
    private $config;

    /**
     * @param RepositoryFactory $repository
     */
    public function __construct(RepositoryFactory $repository)
    {
        $this->config = $repository->create();
    }

    /**
     * @param string $version
     * @throws Exception
     */
    public function setPhpVersion(string $version)
    {
        $supportedVersions = [
            '7.0',
            '7.1',
            self::CONFIG_DEFAULT_PHP_VERSION,
        ];

        if (!\in_array($version, $supportedVersions, true)) {
            throw new Exception('PHP version is not supported');
        }

        $this->config->set('php.version', $version);
    }

    /**
     * @param string $version
     * @throws Exception
     */
    public function setNginxVersion(string $version)
    {
        $supportedVersions = [
            '1.9',
            self::CONFIG_DEFAULT_NGINX_VERSION,
        ];

        if (!\in_array($version, $supportedVersions, true)) {
            throw new Exception('Nginx version is not supported');
        }

        $this->config->set('nginx.version', $version);
    }

    /**
     * @param string $version
     * @throws Exception
     */
    public function setDbVersion(string $version)
    {
        $supportedVersions = [
            self::CONFIG_DEFAULT_DB_VERSION,
        ];

        if (!\in_array($version, $supportedVersions, true)) {
            throw new Exception('DB version is not supported');
        }

        $this->config->set('db.version', $version);
    }

    /**
     * @return array
     */
    public function build(): array
    {
        return [
            'version' => '2',
            'services' => [
                'fpm' => $this->getFpmService(),
                'cli' => $this->getCliService(),
                'db' => $this->getDbService(),
                'web' => $this->getWebService(),
                'appdata' => [
                    'image' => 'tianon/true',
                    'volumes' => [
                        '.:/var/www/magento',
                        '/var/www/magento/vendor',
                        '/var/www/magento/generated',
                        '/var/www/magento/pub',
                        '/var/www/magento/var',
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
            'env_file' => [
                './docker/global.env',
                './docker/config.env',
            ],
        ];
    }

    /**
     * @return array
     */
    private function getCliService(): array
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
            ],
            'links' => [
                'fpm',
                'db',
            ],
            'volumes_from' => [
                'appdata',
            ],
            'env_file' => [
                './docker/global.env',
                './docker/config.env',
            ],
        ];
    }
}
