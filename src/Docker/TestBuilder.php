<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Config\RepositoryFactory;

/**
 * @inheritdoc
 */
class TestBuilder implements BuilderInterface
{
    /**
     * @var Repository
     */
    private $config;

    /**
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct(RepositoryFactory $repositoryFactory)
    {
        $this->config = $repositoryFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function setPhpVersion(string $version)
    {
        $this->setVersion(self::PHP_VERSION, $version, [
            '7.0',
            self::DEFAULT_PHP_VERSION,
        ]);
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
                'fpm' => $this->getFpmService(),
                'cli' => $this->getCliService(),
                'db' => $this->getDbService(),
                'web' => $this->getWebService(),
                'appdata' => [
                    'image' => 'tianon/true',
                    'volumes' => [
                        '.:/var/www/ece-tools',
                        '/var/www/magento',
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
            'env_file' => [
                './docker/global.env',
                './docker/composer.env',
            ],
        ];
    }

    /**
     * @return array
     */
    private function getCliService(): array
    {
        return [
            'image' => sprintf(
                'magento/magento-cloud-docker-php:%s-cli',
                $this->config->get(self::PHP_VERSION, self::DEFAULT_PHP_VERSION)
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
                './docker/composer.env',
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
                './docker/composer.env',
            ],
        ];
    }
}
