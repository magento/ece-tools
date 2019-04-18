<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Service;

use Magento\MagentoCloud\Docker\Config\Reader;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Docker\BuilderInterface;

/**
 * Retrieve Service versions from Cloud configuration.
 */
class Version
{
    private $configurableVersions = [
        BuilderInterface::PHP_VERSION,// => '~7.1.3 || ~7.2.0',
        BuilderInterface::NGINX_VERSION,// => '^1.0',
        BuilderInterface::DB_VERSION,// => '>=10.0 <10.3',
        BuilderInterface::REDIS_VERSION,// => '~3.2 || ~4.0 || ~5.0',
        //BuilderInterface::SERVICE_VARNISH,// => '~4.0 || ~5.0',
        BuilderInterface::ES_VERSION,// => '^2.0 || ^5.0 || ^6.0',
        BuilderInterface::RABBIT_MQ_VERSION,// => '~3.7',
    ];

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param Repository $customVersions
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getVersions(Repository $customVersions)
    {
        $configuredVersions = [];
        foreach ($this->configurableVersions as $serviceName)
        {
            $version = $customVersions->get($serviceName) ?: $this->getServiceVersionFromConfig($serviceName);
            if ($version) {
                $configuredVersions[$serviceName] = $version;
            }

        }
        return $configuredVersions;
    }

    /**
     * @param string $serviceName
     * @return string|null
     * @throws ConfigurationMismatchException
     */
    public function getServiceVersionFromConfig(string $serviceName)
    {
        try {
            return $this->reader->read()['services'][$serviceName]['version'] ?? null;
        } catch (FileSystemException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @return string
     * @throws ConfigurationMismatchException
     */
    public function getPhpVersion(): string
    {
        try {
            $config = $this->reader->read();
            list($type, $version) = explode(':', $config['type']);
        } catch (FileSystemException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($type !== 'php') {
            throw new ConfigurationMismatchException(sprintf(
                'Type "%s" is not supported',
                $type
            ));
        }

        /**
         * We don't support release candidates.
         */
        return rtrim($version, '-rc');
    }
}
