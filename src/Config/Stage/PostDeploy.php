<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * @inheritdoc
 */
class PostDeploy implements PostDeployInterface
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
        if (!array_key_exists($name, $this->schema->getDefaults(StageConfigInterface::STAGE_POST_DEPLOY))) {
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
            $envConfig = $this->environmentReader->read()[self::SECTION_STAGE] ?? [];

            $this->mergedConfig = array_replace(
                $this->schema->getDefaults(StageConfigInterface::STAGE_POST_DEPLOY),
                $envConfig[self::STAGE_GLOBAL] ?? [],
                $envConfig[self::STAGE_POST_DEPLOY] ?? []
            );
        }

        return $this->mergedConfig;
    }
}
