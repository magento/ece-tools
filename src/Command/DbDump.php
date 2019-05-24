<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\DB\DumpGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class DbDump for safely creating backup of database
 */
class DbDump extends Command
{
    const NAME = 'db-dump';

    const OPTION_REMOVE_DEFINERS = 'remove-definers';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DumpGenerator
     */
    private $dumpGenerator;

    /**
     * @param DumpGenerator $dumpGenerator
     * @param LoggerInterface $logger
     */
    public function __construct(DumpGenerator $dumpGenerator, LoggerInterface $logger)
    {
        $this->dumpGenerator = $dumpGenerator;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Creates backup of database');

        $this->addOption(
            self::OPTION_REMOVE_DEFINERS,
            'd',
            InputOption::VALUE_NONE,
            'Remove definers from the database dump'
        );

        parent::configure();
    }

    /**
     * Creates DB dump.
     * Command requires confirmation before execution.
     *
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'We suggest to enable maintenance mode before running this command. Do you want to continue [y/N]?',
            false
        );
        if (!$helper->ask($input, $output, $question) && $input->isInteractive()) {
            return null;
        }
        try {
            $this->logger->info('Starting backup.');
            $this->dumpGenerator->create((bool)$input->getOption(self::OPTION_REMOVE_DEFINERS));
            $this->logger->info('Backup completed.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
