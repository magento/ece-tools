<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\Environment\Reader as EnvironmentReader;
use Magento\MagentoCloud\Config\Build\Reader as BuildReader;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @inheritdoc
 */
class Build implements BuildInterface
{
    /**
     * @var EnvironmentReader
     */
    private $environmentReader;

    /**
     * @var BuildReader
     */
    private $buildReader;

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
     * @param BuildReader $buildReader
     * @param Schema $schema
     */
    public function __construct(
        EnvironmentReader $environmentReader,
        BuildReader $buildReader,
        Schema $schema
    ) {
        $this->environmentReader = $environmentReader;
        $this->buildReader = $buildReader;
        $this->schema = $schema;
    }

    /**
     * @inheritdoc
     */
    public function get(string $name)
    {
        if (!array_key_exists($name, $this->schema->getDefaults(StageConfigInterface::STAGE_BUILD))) {
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
     * @throws ParseException
     * @throws FileSystemException
     */
    private function mergeConfig(): array
    {
        if (null === $this->mergedConfig) {
            $envConfig = $this->environmentReader->read()[self::SECTION_STAGE] ?? [];

            $this->mergedConfig = array_replace(
                $this->schema->getDefaults(StageConfigInterface::STAGE_BUILD),
                $envConfig[self::STAGE_GLOBAL] ?? [],
                $envConfig[self::STAGE_BUILD] ?? [],
                $this->getDeprecatedConfig()
            );
        }

        return $this->mergedConfig;
    }

    /**
     * Resolves configuration from deprecated build configuration file build_options.ini
     *
     * @return array
     */
    private function getDeprecatedConfig(): array
    {
        $buildConfig = $this->buildReader->read();
        $result = [];

        if (isset($buildConfig['scd_strategy'])) {
            $result[self::VAR_SCD_STRATEGY] = $buildConfig['scd_strategy'];
        }

        if (isset($buildConfig['exclude_themes'])) {
            $result[self::VAR_SCD_EXCLUDE_THEMES] = $buildConfig['exclude_themes'];
        }

        if (isset($buildConfig['SCD_COMPRESSION_LEVEL'])) {
            $result[self::VAR_SCD_COMPRESSION_LEVEL] = (int)$buildConfig['SCD_COMPRESSION_LEVEL'];
        }

        if (isset($buildConfig['scd_threads'])) {
            $result[self::VAR_SCD_THREADS] = (int)$buildConfig['scd_threads'];
        }

        if (isset($buildConfig['skip_scd'])) {
            $result[self::VAR_SKIP_SCD] = $buildConfig['skip_scd'] === '1';
        }

        if (isset($buildConfig['VERBOSE_COMMANDS'])) {
            $result[self::VAR_VERBOSE_COMMANDS] = $buildConfig['VERBOSE_COMMANDS'] === 'enabled' ? '-vv' : '';
        }

        return $result;
    }
}
