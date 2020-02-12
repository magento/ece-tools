<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\DB\DumpGenerator;
use Magento\MagentoCloud\Util\BackgroundProcess;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
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
     * @var DumpGenerator
     */
    private $dumpGenerator;

    /**
     * @var MaintenanceModeSwitcher
     */
    private $maintenanceModeSwitcher;

    /**
     * @var BackgroundProcess
     */
    private $backgroundProcess;
    /**
     * @var Switcher
     */
    private $cronSwitcher;

    /**
     * @var JobUnlocker
     */
    private $jobUnlocker;

    /**
     * @param DumpGenerator $dumpGenerator
     * @param LoggerInterface $logger
     * @param MaintenanceModeSwitcher $maintenanceModeSwitcher
     * @param Switcher $cronSwitcher
     * @param BackgroundProcess $backgroundProcess
     * @param JobUnlocker $jobUnlocker
     */
    public function __construct(
        DumpGenerator $dumpGenerator,
        LoggerInterface $logger,
        MaintenanceModeSwitcher $maintenanceModeSwitcher,
        Switcher $cronSwitcher,
        BackgroundProcess $backgroundProcess,
        JobUnlocker $jobUnlocker
    ) {
        $this->dumpGenerator = $dumpGenerator;
        $this->logger = $logger;
        $this->maintenanceModeSwitcher = $maintenanceModeSwitcher;
        $this->cronSwitcher = $cronSwitcher;
        $this->backgroundProcess = $backgroundProcess;
        $this->jobUnlocker = $jobUnlocker;

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
                implode(' ', DumpGenerator::DATABASE_MAP)
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
            $this->maintenanceModeSwitcher->enable();
            $this->cronSwitcher->disable();
            $this->backgroundProcess->kill();
            $this->dumpGenerator->create(
                (bool)$input->getOption(self::OPTION_REMOVE_DEFINERS),
                (array)$input->getArgument(self::ARGUMENT_DATABASES)
            );
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        } finally {
            $this->jobUnlocker->unlockAll('The job is terminated due to database dump');
            $this->cronSwitcher->enable();
            $this->maintenanceModeSwitcher->disable();
        }
    }
}
