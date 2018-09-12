<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Environment;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\SystemList;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @inheritdoc
 */
class Reader implements ReaderInterface
{
    /**
     * @string
     */
    const DIR_ENV_CONFIG = '.magento.env';

    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ConfigFileList
     */
    private $configFileList;

    /**
     * @var File
     */
    private $file;

    /**
     * Cached configuration
     *
     * @var array|null
     */
    private $config;

    /**
     * @param SystemList $systemList
     * @param Environment $environment
     * @param ConfigFileList $configFileList
     * @param File $file
     */
    public function __construct(
        SystemList $systemList,
        Environment $environment,
        ConfigFileList $configFileList,
        File $file
    ) {
        $this->systemList = $systemList;
        $this->environment = $environment;
        $this->configFileList = $configFileList;
        $this->file = $file;
    }

    /**
     * @return array
     * @throws ParseException
     * @throws FileSystemException
     */
    public function read(): array
    {
        if ($this->config === null) {
            $mainConfigPath = $this->configFileList->getEnvConfig();

            $branchConfigPath = sprintf(
                '%s/%s/%s.yaml',
                $this->systemList->getMagentoRoot(),
                self::DIR_ENV_CONFIG,
                $this->environment->getBranchName()
            );

            $this->config = $this->mergeConfigs(
                $this->parseConfig($mainConfigPath),
                $this->parseConfig($branchConfigPath)
            );
        }

        return $this->config;
    }

    /**
     * Returns parsed yaml config from file.
     * Returns an empty array if file doesn't exists
     *
     * @param string $path
     * @return array
     * @throws FileSystemException
     */
    private function parseConfig(string $path): array
    {
        return !$this->file->isExists($path) ?
            [] : (array)Yaml::parse($this->file->fileGetContents($path));
    }

    /**
     * Merges configuration from $branchConfig into $mainConfig by each stage or by each logger configuration.
     *
     * Separately merges each stage and logger configuration to avoid cases when the variable has an array value
     * and it should be replaced instead of recursive merging.
     *
     * @param array $mainConfig
     * @param array $branchConfig
     * @return array
     */
    private function mergeConfigs(array $mainConfig, array $branchConfig): array
    {
        $newConfig = $mainConfig;

        foreach ($branchConfig as $sectionName => $sectionConfig) {
            foreach ($sectionConfig as $stageName => $stageConfig) {
                if (isset($newConfig[$sectionName][$stageName])) {
                    $newConfig[$sectionName][$stageName] = array_merge(
                        $newConfig[$sectionName][$stageName],
                        $stageConfig
                    );
                } else {
                    $newConfig[$sectionName][$stageName] = $stageConfig;
                }
            }
        }

        return $newConfig;
    }
}
