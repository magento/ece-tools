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
class DevBuilder implements BuilderInterface
{
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
     * @inheritdoc
     */
    public function setPhpVersion(string $version)
    {
        $this->setVersion(self::PHP_VERSION, $version, self::PHP_VERSIONS);
    }

    /**
     * @inheritdoc
     */
    public function setNginxVersion(string $version)
    {
        $this->setVersion(self::NGINX_VERSION, $version, [
            '1.9',
            self::DEFAULT_NGINX_VERSION,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function setDbVersion(string $version)
    {
        $this->setVersion(self::DB_VERSION, $version, [
            self::DEFAULT_DB_VERSION,
        ]);
    }

    /**
     * @param string $key
     * @param string $version
     * @param array $supportedVersions
     * @throws ConfigurationMismatchException
     */
    private function setVersion(string $key, string $version, array $supportedVersions)
    {
        $parts = explode('.', $key);
        $name = reset($parts);

        if (!\in_array($version, $supportedVersions, true)) {
            throw new ConfigurationMismatchException(sprintf(
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
                'redis' => $this->serviceFactory->create(ServiceFactory::SERVICE_REDIS)->get(),
                'fpm' => $this->getFpmService(),
                /** For backward compatibility. */
                'cli' => $this->getCliService(false),
                'build' => $this->getCliService(false),
                'deploy' => $this->getCliService(true),
                'db' => $this->getDbService(),
                'web' => $this->getWebService(),
                'cron' => $this->getCronService(),
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
                        './docker/mysql/docker-entrypoint-initdb.d:/docker-entrypoint-initdb.d',
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
            'image' => sprintf(
                'magento/magento-cloud-docker-php:%s-fpm',
                $this->config->get(self::PHP_VERSION, self::DEFAULT_PHP_VERSION)
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
        if (file_exists(getenv('HOME') . '/.cache/composer')) {
            $composeCacheDirectory = '~/.cache/composer';
        } else {
            $composeCacheDirectory = '~/.composer/cache';
        }

        return [
            'image' => sprintf(
                'magento/magento-cloud-docker-php:%s-cli',
                $this->config->get(self::PHP_VERSION, self::DEFAULT_PHP_VERSION)
            ),
            'links' => [
                'db',
                'redis',
            ],
            'volumes' => [
                $composeCacheDirectory . ':/root/.composer/cache',
                $this->getMagentoVolume($isReadOnly),
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
    private function getDbService(): array
    {
        return [
            'image' => sprintf(
                'mariadb:%s',
                $this->config->get(self::DB_VERSION, self::DEFAULT_DB_VERSION)
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
                $this->config->get(self::NGINX_VERSION, self::DEFAULT_NGINX_VERSION)
            ),
            'ports' => [
                '8080:80',
                '443:443',
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

    /**
     * @return array
     */
    private function getCronService(): array
    {
        $cliService = $this->getCliService(true);
        $cliService['command'] = 'run-cron';

        return $cliService;
    }
}
