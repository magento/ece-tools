<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Config\Environment;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\SystemList;

/**
 * Reader of config.php and config.php.dist files.
 */
class Reader
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;

    /**
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file
    ) {
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function read(): array
    {
        $sourcePath = $this->directoryList->getDockerRoot() . '/config.php';

        if (!$this->file->isExists($sourcePath)) {
            $sourcePath .= '.dist';
        }

        if ($this->file->isExists($sourcePath)) {
            return $this->file->requireFile($sourcePath);
        }

        throw new ConfigurationMismatchException(sprintf(
            'Source file %s does not exists',
            $sourcePath
        ));
    }
}
