<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Environment;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @inheritdoc
 */
class Reader implements ReaderInterface
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

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
     * @param DirectoryList $directoryList
     * @param Environment $environment
     * @param ConfigFileList $configFileList
     * @param File $file
     */
    public function __construct(
        DirectoryList $directoryList,
        Environment $environment,
        ConfigFileList $configFileList,
        File $file
    ) {
        $this->directoryList = $directoryList;
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
                '%s/%s.yaml',
                $this->directoryList->getEnvConfig(),
                $this->environment->getBranchName()
            );

            $this->config = array_replace_recursive(
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
}
