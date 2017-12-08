<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Backup;

use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * Class returns the list of files that are in backup
 */
class FileList
{
    /**
     * @var BackupList
     */
    private $backupList;

    /**
     * @var File
     */
    private $file;

    /**
     * @param BackupList $backupList
     * @param File $file
     */
    public function __construct(
        BackupList $backupList,
        File $file
    ) {
        $this->backupList = $backupList;
        $this->file = $file;
    }

    /**
     * Returns the list of alias path of existed backup files
     *
     * @return array
     */
    public function get(): array
    {
        $resultList = [];
        foreach ($this->backupList->getList() as $aliasPath => $filePath) {
            $backupPath = $filePath . BackupList::BACKUP_SUFFIX;
            if ($this->file->isExists($backupPath)) {
                $resultList[] = $aliasPath;
            }
        }

        return $resultList;
    }
}
