<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Creates backup Magento files
 * @see \Magento\MagentoCloud\Filesystem\BackupList contains the list of files for backup
 */
class Backup implements ProcessInterface
{
    /**
     * @var BackupList
     */
    private $backupList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @param BackupList $backupList
     * @param File $file
     * @param LoggerInterface $logger
     */
    public function __construct(
        BackupList $backupList,
        File $file,
        LoggerInterface $logger
    ) {
        $this->backupList = $backupList;
        $this->file = $file;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Create backup of important files.');

        foreach ($this->backupList->getList() as $file) {
            if (!$this->file->isExists($file)) {
                $this->logger->notice(sprintf('File %s does not exist. Skipped.', $file));
                continue;
            }

            $backup = $file . BackupList::BACKUP_SUFFIX;
            $this->file->copy($file, $backup);
            $this->logger->info(sprintf('Backup %s for %s was created.', $backup, $file));
        }
    }
}
