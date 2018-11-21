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
 * Docker configuration builder.
 *
 * @codeCoverageIgnore
 */
class DevBuilder implements BuilderInterface
{
    const DEFAULT_NGINX_VERSION = 'latest';
    const DEFAULT_VARNISH_VERSION = 'latest';

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param ServiceFactory $serviceFactory
     * @param FileList $fileList
     * @param Config $config
     */
    public function __construct(ServiceFactory $serviceFactory, FileList $fileList, Config $config)
    {
        $this->serviceFactory = $serviceFactory;
        $this->fileList = $fileList;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function build(Repository $config): array
    {
        $phpVersion = $config->get(self::PHP_VERSION, '') ?: $this->config->getPhpVersion();
        $dbVersion = $config->get(self::DB_VERSION, '') ?: $this->config->getServiceVersion(Config::KEY_DB);

        $services = [
            'db' => $this->serviceFactory->create(
                ServiceFactory::SERVICE_DB,
                $dbVersion,
                [
                    'ports' => [3306],
                    'volumes' => [
                        '/var/lib/mysql',
                        './docker/mysql/docker-entrypoint-initdb.d:/docker-entrypoint-initdb.d',
                    ],
                    'environment' => [
                        'MYSQL_ROOT_PASSWORD=magento2',
                        'MYSQL_DATABASE=magento2',
                        'MYSQL_USER=magento2',
                        'MYSQL_PASSWORD=magento2',
                    ],
                ]
            )
        ];

        $redisVersion = $config->get(self::REDIS_VERSION) ?: $this->config->getServiceVersion(Config::KEY_REDIS);

        if ($redisVersion) {
            $services['redis'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_REDIS,
                $redisVersion
            );
        }

        $esVersion = $config->get(self::ES_VERSION) ?: $this->config->getServiceVersion(Config::KEY_ELASTICSEARCH);

        if ($esVersion) {
            $services['elasticsearch'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_ELASTICSEARCH,
                $esVersion
            );
        }

        $rabbitMQVersion = $config->get(self::RABBIT_MQ_VERSION)
            ?: $this->config->getServiceVersion(Config::KEY_RABBIT_MQ);

        if ($rabbitMQVersion) {
            $services['rabbitmq'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_RABBIT_MQ,
                $rabbitMQVersion
            );
        }

        $cliDepends = array_keys($services);

        $services['varnish'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_VARNISH,
            self::DEFAULT_VARNISH_VERSION,
            ['depends_on' => ['web']]
        );
        $services['fpm'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_FPM,
            $phpVersion,
            [
                'ports' => [9000],
                'depends_on' => ['db'],
                'volumes_from' => ['appdata'],
                'volumes' => [$this->getMagentoVolume(false)],
                'env_file' => [
                    './docker/global.env',
                    './docker/config.env',
                ],
            ]
        );
        /** For backward compatibility. */
        $services['cli'] = $this->getCliService($phpVersion, false, $cliDepends);
        $services['build'] = $this->getCliService($phpVersion, false, $cliDepends);
        $services['deploy'] = $this->getCliService($phpVersion, true, $cliDepends);
        $services['web'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_NGINX,
            $config->get(self::NGINX_VERSION, self::DEFAULT_NGINX_VERSION),
            [
                'ports' => [
                    '8080:80',
                    '443:443',
                ],
                'depends_on' => [
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
            ]
        );
        $services['cron'] = $this->getCliService($phpVersion, true, $cliDepends, true);
        $services['appdata'] = [
            'image' => 'tianon/true',
            'volumes' => [
                './docker/mnt:/mnt',
                '/var/www/magento/vendor',
                '/var/www/magento/generated',
                '/var/www/magento/pub',
                '/var/www/magento/var',
                '/var/www/magento/app/etc',
            ],
        ];

        return [
            'version' => '2',
            'services' => $services,
        ];
    }

    /**
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->fileList->getMagentoDockerCompose();
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
     * @param string $version
     * @param bool $isReadOnly
     * @param array $depends
     * @param bool $cron
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCliService(string $version, bool $isReadOnly, array $depends, bool $cron = false): array
    {
        $composeCacheDirectory = file_exists(getenv('HOME') . '/.cache/composer')
            ? '~/.cache/composer'
            : '~/.composer/cache';

        $config = $this->serviceFactory->create(
            ServiceFactory::SERVICE_CLI,
            $version,
            [
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
                    './docker/config.env',
                ],
            ]
        );

        if ($cron) {
            $config['command'] = 'run-cron';
        }

        return $config;
    }
}
