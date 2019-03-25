<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Docker\Service\ServiceFactory;
use Magento\MagentoCloud\Filesystem\FileList;

/**
 * Docker integration test builder.
 *
 * @codeCoverageIgnore
 */
class IntegrationV2Builder implements BuilderInterface
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @param FileList $fileList
     * @param ServiceFactory $serviceFactory
     */
    public function __construct(FileList $fileList, ServiceFactory $serviceFactory)
    {
        $this->fileList = $fileList;
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function build(Repository $repository): array
    {
        $phpVersion = $repository->get(self::PHP_VERSION);
        $dbVersion = $repository->get(self::DB_VERSION);
        $nginxVersion = $repository->get(self::NGINX_VERSION);

        $services = [
            'version' => '2',
            'services' => [
                'fpm' => $this->serviceFactory->create(
                    ServiceFactory::SERVICE_FPM,
                    $phpVersion,
                    [
                        'ports' => [9000],
                        'links' => [
                            'db',
                        ],
                        'volumes' => [
                            $this->getMagentoVolume(true)
                        ],
                        'volumes_from' => [
                            'appdata',
                        ],
                        'env_file' => [
                            './docker/global.env',
                            './docker/composer.env',
                        ],
                    ]
                ),
                'db' => $this->serviceFactory->create(
                    ServiceFactory::SERVICE_DB,
                    $dbVersion,
                    [
                        'ports' => [3306],
                        'volumes' => [
                            '/var/lib/mysql',
                        ],
                        'environment' => [
                            'MYSQL_ROOT_PASSWORD=magento2',
                            'MYSQL_DATABASE=magento2',
                            'MYSQL_USER=magento2',
                            'MYSQL_PASSWORD=magento2',
                        ],
                    ]
                ),
                'web' => $this->serviceFactory->create(
                    ServiceFactory::SERVICE_NGINX,
                    $nginxVersion,
                    [
                        'ports' => [
                            '8030:80',
                        ],
                        'links' => [
                            'fpm',
                            'db',
                        ],
                        'volumes' => [
                            $this->getMagentoVolume(true)
                        ],
                        'volumes_from' => [
                            'appdata',
                        ],
                        'env_file' => [
                            './docker/global.env',
                            './docker/composer.env',
                        ],
                    ]
                ),
            ],
            'volumes' => [
                'magento' => []
            ]
        ];

        $services['services']['build'] = $this->getCliService(
            $phpVersion,
            'build',
            false,
            ['db'],
            'build.magento2.docker'
        );
        $services['services']['deploy'] = $this->getCliService(
            $phpVersion,
            'deploy',
            false,
            ['db'],
            'deploy.magento2.docker'
        );
        $services['services']['cron'] = $this->getCliService(
            $phpVersion,
            'cron',
            false,
            ['db'],
            'cron.magento2.docker',
            true
        );
        $services ['services']['appdata'] = [
            'image' => 'tianon/true',
            'volumes' => [
                '.:/var/www/ece-tools',
                '/var/www/magento/pub/static',
                '/var/www/magento/pub/media',
                '/var/www/magento/var',
                '/var/www/magento/app/etc',
            ],
        ];

        return $services;
    }

    /**
     * @param string $version
     * @param string $name
     * @param bool $isReadOnly
     * @param array $depends
     * @param string $hostname
     * @param bool $cron
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCliService(
        string $version,
        string $name,
        bool $isReadOnly,
        array $depends,
        string $hostname,
        bool $cron = false
    ): array {
        $composeCacheDirectory = file_exists(getenv('HOME') . '/.cache/composer')
            ? '~/.cache/composer'
            : '~/.composer/cache';

        $config = $this->serviceFactory->create(
            ServiceFactory::SERVICE_CLI,
            $version,
            [
                'hostname' => $hostname,
                'container_name' => $name,
                'depends_on' => $depends,
                'volumes' => [
                    $composeCacheDirectory . ':/root/.composer/cache',
                    $this->getMagentoVolume($isReadOnly),
                ],
                'volumes_from' => [
                    'appdata',
                ],
                'env_file' => [
                    './docker/global.env',
                    './docker/composer.env',
                ],
            ]
        );

        if ($cron) {
            $config['command'] = 'run-cron';
        }

        return $config;
    }

    /**
     * @param bool $isReadOnly
     * @return string
     */
    private function getMagentoVolume(bool $isReadOnly): string
    {
        $volume = 'magento:/var/www/magento';

        return $isReadOnly ? $volume . ':ro' : $volume . ':rw';
    }

    /**
     * @inheritdoc
     */
    public function getConfigPath(): string
    {
        return $this->fileList->getToolsDockerCompose();
    }
}
