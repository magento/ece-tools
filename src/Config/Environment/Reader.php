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
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Tag\TaggedValue;
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
     * @param ConfigFileList $configFileList
     * @param File $file
     */
    public function __construct(ConfigFileList $configFileList, File $file)
    {
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
                $this->config = (array)Yaml::parse($this->file->fileGetContents($path), $parseFlag);
                $this->config = $this->normalizeYamlData($this->config);
            }
        }

        return $this->config;
    }

    /**
     * Recursively unwrap Symfony YAML TaggedValue objects and handle common tags.
     *
     * This method handles !env, !include, !php/const, and unknown tags,
     * ensuring all YAML values are normalized to arrays or scalars for safe merging.
     *
     * @param mixed $data
     * @return mixed
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity") Method is intentionally complex due to tag handling.
     */
    private function normalizeYamlData(mixed $data): mixed
    {
        if ($data instanceof TaggedValue) {
            $tag = $data->getTag();
            $value = $data->getValue();

            switch ($tag) {
                case '!env':
                    $envValue = getenv((string)$value);
                    return $envValue !== false ? $envValue : null;

                case '!include':
                    if (file_exists((string)$value)) {
                        $included = Yaml::parseFile((string)$value);
                        return $this->normalizeYamlData($included);
                    }
                    return null;

                case '!php/const':
                    // Evaluate the PHP constant
                    return defined($value) ? constant($value) : null;

                default:
                    $val = $this->normalizeYamlData($value);
                    return is_array($val) ? $val : [$val];
            }
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->normalizeYamlData($value);
            }
            return $data;
        }

        return $data;
    }
}
