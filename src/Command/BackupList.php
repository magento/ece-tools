<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Command\Backup\FileList as BackupFilesList;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * CLI command for showing the list of backup files.
 *
 * @api
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

    public const NAME = 'backup:list';

    /**
     * @param BackupFilesList $backupFilesList
     * @param LoggerInterface $logger
     */
    public function __construct(
        BackupFilesList $backupFilesList,
        LoggerInterface $logger
    ) {
        $this->backupFilesList = $backupFilesList;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Shows the list of backup files.');

        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln('<comment>The list of backup files:</comment>');
            $output->writeln($this->backupFilesList->get() ?: 'There are no files in the backup');
        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }

        return Cli::SUCCESS;
    }
}
