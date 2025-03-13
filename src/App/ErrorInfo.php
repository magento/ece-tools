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
            $this->errors = Yaml::parse(
                $this->file->fileGetContents($this->fileList->getErrorSchema()),
                Yaml::PARSE_CONSTANT
            );
        }
    }
}
