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

/**
 * Retrieve Service versions from Cloud configuration.
 */
class Version
{
    private $configurableVersions = [
        ServiceFactory::SERVICE_FPM,// => '~7.1.3 || ~7.2.0',
        ServiceFactory::SERVICE_NGINX,// => '^1.0',
        ServiceFactory::SERVICE_DB,// => '>=10.0 <10.3',
        ServiceFactory::SERVICE_REDIS,// => '~3.2 || ~4.0 || ~5.0',
        //ServiceFactory::SERVICE_VARNISH,// => '~4.0 || ~5.0',
        ServiceFactory::SERVICE_ELASTICSEARCH,// => '^2.0 || ^5.0 || ^6.0',
        ServiceFactory::SERVICE_RABBIT_MQ,// => '~3.7',
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
            $version = $serviceName == ServiceFactory::SERVICE_FPM
                ? $this->getPhpVersion()
                : $this->reader->read()['services'][$serviceName]['version'] ?? null;
            return $version;
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
