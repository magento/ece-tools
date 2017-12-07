<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Filesystem\BackupList as BackupFilesList;
use Psr\Log\LoggerInterface;

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
     * Command name
     */
    const NAME = 'backup:list';

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

        try {
            $output->writeln('<comment>The list of backup files:</comment>');
            $output->writeln($this->backupFilesList->getList());
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
