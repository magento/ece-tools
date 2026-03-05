<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Util\YamlNormalizer;
use Symfony\Component\Yaml\Yaml;

/**
 * Returns info about errors from ./config/schema.error.yaml file
 */
class ErrorInfo
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var YamlNormalizer
     */
    private $yamlNormalizer;

    /**
     * ErrorInfo constructor
     *
     * @param File $file
     * @param FileList $fileList
     * @param YamlNormalizer $yamlNormalizer
     */
    public function __construct(
        File $file,
        FileList $fileList,
        YamlNormalizer $yamlNormalizer
    ) {
        $this->file           = $file;
        $this->fileList       = $fileList;
        $this->yamlNormalizer = $yamlNormalizer;
    }

    /**
     * Returns info about error based on passed error code
     *
     * @param int $errorCode
     * @return array
     * @throws FileSystemException
     */
    public function get(int $errorCode): array
    {
        $this->loadErrors();

        return $this->errors[$errorCode] ?? [];
    }

    /**
     * Fetches all errors from schema.error.yaml file and caches them
     *
     * @throws FileSystemException
     */
    private function loadErrors(): void
    {
        if (empty($this->errors)) {
            $parseFlags = 0;
            if (defined(Yaml::class . '::PARSE_CONSTANT')) {
                $parseFlags |= Yaml::PARSE_CONSTANT;
            }
            if (defined(Yaml::class . '::PARSE_CUSTOM_TAGS')) {
                $parseFlags |= Yaml::PARSE_CUSTOM_TAGS;
            }

            $this->errors = (array) Yaml::parse(
                $this->file->fileGetContents($this->fileList->getErrorSchema()),
                $parseFlags
            );

            $this->errors = $this->yamlNormalizer->normalize($this->errors) ?? [];
        }
    }
}
