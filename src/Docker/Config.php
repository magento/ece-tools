<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker;

use Magento\MagentoCloud\Docker\Config\Reader;
use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Processed configuration.
 */
class Config
{
    /**
     * @var Reader
     */
    private $reader;

    const KEY_REDIS = 'redis';
    const KEY_DB = 'mysql';
    const KEY_ELASTICSEARCH = 'elasticsearch';
    const KEY_RABBIT_MQ = 'rabbitmq';

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
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

    /**
     * @param string $service
     * @return string|null
     * @throws ConfigurationMismatchException
     */
    public function getServiceVersion(string $service)
    {
        try {
            return $this->reader->read()['services'][$service]['version'] ?? null;
        } catch (FileSystemException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
