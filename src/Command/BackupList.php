<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Command\Backup\FileList as BackupFilesList;
use Psr\Log\LoggerInterface;

/**
 * CLI command for showing the list of backup files.
 */
class BackupList extends Command
{
    /**
     * @var BackupFilesList
     */
    private $backupFilesList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Wrapper
     */
    private $wrapper;

    /**
     * Command name
     */
    const NAME = 'backup:list';

    /**
     * @param BackupFilesList $backupFilesList
     * @param LoggerInterface $logger
     * @param Wrapper $wrapper
     */
    public function __construct(
        BackupFilesList $backupFilesList,
        LoggerInterface $logger,
        Wrapper $wrapper
    ) {
        $this->backupFilesList = $backupFilesList;
        $this->logger = $logger;
        $this->wrapper = $wrapper;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Shows the list of backup files');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->wrapper->execute(function () use ($output) {
            $output->writeln('<comment>The list of backup files:</comment>');
            $output->writeln($this->backupFilesList->get() ?: 'There are no files in the backup');
        }, $output);
    }
}
