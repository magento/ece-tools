<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker\Compose;

use Magento\MagentoCloud\Service\Service;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Illuminate\Contracts\Config\Repository;

/**
 * Docker functional test builder.
 *
 * @codeCoverageIgnore
 */
class FunctionalCompose extends ProductionCompose
{
    const DIR_MAGENTO = '/var/www/magento';
    const CRON_ENABLED = false;

    /**
     * @inheritDoc
     */
    public function build(Repository $config): array
    {
        $compose = parent::build($config);
        $compose['services']['generic']['env_file'] = [
            './.docker/composer.env'
        ];
        $compose['services']['db']['ports'] = ['3306:3306'];
        $compose['volumes']['magento'] = [];

        return $compose;
    }

    /**
     * @param bool $isReadOnly
     * @return array
     */
    protected function getMagentoVolumes(bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        return [
            '.:/var/www/ece-tools',
            'magento:' . self::DIR_MAGENTO . $flag,
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
            '.:/var/www/ece-tools',
            'magento:' . self::DIR_MAGENTO . $flag,
        ];
    }

    /**
     * @return array
     */
    protected function getVariables(): array
    {
        return [
            'MAGENTO_RUN_MODE' => 'production',
            'PHP_MEMORY_LIMIT' => '2048M',
            'DEBUG' => 'false',
            'ENABLE_SENDMAIL' => 'false',
            'UPLOAD_MAX_FILESIZE' => '64M',
            'MAGENTO_ROOT' => self::DIR_MAGENTO,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getServiceVersion(string $serviceName)
    {
        $mapDefaultVersion = [
            Service::NAME_DB => '10.2',
            Service::NAME_PHP => '7.2',
            Service::NAME_NGINX => self::DEFAULT_NGINX_VERSION,
            Service::NAME_VARNISH => self::DEFAULT_VARNISH_VERSION,
            Service::NAME_ELASTICSEARCH => null,
            Service::NAME_NODE => null,
            Service::NAME_RABBITMQ => null,
            Service::NAME_REDIS => null,
        ];

        if (!array_key_exists($serviceName, $mapDefaultVersion)) {
            throw new ConfigurationMismatchException(sprintf('Type "%s" is not supported', $serviceName));
        }

        return $mapDefaultVersion[$serviceName];
    }

    /**
     * @inheritdoc
     */
    protected function getPhpVersion()
    {
        return $this->getServiceVersion(Service::NAME_PHP);
    }

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return $this->fileList->getToolsDockerCompose();
    }

    /**
     * @param string $phpVersion
     * @return array
     */
    protected function getPhpExtensions(string $phpVersion): array
    {
        return array_unique(array_merge(
            PhpExtension::DEFAULT_PHP_EXTENSIONS,
            ['xsl', 'redis'],
            in_array($phpVersion, ['7.0', '7.1']) ? ['mcrypt'] : []
        ));
    }
}
