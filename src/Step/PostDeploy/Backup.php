<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\PostDeploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Creates backup Magento files
 * @see \Magento\MagentoCloud\Filesystem\BackupList contains the list of files for backup
 */
class Backup implements StepInterface
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
        try {
            $this->logger->info('Create backup of important files.');

            foreach ($this->backupList->getList() as $file) {
                if (!$this->file->isExists($file)) {
                    $this->logger->notice(sprintf('File %s does not exist. Skipped.', $file));
                    continue;
                }

                $backup = $file . BackupList::BACKUP_SUFFIX;
                $result = $this->file->copy($file, $backup);
                if (!$result) {
                    $this->logger->warning(
                        sprintf('Backup %s for %s was not created.', $backup, $file),
                        ['errorCode' => Error::WARN_CREATE_CONFIG_BACKUP_FAILED]
                    );
                } else {
                    $this->logger->info(sprintf('Successfully created backup %s for %s .', $backup, $file));
                }
            }
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
