<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Symfony\Component\Console\Command\Command;
use Magento\MagentoCloud\Backup\Restorer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Psr\Log\LoggerInterface;

/**
 * CLI command for restoring Magento configuration files from backup.
 */
class BackupRestore extends Command
{
    /**
     * @var Restorer
     */
    private $restorer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Command name
     */
    const NAME = 'backup:restore';

    /**
     * @param Restorer $restorer
     * @param LoggerInterface $logger
     */
    public function __construct(Restorer $restorer, LoggerInterface $logger)
    {
        $this->restorer = $restorer;
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Restore important configuration files');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Overwrite existing files during restoring a backup'
        );
        $this->addOption(
            'file',
            null,
            InputOption::VALUE_OPTIONAL,
            'A specific file recovery path'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->restorer->run($input, $output);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
