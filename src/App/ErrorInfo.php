<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Symfony\Component\Yaml\Yaml;

class ErrorInfo
{
    /**
     * @var File
     */
    private $file;
    /**
     * @var FileList
     */
    private $fileList;

    /**
     *
     * @param File $file
     * @param FileList $fileList
     */
    public function __construct(File $file, FileList $fileList)
    {
        $this->file = $file;
        $this->fileList = $fileList;
    }

    /**
     * @param int $errorCode
     * @return array
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function get(int $errorCode): array
    {
        $errors = Yaml::parse(
            $this->file->fileGetContents($this->fileList->getErrorSchema()),
            Yaml::PARSE_CONSTANT
        );

        return $errors[$errorCode] ?? [];
    }
}
