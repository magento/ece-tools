<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Checks if app/etc/env.php exists and restores it if not.
 */
class CheckEnvFile implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var State
     */
    private $state;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var File
     */
    private $file;

    /**
     * @param LoggerInterface $logger
     * @param FileList $fileList
     * @param File $file
     * @param Reader $reader
     * @param Writer $writer
     * @param State $state
     */
    public function __construct(
        LoggerInterface $logger,
        FileList $fileList,
        File $file,
        Reader $reader,
        Writer $writer,
        State $state
    ) {
        $this->logger = $logger;
        $this->reader = $reader;
        $this->writer = $writer;
        $this->state = $state;
        $this->fileList = $fileList;
        $this->file = $file;
    }

    /**
     * Checks if app/etc/env.php exists.
     * Writes to log magento installation date.
     * If file doesn't exist restores it from backup.
     * If backup doesn't exists creates new file.
     *
     * @inheritdoc
     */
    public function execute()
    {
        try {
            if (!$this->state->isInstalled()) {
                return;
            }

            $envFilePath = $this->fileList->getEnv();
            if ($this->file->isExists($envFilePath)) {
                $data = $this->reader->read();

                if (isset($data['install']['date'])) {
                    $this->logger->info('Magento was installed on ' . $data['install']['date']);
                } else {
                    $this->updateInstallDate();
                }

                return;
            }

            $this->logger->warning('Magento is installed but the environment configuration file doesn\'t exist.');

            $backupFilePatch = $envFilePath . BackupList::BACKUP_SUFFIX;
            if ($this->file->isExists($backupFilePatch)) {
                $this->logger->info('Restoring environment configuration file from the backup.');
                $this->file->copy($backupFilePatch, $envFilePath);
            } else {
                $this->logger->info('Generating new environment configuration file.');
                $this->updateInstallDate();
            }
        } catch (GenericException $e) {
            throw new ProcessException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Update installation date in the env.php file
     *
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    private function updateInstallDate()
    {
        $this->writer->update(['install' => ['date' => date('r')]]);
    }
}
