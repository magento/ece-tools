<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Stage\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Environment\ReaderInterface as EnvironmentReader;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Returns all merged configuration for deploy phase
 */
class MergedConfig
{
    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var EnvironmentConfig
     */
    private $environmentConfig;

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
     * @param EnvironmentConfig $environmentConfig
     * @param Schema $schema
     */
    public function __construct(
        EnvironmentReader $environmentReader,
        EnvironmentConfig $environmentConfig,
        Schema $schema
    ) {
        $this->environmentReader = $environmentReader;
        $this->environmentConfig = $environmentConfig;
        $this->schema = $schema;
    }

    /**
     * Returns all merged configuration for deploy stage.
     *
     * @return array
     * @throws ConfigException If the configuration file can't be read or can't be parsed
     */
    public function get(): array
    {
        try {
            $defaults = $this->schema->getDefaults(StageConfigInterface::STAGE_DEPLOY);

            return $this->mergedConfig($defaults);
        } catch (FileSystemException $e) {
            throw new ConfigException($e->getMessage(), Error::DEPLOY_CONFIG_UNABLE_TO_READ_SCHEMA_YAML, $e);
        }
    }

    /**
     * @param array $defaults
     * @return array
     * @throws ConfigException
     */
    private function mergedConfig(array $defaults)
    {
        try {
            if (null === $this->mergedConfig) {
                $envConfig = $this->environmentReader->read()[DeployInterface::SECTION_STAGE] ?? [];

                $this->mergedConfig = array_replace(
                    $defaults,
                    $envConfig[DeployInterface::STAGE_GLOBAL] ?? [],
                    $envConfig[DeployInterface::STAGE_DEPLOY] ?? [],
                    $this->environmentConfig->getAll()
                );
            }

            return $this->mergedConfig;
        } catch (FileSystemException $e) {
            throw new ConfigException($e->getMessage(), Error::DEPLOY_CONFIG_UNABLE_TO_READ, $e);
        } catch (ParseException $e) {
            throw new ConfigException($e->getMessage(), Error::DEPLOY_CONFIG_PARSE_FAILED, $e);
        }
    }
}
