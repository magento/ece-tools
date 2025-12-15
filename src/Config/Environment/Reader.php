<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Environment;

use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\YamlNormalizer;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Reads configuration from .magento.env.yaml configuration file.
 */
class Reader implements ReaderInterface
{
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
     * @var YamlNormalizer
     */
    private YamlNormalizer $yamlNormalizer;

    /**
     * Reader constructor
     *
     * @param ConfigFileList $configFileList
     * @param File $file
     * @param YamlNormalizer $yamlNormalizer
     */
    public function __construct(
        ConfigFileList $configFileList,
        File $file,
        YamlNormalizer $yamlNormalizer
    ) {
        $this->configFileList = $configFileList;
        $this->file           = $file;
        $this->yamlNormalizer = $yamlNormalizer;
    }

    /**
     * Reads configuration from .magento.env.yaml file.
     *
     * @return array
     * @throws ParseException
     * @throws FileSystemException
     */
    public function read(): array
    {
        if ($this->config === null) {
            $path = $this->configFileList->getEnvConfig();

            if (!$this->file->isExists($path)) {
                $this->config = [];
            } else {
                $parseFlag = 0;
                if (defined(Yaml::class . '::PARSE_CONSTANT')) {
                    $parseFlag |= Yaml::PARSE_CONSTANT;
                }
                if (defined(Yaml::class . '::PARSE_CUSTOM_TAGS')) {
                    $parseFlag |= Yaml::PARSE_CUSTOM_TAGS;
                }
                $this->config = (array) Yaml::parse($this->file->fileGetContents($path), $parseFlag);
                $this->config = $this->yamlNormalizer->normalize($this->config);
            }
        }

        return $this->config;
    }
}
