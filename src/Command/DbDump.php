<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\DB\DumpProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class DbDump for safely creating backup of database
 *
 * @api
 */
class DbDump extends Command
{
    public const NAME = 'db-dump';
    public const ARGUMENT_DATABASES = 'databases';
    public const OPTION_REMOVE_DEFINERS = 'remove-definers';
    public const OPTION_DUMP_DIRECTORY = 'dump-directory';

    /**
     * @var DumpProcessor
     */
    private $dumpProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DumpProcessor $dumpProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        DumpProcessor $dumpProcessor,
        LoggerInterface $logger
    ) {
        $this->dumpProcessor = $dumpProcessor;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Creates database backups.');
        $this->addArgument(
            self::ARGUMENT_DATABASES,
            InputArgument::IS_ARRAY,
            sprintf(
                'Databases for backup. Available Values: [ %s ]. If the argument value is not specified,'
                . ' database backups will be created using the credentials stored in the `MAGENTO_CLOUD_RELATIONSHIP`'
                . ' environment variable or/and the `stage.deploy.DATABASE_CONFIGURATION` property of the'
                . ' .magento.env.yaml configuration file.',
                implode(' ', DumpProcessor::DATABASES)
            ),
            []
        );
        $this->addOption(
            self::OPTION_REMOVE_DEFINERS,
            'd',
            InputOption::VALUE_NONE,
            'Remove definers from the database dump'
        );
        $this->addOption(
            self::OPTION_DUMP_DIRECTORY,
            'a',
            InputOption:: VALUE_REQUIRED,
            'Use alternative directory for saving dump'
        );

        parent::configure();
    }

    /**
     * Creates DB dump.
     * Command requires confirmation before execution.
     *
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $databases = (array)$input->getArgument(self::ARGUMENT_DATABASES);
        try {
            $this->validateDatabaseNames($databases);

            if ($input->isInteractive()) {
                $helper = $this->getHelper('question');

                $questionParts = [
                    'The db-dump operation switches the site to maintenance mode, stops all active cron jobs ' .
                    'and consumer queue processes, and disables cron jobs before starting the dump process.',
                    'Your site will not receive any traffic until the operation completes.',
                    'Do you wish to proceed with this process? (y/N)?',
                ];
                $question = new ConfirmationQuestion(
                    implode(PHP_EOL, $questionParts),
                    false
                );

                if (!$helper->ask($input, $output, $question)) {
                    return Cli::SUCCESS;
                }
            }

            $this->logger->info('Starting backup.');
            $this->dumpProcessor->execute(
                (bool)$input->getOption(self::OPTION_REMOVE_DEFINERS),
                $databases,
                (string)$input->getOption(self::OPTION_DUMP_DIRECTORY)
            );
            $this->logger->info('Backup completed.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            throw $exception;
        }

        return Cli::SUCCESS;
    }

    /**
     * Validates database names
     *
     * @param array $databases
     * @throws GenericException
     */
    private function validateDatabaseNames(array $databases): void
    {
        $invalidDatabaseNames = array_diff($databases, DumpProcessor::DATABASES);
        if (!empty($invalidDatabaseNames)) {
            throw new GenericException(sprintf(
                'Incorrect the database names: [ %s ]. Available database names: [ %s ]',
                implode(' ', $invalidDatabaseNames),
                implode(' ', DumpProcessor::DATABASES)
            ));
        }
    }
}
