<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

/**
 * Class contains the list of files for backup
 */
class BackupList
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * Suffix for backup files
     */
    const BACKUP_SUFFIX = '.bak';

    /**
     * @param FileList $fileList
     */
    public function __construct(FileList $fileList)
    {
        $this->fileList = $fileList;
    }

    /**
     * Returns a list of files for backup
     *
     * @return array
     */
    public function getList(): array
    {
        return [
            'app/etc/env.php' => $this->fileList->getEnv(),
            'app/etc/config.php' => $this->fileList->getConfig(),
        ];
    }
}
