<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Stage\Deploy;

use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Environment\ReaderInterface as EnvironmentReader;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\StageConfigInterface;

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
            if (null === $this->mergedConfig) {
                $envConfig = $this->environmentReader->read()[DeployInterface::SECTION_STAGE] ?? [];

                $this->mergedConfig = array_replace(
                    $this->schema->getDefaults(StageConfigInterface::STAGE_DEPLOY),
                    $envConfig[DeployInterface::STAGE_GLOBAL] ?? [],
                    $envConfig[DeployInterface::STAGE_DEPLOY] ?? [],
                    $this->environmentConfig->getAll()
                );
            }

            return $this->mergedConfig;
        } catch (\Exception $exception) {
            throw new ConfigException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }
}
