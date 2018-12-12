<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\System;

use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\SystemConfigInterface;

/**
 * @inheritdoc
 */
class Variables implements SystemConfigInterface
{
    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var array
     */
    private $mergedConfig;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @param EnvironmentReader $environmentReader
     * @param Schema $schema
     */
    public function __construct(EnvironmentReader $environmentReader, Schema $schema)
    {
        $this->environmentReader = $environmentReader;
        $this->schema = $schema;
    }

    /**
     * @inheritdoc
     */
    public function get(string $name)
    {
        if (!array_key_exists($name, $this->schema->getDefaults(SystemConfigInterface::SYSTEM_VARIABLES))) {
            throw new \RuntimeException(sprintf(
                'Config %s was not defined.',
                $name
            ));
        }

        try {
            return $this->mergeConfig()[$name];
        } catch (\Exception $exception) {
            throw new \RuntimeException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @return array
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    private function mergeConfig(): array
    {
        if (null === $this->mergedConfig) {
            $envConfig = $this->environmentReader->read()[self::SECTION_SYSTEM] ?? [];

            $this->mergedConfig = array_replace(
                $this->schema->getDefaults(SystemConfigInterface::SYSTEM_VARIABLES),
                $envConfig[self::SYSTEM_VARIABLES] ?? []
            );
        }

        return $this->mergedConfig;
    }
}
