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
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Tag\TaggedValue;

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
     * @param File $file
     * @param FileList $fileList
     */
    public function __construct(File $file, FileList $fileList)
    {
        $this->file = $file;
        $this->fileList = $fileList;
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

            $this->errors = $this->normalizeYamlData($this->errors) ?? [];
        }
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
