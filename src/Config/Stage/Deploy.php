<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Stage\Deploy\EnvironmentConfig;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @inheritdoc
 */
class Deploy implements DeployInterface
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
     * @var Environment
     */
    private $environment;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @param Environment $environment
     * @param EnvironmentReader $environmentReader
     * @param EnvironmentConfig $environmentConfig
     * @param Schema $schema
     */
    public function __construct(
        Environment $environment,
        EnvironmentReader $environmentReader,
        EnvironmentConfig $environmentConfig,
        Schema $schema
    ) {
        $this->environment = $environment;
        $this->environmentReader = $environmentReader;
        $this->environmentConfig = $environmentConfig;
        $this->schema = $schema;
    }

    /**
     * @inheritdoc
     */
    public function get(string $name)
    {
        if (!array_key_exists($name, $this->schema->getDefaults(StageConfigInterface::STAGE_DEPLOY))) {
            throw new \RuntimeException(sprintf(
                'Config %s was not defined.',
                $name
            ));
        }

        try {
            $value = $this->mergeConfig()[$name];

            if (!is_string($value)) {
                return $value;
            }

            /**
             * Trying to determine json object in string.
             */
            $decodedValue = json_decode($value, true);

            return $decodedValue !== null && json_last_error() === JSON_ERROR_NONE ? $decodedValue : $value;
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
     * @throws ParseException;
     * @throws FileSystemException;
     */
    private function mergeConfig(): array
    {
        if (null === $this->mergedConfig) {
            $envConfig = $this->environmentReader->read()[self::SECTION_STAGE] ?? [];

            $this->mergedConfig = array_replace(
                $this->schema->getDefaults(StageConfigInterface::STAGE_DEPLOY),
                $this->getDeployConfiguration(),
                $envConfig[self::STAGE_GLOBAL] ?? [],
                $envConfig[self::STAGE_DEPLOY] ?? [],
                $this->environmentConfig->getAll()
            );
        }

        return $this->mergedConfig;
    }

    /**
     * Resolves default configuration value for deploy stage.
     *
     * SCD_THREADS = 3 for production environment.
     *
     * @return array
     */
    private function getDeployConfiguration(): array
    {
        $config = [];

        if ($this->environment->getEnv('MAGENTO_CLOUD_MODE')  === Environment::CLOUD_MODE_ENTERPRISE) {
            $config[self::VAR_SCD_THREADS] = 3;
        }

        return $config;
    }
}
