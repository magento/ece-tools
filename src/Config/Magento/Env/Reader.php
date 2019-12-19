<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Magento\Env;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * Reads configuration from /app/etc/env.php file
 *
 * {@inheritdoc}
 */
class Reader implements ReaderInterface
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
     * @param File $file
     * @param FileList $fileList
     */
    public function __construct(File $file, FileList $fileList)
    {
        $this->file = $file;
        $this->fileList = $fileList;
    }

    /**
     * @return array
     */
    public function read(): array
    {
        $configPath = $this->fileList->getEnv();
        if (!$this->file->isExists($configPath)) {
            return [];
        }

        return require $configPath;
    }
}
