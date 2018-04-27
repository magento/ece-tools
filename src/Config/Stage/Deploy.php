<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\Deploy\EnvironmentConfig;
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
     * @param EnvironmentReader $environmentReader
     * @param EnvironmentConfig $environmentConfig
     */
    public function __construct(
        EnvironmentReader $environmentReader,
        EnvironmentConfig $environmentConfig
    ) {
        $this->environmentReader = $environmentReader;
        $this->environmentConfig = $environmentConfig;
    }

    /**
     * @inheritdoc
     */
    public function get(string $name)
    {
        if (!array_key_exists($name, $this->getDefault())) {
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
                $this->getDefault(),
                $envConfig[self::STAGE_GLOBAL] ?? [],
                $envConfig[self::STAGE_DEPLOY] ?? [],
                $this->environmentConfig->getAll()
            );
        }

        return $this->mergedConfig;
    }

    /**
     * Resolves default configuration value if other was not provided.
     *
     * @return array
     */
    private function getDefault(): array
    {
        return [
            self::VAR_SCD_STRATEGY => '',
            self::VAR_SCD_COMPRESSION_LEVEL => 4,
            self::VAR_SEARCH_CONFIGURATION => [],
            self::VAR_QUEUE_CONFIGURATION => [],
            self::VAR_CACHE_CONFIGURATION => [],
            self::VAR_SESSION_CONFIGURATION => [],
            self::VAR_VERBOSE_COMMANDS => '',
            self::VAR_CRON_CONSUMERS_RUNNER => [],
            self::VAR_CLEAN_STATIC_FILES => true,
            self::VAR_STATIC_CONTENT_SYMLINK => true,
            self::VAR_UPDATE_URLS => true,
            self::VAR_SKIP_SCD => false,
            self::VAR_SCD_THREADS => $this->getDefaultScdThreads(),
            self::VAR_GENERATED_CODE_SYMLINK => true,
            self::VAR_SCD_EXCLUDE_THEMES => '',
            self::VAR_REDIS_USE_SLAVE_CONNECTION => false,
            self::VAR_MYSQL_USE_SLAVE_CONNECTION => false,
        ];
    }

    /**
     * Retrieves default scd threads value.
     * 3 if production environment otherwise 1
     *
     * @return int
     */
    private function getDefaultScdThreads()
    {
        if (isset($_ENV['MAGENTO_CLOUD_MODE'])
            && $_ENV['MAGENTO_CLOUD_MODE'] === Environment::CLOUD_MODE_ENTERPRISE
        ) {
            return 3;
        }

        return 1;
    }
}
