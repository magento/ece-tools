<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Environment as EnvironmentConfig;
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
                $this->getEnvironmentConfig()
            );
        }

        return $this->mergedConfig;
    }

    /**
     * Resolves environment values with and adds custom mappings.
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getEnvironmentConfig(): array
    {
        $variables = $this->environmentConfig->getVariables();

        if (isset($variables[self::VAR_VERBOSE_COMMANDS])
            && $variables[self::VAR_VERBOSE_COMMANDS] === EnvironmentConfig::VAL_ENABLED
        ) {
            $variables[self::VAR_VERBOSE_COMMANDS] = '-vvv';
        }

        $disabledFlow = [
            self::VAR_CLEAN_STATIC_FILES,
            self::VAR_STATIC_CONTENT_SYMLINK,
            self::VAR_UPDATE_URLS,
            self::VAR_GENERATED_CODE_SYMLINK,
        ];

        foreach ($disabledFlow as $disabledVar) {
            if (isset($variables[$disabledVar]) && $variables[$disabledVar] === EnvironmentConfig::VAL_DISABLED) {
                $variables[$disabledVar] = false;
            }
        }

        if (isset($variables['DO_DEPLOY_STATIC_CONTENT']) &&
            $variables['DO_DEPLOY_STATIC_CONTENT'] === EnvironmentConfig::VAL_DISABLED
        ) {
            $variables[self::VAR_SKIP_SCD] = true;
        }

        if ($scdThreads = $this->getScdThreads()) {
            $variables[self::VAR_SCD_THREADS] = $scdThreads;
        }

        if (isset($variables['STATIC_CONTENT_EXCLUDE_THEMES'])) {
            $variables[self::VAR_SCD_EXCLUDE_THEMES] = $variables['STATIC_CONTENT_EXCLUDE_THEMES'];
        }

        return $variables;
    }

    /**
     * Retrieves deploy threads.
     * By default it's 1, unless it is re-declared via environment variable.
     *
     * @return int
     */
    private function getScdThreads(): int
    {
        $variables = $this->environmentConfig->getVariables();
        $staticDeployThreads = 0;

        if (isset($variables['STATIC_CONTENT_THREADS'])) {
            $staticDeployThreads = (int)$variables['STATIC_CONTENT_THREADS'];
        } elseif ($envScThreads = $this->getEnvScdThreads()) {
            $staticDeployThreads = $envScThreads;
        }

        return $staticDeployThreads;
    }

    /**
     * Retrieves SCD threads configuration from raw environment data.
     *
     * @return int
     * @deprecated Environment variable STATIC_CONTENT_THREADS must be used instead
     */
    private function getEnvScdThreads(): int
    {
        $staticDeployThreads = 0;

        if (isset($_ENV['STATIC_CONTENT_THREADS'])) {
            $staticDeployThreads = (int)$_ENV['STATIC_CONTENT_THREADS'];
        } elseif (isset($_ENV['MAGENTO_CLOUD_MODE'])
            && $_ENV['MAGENTO_CLOUD_MODE'] === EnvironmentConfig::CLOUD_MODE_ENTERPRISE
        ) {
            $staticDeployThreads = 3;
        }

        return $staticDeployThreads;
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
            self::VAR_SCD_THREADS => 1,
            self::VAR_GENERATED_CODE_SYMLINK => true,
            self::VAR_SCD_EXCLUDE_THEMES => '',
            self::VAR_REDIS_USE_SLAVE_CONNECTION => false,
            self::VAR_MYSQL_USE_SLAVE_CONNECTION => false,
        ];
    }
}
