<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Database\DbConfig;
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
    const NAME = 'db-dump';

    const ARGUMENT_DATABASES = 'databases';

    const OPTION_REMOVE_DEFINERS = 'remove-definers';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DumpProcessor
     */
    private $dumpProcessor;

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @param DumpProcessor $dumpProcessor
     * @param LoggerInterface $logger
     * @param DbConfig $dbConfig
     */
    public function __construct(
        DumpProcessor $dumpProcessor,
        LoggerInterface $logger,
        DbConfig $dbConfig
    ) {
        $this->dumpProcessor = $dumpProcessor;
        $this->logger = $logger;
        $this->dbConfig = $dbConfig;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Creates backups of databases');
        $this->addArgument(
            self::ARGUMENT_DATABASES,
            InputArgument::IS_ARRAY,
            sprintf(
                'Databases to backup. Available values: %s or empty. By default will backup the databases'
                . ' based on the databases configuration from the file <magento_root>/app/etc/env.php ',
                implode(' ', DumpProcessor::DATABASE_MAP)
            ),
            []
        );
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
     * @throws ConfigException
     * @throws GenericException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $databases = (array)$input->getArgument(self::ARGUMENT_DATABASES);
        if (!empty($databases)) {
            if (!$this->validateDatabaseNames($databases)) {
                return null;
            }
            $connections = array_keys(array_intersect(DumpProcessor::DATABASE_MAP, $databases));
            if (!$this->checkConnectionsAvailability($connections)) {
                return null;
            }
        } else {
            $connections = array_values(array_intersect_key(
                array_flip(DumpProcessor::CONNECTION_MAP),
                $this->dbConfig->get()[DbConfig::KEY_CONNECTION] ?? []
            ));
        }

        if ($input->isInteractive()) {
            $helper = $this->getHelper('question');

            $questionParts = [
                'The db-dump operation switches the site to maintenance mode, stops all active cron jobs ' .
                'and consumer queue processes, and disables cron jobs before starting the the dump process.',
                'Your site will not receive any traffic until the operation completes.',
                'Do you wish to proceed with this process? (y/N)?',
            ];
            $question = new ConfirmationQuestion(
                implode(PHP_EOL, $questionParts),
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                return null;
            }
        }

        try {
            $this->logger->info('Starting backup.');
            $this->dumpProcessor->execute(
                $connections,
                (bool)$input->getOption(self::OPTION_REMOVE_DEFINERS)
            );
            $this->logger->info('Backup completed.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * Validates database names
     *
     * @param array $databases
     * @return bool
     */
    private function validateDatabaseNames(array $databases): bool
    {
        $invalidDatabaseNames = array_diff($databases, DumpProcessor::DATABASE_MAP);
        if (!empty($invalidDatabaseNames)) {
            $this->logger->error(sprintf(
                'Incorrect the database names:[ %s ]. Available database names: [ %s ]',
                implode(' ', $invalidDatabaseNames),
                implode(' ', DumpProcessor::DATABASE_MAP)
            ));
            return false;
        }
        return true;
    }

    /**
     * Checks availability of connections
     *
     * @param array $connections
     * @return bool
     * @throws ConfigException
     */
    private function checkConnectionsAvailability(array $connections): bool
    {
        $result = true;
        $envConnections = $this->dbConfig->get()[DbConfig::KEY_CONNECTION] ?? [];
        foreach ($connections as $connection) {
            if (!isset($envConnections[DumpProcessor::CONNECTION_MAP[$connection]])) {
                $this->logger->error(sprintf(
                    'Environment has not connection `%s` associated with database `%s`',
                    DumpProcessor::CONNECTION_MAP[$connection],
                    DumpProcessor::DATABASE_MAP[$connection]
                ));
                $result = false;
            }
        }
        return $result;
    }
}
