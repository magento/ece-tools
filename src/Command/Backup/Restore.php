<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Backup;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Filesystem\BackupList;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * Class for restoring Magento files from backup
 *
 * @see \Magento\MagentoCloud\Filesystem\BackupList contains the list of files for restoring
 */
class Restore
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
     * Restores Magento files from backup
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $specificPath = $input->getOption('file');
        $backupList = $this->backupList->getList();

        if ($specificPath) {
            if (empty($backupList[$specificPath])) {
                $output->writeln(sprintf('<error>There is no %s file in the backup list.</error>'
                    . ' <comment>Run backup:list to show files from backup list.</comment>', $specificPath));
                return;
            }
            $this->restore($input, $output, $backupList[$specificPath], $specificPath);
        } else {
            foreach ($backupList as $aliasPath => $filePath) {
                $this->restore($input, $output, $filePath, $aliasPath);
            }
        }
    }

    /**
     * Restores a file
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $aliasPath
     * @param string $filePath
     * @return void
     */
    private function restore(InputInterface $input, OutputInterface $output, string $filePath, string $aliasPath)
    {
        $backupPath = $filePath . BackupList::BACKUP_SUFFIX;
        if (!$this->file->isExists($backupPath)) {
            $output->writeln(sprintf('<info>Backup for %s does not exist.</info>'
                . ' <comment>Skipped.</comment>', $aliasPath));
            return;
        }

        if ($this->file->isExists($filePath) && !$input->getOption('force')) {
            $output->writeln(sprintf('<info>%s file exists!</info>'
                . ' <comment>If you want to rewrite existed files use --force</comment>', $aliasPath));
            return;
        }

        $this->file->copy($backupPath, $filePath);
        $output->writeln(sprintf('<info>Backup file %s was restored.</info>', $aliasPath));
    }
}
