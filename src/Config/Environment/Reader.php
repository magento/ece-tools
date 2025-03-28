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
                $parseFlag = defined(Yaml::class . '::PARSE_CONSTANT') ? Yaml::PARSE_CONSTANT : 0;
                $this->config = (array)Yaml::parse($this->file->fileGetContents($path), $parseFlag);
            }
        }

        return $this->config;
    }
}
