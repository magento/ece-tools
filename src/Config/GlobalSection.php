<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Environment\ReaderInterface as EnvironmentReader;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @inheritdoc
 */
class GlobalSection implements StageConfigInterface
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
        try {
            $defaults = $this->schema->getDefaults(StageConfigInterface::STAGE_GLOBAL);
            if (!array_key_exists($name, $defaults)) {
                throw new ConfigException(
                    sprintf('Config %s was not defined.', $name),
                    Error::GLOBAL_CONFIG_NOT_DEFINED
                );
            }

            return $this->mergeConfig($defaults)[$name];
        } catch (FileSystemException $e) {
            throw new ConfigException($e->getMessage(), Error::GLOBAL_CONFIG_UNABLE_TO_READ_SCHEMA_YAML, $e);
        }
    }

    /**
     * @param array $defaults
     * @return array
     * @throws ConfigException
     */
    private function mergeConfig(array $defaults): array
    {
        try {
            if (null === $this->mergedConfig) {
                $envConfig = $this->environmentReader->read()[self::SECTION_STAGE] ?? [];

                $this->mergedConfig = array_replace(
                    $defaults,
                    $envConfig[self::STAGE_GLOBAL] ?? []
                );
            }

            return $this->mergedConfig;
        } catch (ParseException $e) {
            throw new ConfigException($e->getMessage(), Error::GLOBAL_CONFIG_PARSE_FAILED, $e);
        } catch (FileSystemException $e) {
            throw new ConfigException($e->getMessage(), Error::GLOBAL_CONFIG_UNABLE_TO_READ, $e);
        }
    }
}
