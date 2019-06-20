<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Compose;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Docker\ComposeInterface;
use Magento\MagentoCloud\Docker\Config\Converter;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\ServiceFactory;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Service\Service;

/**
 * Production compose configuration.
 *
 * @codeCoverageIgnore
 */
class ProductionCompose implements ComposeInterface
{
    const DEFAULT_NGINX_VERSION = 'latest';
    const DEFAULT_VARNISH_VERSION = 'latest';
    const DEFAULT_TLS_VERSION = 'latest';

    const DIR_MAGENTO = '/app';

    const CRON_ENABLED = true;

    /**
     * Extensions which should be installed by default
     */
    const DEFAULT_PHP_EXTENSIONS = [
        'bcmath',
        'bz2',
        'calendar',
        'exif',
        'gd',
        'gettext',
        'intl',
        'mysqli',
        'pcntl',
        'pdo_mysql',
        'soap',
        'sockets',
        'sysvmsg',
        'sysvsem',
        'sysvshm',
        'opcache',
        'zip',
    ];

    /**
     * Extensions which can be installed or uninstalled
     */
    const AVAILABLE_PHP_EXTENSIONS = [
        'bcmath' => '>=7.0.0 <7.3.0',
        'bz2' => '>=7.0.0 <7.3.0',
        'calendar' => '>=7.0.0 <7.3.0',
        'exif' => '>=7.0.0 <7.3.0',
        'gd' => '>=7.0.0 <7.3.0',
        'geoip' => '>=7.0.0 <7.3.0',
        'gettext' => '>=7.0.0 <7.3.0',
        'gmp' => '>=7.0.0 <7.3.0',
        'igbinary' => '>=7.0.0 <7.3.0',
        'imagick' => '>=7.0.0 <7.3.0',
        'imap' => '>=7.0.0 <7.3.0',
        'intl' => '>=7.0.0 <7.3.0',
        'ldap' => '>=7.0.0 <7.3.0',
        'mailparse' => '>=7.0.0 <7.3.0',
        'mcrypt' => '>=7.0.0 <7.2.0',
        'msgpack' => '>=7.0.0 <7.3.0',
        'mysqli' => '>=7.0.0 <7.3.0',
        'oauth' => '>=7.0.0 <7.3.0',
        'opcache' => '>=7.0.0 <7.3.0',
        'pdo_mysql' => '>=7.0.0 <7.3.0',
        'propro' => '>=7.0.0 <7.3.0',
        'pspell' => '>=7.0.0 <7.3.0',
        'raphf' => '>=7.0.0 <7.3.0',
        'recode' => '>=7.0.0 <7.3.0',
        'redis' => '>=7.0.0 <7.3.0',
        'shmop' => '>=7.0.0 <7.3.0',
        'soap' => '>=7.0.0 <7.3.0',
        'sockets' => '>=7.0.0 <7.3.0',
        'sodium' => '>=7.0.0 <7.3.0',
        'ssh2' => '>=7.0.0 <7.3.0',
        'sysvmsg' => '>=7.0.0 <7.3.0',
        'sysvsem' => '>=7.0.0 <7.3.0',
        'sysvshm' => '>=7.0.0 <7.3.0',
        'tidy' => '>=7.0.0 <7.3.0',
        'xdebug' => '>=7.0.0 <7.3.0',
        'xmlrpc' => '>=7.0.0 <7.3.0',
        'xsl' => '>=7.0.0 <7.3.0',
        'yaml' => '>=7.0.0 <7.3.0',
        'zip' => '>=7.0.0 <7.3.0',
        'pcntl' => '>=7.0.0 <7.3.0',
    ];

    /**
     * Extensions which built-in and can't be uninstalled
     */
    const BUILTIN_EXTENSIONS = [
        'ctype' => '>=7.0.0 <7.3.0',
        'curl' => '>=7.0.0 <7.3.0',
        'date' => '>=7.0.0 <7.3.0',
        'dom' => '>=7.0.0 <7.3.0',
        'fileinfo' => '>=7.0.0 <7.3.0',
        'filter' => '>=7.0.0 <7.3.0',
        'ftp' => '>=7.0.0 <7.3.0',
        'hash' => '>=7.0.0 <7.3.0',
        'iconv' => '>=7.0.0 <7.3.0',
        'json' => '>=7.0.0 <7.3.0',
        'mbstring' => '>=7.0.0 <7.3.0',
        'mysqlnd' => '>=7.0.0 <7.3.0',
        'openssl' => '>=7.0.0 <7.3.0',
        'pcre' => '>=7.0.0 <7.3.0',
        'pdo' => '>=7.0.0 <7.3.0',
        'pdo_sqlite' => '>=7.0.0 <7.3.0',
        'phar' => '>=7.0.0 <7.3.0',
        'posix' => '>=7.0.0 <7.3.0',
        'readline' => '>=7.0.0 <7.3.0',
        'session' => '>=7.0.0 <7.3.0',
        'simplexml' => '>=7.0.0 <7.3.0',
        'sqlite3' => '>=7.0.0 <7.3.0',
        'tokenizer' => '>=7.0.0 <7.3.0',
        'xml' => '>=7.0.0 <7.3.0',
        'xmlreader' => '>=7.0.0 <7.3.0',
        'xmlwriter' => '>=7.0.0 <7.3.0',
        'zlib' => '>=7.0.0 <7.3.0',
    ];

    /**
     * Extensions which should be ignored
     */
    const IGNORED_EXTENSIONS = ['blackfire', 'newrelic'];

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var FileList
     */
    protected $fileList;

    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @param ServiceFactory $serviceFactory
     * @param FileList $fileList
     * @param Config $config
     * @param Converter $converter
     * @param VersionParser $versionParser
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        FileList $fileList,
        Config $config,
        Converter $converter,
        VersionParser $versionParser
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->fileList = $fileList;
        $this->config = $config;
        $this->converter = $converter;
        $this->versionParser = $versionParser;
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
        $phpVersion = $config->get(Service::NAME_PHP, '') ?: $this->getPhpVersion();
        $dbVersion = $config->get(Service::NAME_DB, '') ?: $this->getServiceVersion(Service::NAME_DB);

        $services = [
            'db' => $this->serviceFactory->create(
                ServiceFactory::SERVICE_DB,
                $dbVersion,
                [
                    'ports' => [3306],
                    'volumes' => [
                        '/var/lib/mysql',
                        './.docker/mysql/docker-entrypoint-initdb.d:/docker-entrypoint-initdb.d',
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

        $redisVersion = $config->get(Service::NAME_REDIS) ?: $this->getServiceVersion(Service::NAME_REDIS);

        if ($redisVersion) {
            $services['redis'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_REDIS,
                $redisVersion
            );
        }

        $esVersion = $config->get(Service::NAME_ELASTICSEARCH)
            ?: $this->getServiceVersion(Service::NAME_ELASTICSEARCH);

        if ($esVersion) {
            $services['elasticsearch'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_ELASTICSEARCH,
                $esVersion
            );
        }

        $nodeVersion = $config->get(Service::NAME_NODE);

        if ($nodeVersion) {
            $services['node'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_NODE,
                $nodeVersion,
                ['volumes' => $this->getMagentoVolumes(false)]
            );
        }

        $rabbitMQVersion = $config->get(Service::NAME_RABBITMQ)
            ?: $this->getServiceVersion(Service::NAME_RABBITMQ);

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
        $services['build'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_CLI,
            $phpVersion,
            [
                'hostname' => 'deploy.magento2.docker',
                'depends_on' => $cliDepends,
                'extends' => 'generic',
                'volumes' => array_merge(
                    $this->getMagentoBuildVolumes(false),
                    $this->getComposerVolumes(),
                    [
                        './.docker/mnt:/mnt',
                        './.docker/tmp:/tmp'
                    ]
                )
            ]
        );
        $services['deploy'] = $this->getCliService($phpVersion, true, $cliDepends, 'deploy.magento2.docker');
        $services['web'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_NGINX,
            $config->get(Service::NAME_NGINX, self::DEFAULT_NGINX_VERSION),
            [
                'hostname' => 'web.magento2.docker',
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
        $phpExtensions = $this->getPhpExtensions($phpVersion);
        $services['generic'] = [
            'image' => 'alpine',
            'environment' => $this->converter->convert(array_merge(
                $this->getVariables(),
                !empty($phpExtensions) ? ['PHP_EXTENSIONS' => implode(' ', $phpExtensions)] : []
            )),
            'env_file' => [
                './.docker/config.env',
            ],
        ];

        if (static::CRON_ENABLED) {
            $services['cron'] = $this->getCronCliService($phpVersion, true, $cliDepends, 'cron.magento2.docker');
        }

        $volumeConfig = [];

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
                        './.docker/mnt:/mnt',
                        './.docker/tmp:/tmp'
                    ]
                )
            ]
        );

        return $config;
    }

    /**
     * @return string
     */
    public function getPath(): string
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
            'magento-var:' . self::DIR_MAGENTO . '/var:delegated',
            'magento-etc:' . self::DIR_MAGENTO . '/app/etc:delegated',
            'magento-static:' . self::DIR_MAGENTO . '/pub/static:delegated',
            'magento-media:' . self::DIR_MAGENTO . '/pub/media:delegated',
        ];
    }

    /**
     * @param bool $isReadOnly
     * @return array
     */
    protected function getMagentoBuildVolumes(bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        return [
            'magento:' . self::DIR_MAGENTO . $flag,
            'magento-vendor:' . self::DIR_MAGENTO . '/vendor' . $flag,
            'magento-generated:' . self::DIR_MAGENTO . '/generated' . $flag,
            'magento-setup:' . self::DIR_MAGENTO . '/setup' . $flag,
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
            $composeCacheDirectory . ':/root/.composer/cache:delegated',
        ];
    }

    /**
     * @return array
     */
    protected function getVariables(): array
    {
        return [
            'PHP_MEMORY_LIMIT' => '2048M',
            'UPLOAD_MAX_FILESIZE' => '64M',
            'MAGENTO_ROOT' => self::DIR_MAGENTO,
            # Name of your server in IDE
            'PHP_IDE_CONFIG' => 'serverName=magento_cloud_docker',
            # Docker host for developer environments, can be different for your OS
            'XDEBUG_CONFIG' => 'remote_host=host.docker.internal',
        ];
    }

    /**
     * @param string $serviceName
     * @return string|null
     * @throws ConfigurationMismatchException
     */
    protected function getServiceVersion(string $serviceName)
    {
        return $this->config->getServiceVersion($serviceName);
    }

    /**
     * @return string
     * @throws ConfigurationMismatchException
     */
    protected function getPhpVersion()
    {
        return $this->config->getPhpVersion();
    }

    /**
     * @param string $phpVersion
     * @return array
     * @throws ConfigurationMismatchException
     */
    protected function getPhpExtensions(string $phpVersion): array
    {
        $phpConstraint = new Constraint('==', $this->versionParser->normalize($phpVersion));
        $phpExtensions = array_diff(
            array_merge(self::DEFAULT_PHP_EXTENSIONS, $this->config->getEnabledPhpExtensions()),
            $this->config->getDisabledPhpExtensions(),
            self::IGNORED_EXTENSIONS
        );
        $messages = [];
        $result = [];
        foreach ($phpExtensions as $phpExtName) {
            if (isset(self::BUILTIN_EXTENSIONS[$phpExtName])) {
                $phpExtConstraint = $this->versionParser->parseConstraints(self::BUILTIN_EXTENSIONS[$phpExtName]);
                if ($phpConstraint->matches($phpExtConstraint)) {
                    continue;
                }
            }
            if (isset(self::AVAILABLE_PHP_EXTENSIONS[$phpExtName])) {
                $phpExtConstraintAvailable = $this->versionParser->parseConstraints(
                    self::AVAILABLE_PHP_EXTENSIONS[$phpExtName]
                );
                if ($phpConstraint->matches($phpExtConstraintAvailable)) {
                    $result[] = $phpExtName;
                    continue;
                }
                $messages[] = "PHP extension $phpExtName is not available for PHP version $phpVersion";
            }
            $messages[] = "PHP extension $phpExtName is not supported";
        }
        if (!empty($messages)) {
            throw new ConfigurationMismatchException(implode(PHP_EOL, $messages));
        }
        return $result;
    }
}
