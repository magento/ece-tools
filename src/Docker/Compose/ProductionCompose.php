<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Compose;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Docker\ComposeManagerInterface;
use Magento\MagentoCloud\Docker\Config\Converter;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\ServiceFactory;
use Magento\MagentoCloud\Filesystem\FileList;

/**
 * Production compose configuration.
 *
 * @codeCoverageIgnore
 */
class ProductionCompose implements ComposeManagerInterface
{
    const DEFAULT_NGINX_VERSION = 'latest';
    const DEFAULT_VARNISH_VERSION = 'latest';
    const DEFAULT_TLS_VERSION = 'latest';

    const DIR_MAGENTO = '/app';

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
     * @var Converter
     */
    private $converter;

    /**
     * @param ServiceFactory $serviceFactory
     * @param FileList $fileList
     * @param Config $config
     * @param Converter $converter
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        FileList $fileList,
        Config $config,
        Converter $converter
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->fileList = $fileList;
        $this->config = $config;
        $this->converter = $converter;
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
        $phpVersion = $config->get(Config::KEY_PHP, '') ?: $this->config->getPhpVersion();
        $dbVersion = $config->get(Config::KEY_DB, '') ?: $this->config->getServiceVersion(Config::KEY_DB);

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

        $redisVersion = $config->get(Config::KEY_REDIS) ?: $this->config->getServiceVersion(Config::KEY_REDIS);

        if ($redisVersion) {
            $services['redis'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_REDIS,
                $redisVersion
            );
        }

        $esVersion = $config->get(Config::KEY_ELASTICSEARCH)
            ?: $this->config->getServiceVersion(Config::KEY_ELASTICSEARCH);

        if ($esVersion) {
            $services['elasticsearch'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_ELASTICSEARCH,
                $esVersion
            );
        }

        $rabbitMQVersion = $config->get(Config::KEY_RABBITMQ)
            ?: $this->config->getServiceVersion(Config::KEY_RABBITMQ);

        if ($rabbitMQVersion) {
            $services['rabbitmq'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_RABBIT_MQ,
                $rabbitMQVersion
            );
        }

        $cliDepends = array_keys($services);

        $services['fpm'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_FPM,
            $phpVersion,
            [
                'ports' => [9000],
                'depends_on' => ['db'],
                'extends' => 'generic',
                'volumes' => $this->getMagentoVolumes(true),
            ]
        );
        $services['build'] = $this->getCliService($phpVersion, false, $cliDepends, 'build.magento2.docker');
        $services['deploy'] = $this->getCliService($phpVersion, true, $cliDepends, 'deploy.magento2.docker');
        $services['web'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_NGINX,
            $config->get(Config::KEY_NGINX, self::DEFAULT_NGINX_VERSION),
            [
                'depends_on' => ['fpm'],
                'extends' => 'generic',
                'volumes' => $this->getMagentoVolumes(true),
            ]
        );
        $services['varnish'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_VARNISH,
            self::DEFAULT_VARNISH_VERSION,
            ['depends_on' => ['web']]
        );
        $services['tls'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_TLS,
            self::DEFAULT_TLS_VERSION,
            ['depends_on' => ['varnish']]
        );
        $services['cron'] = $this->getCronCliService($phpVersion, true, $cliDepends, 'cron.magento2.docker');
        $services['generic'] = [
            'image' => 'alpine',
            'environment' => $this->converter->convert($this->getVariables()),
            'env_file' => [
                './docker/config.env',
            ],
        ];

        $volumeConfig = [
            'driver_opts' => [
                'type' => 'tmpfs',
                'device' => 'tmpfs'
            ]
        ];

        return [
            'version' => '2',
            'services' => $services,
            'volumes' => [
                'magento' => [
                    'driver_opts' => [
                        'type' => 'none',
                        'device' => '${PWD}',
                        'o' => 'bind'
                    ]
                ],
                'magento-vendor' => $volumeConfig,
                'magento-generated' => $volumeConfig,
                'magento-setup' => $volumeConfig,
                'magento-var' => $volumeConfig,
                'magento-etc' => $volumeConfig,
                'magento-static' => $volumeConfig,
                'magento-media' => $volumeConfig,
            ]
        ];
    }

    /**
     * @param string $version
     * @param bool $isReadOnly
     * @param array $depends
     * @param string $hostname
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCronCliService(string $version, bool $isReadOnly, array $depends, string $hostname): array
    {
        $config = $this->getCliService($version, $isReadOnly, $depends, $hostname);

        if ($cronConfig = $this->config->getCron()) {
            $preparedCronConfig = [];

            foreach ($cronConfig as $job) {
                $preparedCronConfig[] = sprintf(
                    '%s root cd %s && %s >> %s/var/log/cron.log',
                    $job['spec'],
                    self::DIR_MAGENTO,
                    str_replace('php ', '/usr/local/bin/php ', $job['cmd']),
                    self::DIR_MAGENTO
                );
            }

            $config['environment'] = [
                'CRONTAB' => implode(PHP_EOL, $preparedCronConfig)
            ];
        }

        $config['command'] = 'run-cron';

        return $config;
    }

    /**
     * @param string $version
     * @param bool $isReadOnly
     * @param array $depends
     * @param string $hostname
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCliService(
        string $version,
        bool $isReadOnly,
        array $depends,
        string $hostname
    ): array {
        $config = $this->serviceFactory->create(
            ServiceFactory::SERVICE_CLI,
            $version,
            [
                'hostname' => $hostname,
                'depends_on' => $depends,
                'extends' => 'generic',
                'volumes' => array_merge(
                    $this->getMagentoVolumes($isReadOnly),
                    $this->getComposerVolumes(),
                    [
                        './docker/mnt:/mnt',
                        './docker/tmp:/tmp'
                    ]
                )
            ]
        );

        return $config;
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
     * @return array
     */
    protected function getMagentoVolumes(bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        return [
            'magento:' . self::DIR_MAGENTO . $flag,
            'magento-vendor:' . self::DIR_MAGENTO . '/vendor' . $flag,
            'magento-generated:' . self::DIR_MAGENTO . '/generated' . $flag,
            'magento-setup:' . self::DIR_MAGENTO . '/setup' . $flag,
            'magento-var:' . self::DIR_MAGENTO . '/var:rw',
            'magento-etc:' . self::DIR_MAGENTO . '/app/etc:rw',
            'magento-static:' . self::DIR_MAGENTO . '/pub/static:rw',
            'magento-media:' . self::DIR_MAGENTO . '/pub/media:rw',
        ];
    }

    /***
     * @return array
     */
    private function getComposerVolumes(): array
    {
        $composeCacheDirectory = file_exists(getenv('HOME') . '/.cache/composer')
            ? '~/.cache/composer'
            : '~/.composer/cache';

        return [
            $composeCacheDirectory . ':/root/.composer/cache',
        ];
    }

    /**
     * @return array
     */
    protected function getVariables(): array
    {
        return [
            'PHP_MEMORY_LIMIT' => '2048M',
            'DEBUG' => 'false',
            'ENABLE_SENDMAIL' => 'false',
            'UPLOAD_MAX_FILESIZE' => '64M',
            'MAGENTO_ROOT' => self::DIR_MAGENTO,
            'PHP_ENABLE_XDEBUG' => 'false',
            # name of your server in IDE
            'PHP_IDE_CONFIG' => 'serverName=magento_cloud_docker',
            # docker host for developer environments, can be different for your OS
            'XDEBUG_CONFIG' => 'remote_host=host.docker.internal',
        ];
    }
}
