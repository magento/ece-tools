<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Dev\UpdateComposer;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * Generates script for clearing module requirements that run after composer install.
 *
 * This requires for avoiding requirement conflicts for not released magento version.
 */
class ClearModuleRequirements
{
    const SCRIPT_PATH = 'clear_module_requirements.php';

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

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
     * Generates script for clearing module requirements that run after composer install.
     *
     * @return string script name
     *
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function generate()
    {
        $rootDirectory = $this->directoryList->getMagentoRoot();
        $this->file->copy(__DIR__  . '/' . self::SCRIPT_PATH . '.tpl', $rootDirectory . '/' . self::SCRIPT_PATH);
        $gitIgnore = $this->file->fileGetContents($rootDirectory . '/.gitignore');
        if (strpos($gitIgnore ?? '', self::SCRIPT_PATH) === false) {
            $this->file->filePutContents(
                $rootDirectory . '/.gitignore',
                '!/' . self::SCRIPT_PATH . PHP_EOL,
                FILE_APPEND
            );
        }
        return self::SCRIPT_PATH;
    }
}
