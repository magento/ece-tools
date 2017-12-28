<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Util\CronJobUnlocker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for unlocking cron jobs that stuck in "running" state.
 */
class CronUnlock extends Command
{
    const NAME = 'cron:unlock';

    const OPTION_JOB_CODE = 'job_code';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CronJobUnlocker
     */
    private $cronJobUnlocker;

    /**
     * @param CronJobUnlocker $cronJobUnlocker
     * @param LoggerInterface $logger
     */
    public function __construct(CronJobUnlocker $cronJobUnlocker, LoggerInterface $logger)
    {
        $this->cronJobUnlocker = $cronJobUnlocker;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Unlock cron jobs that stuck in "running" state.');

        $this->addOption(
            self::OPTION_JOB_CODE,
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
            'Cron job code to unlock.'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->logger->info('Starting unlocking.');

            $jobCodesToUnlock = array_filter($input->getOption(self::OPTION_JOB_CODE));

            if (count($jobCodesToUnlock)) {
                foreach ($jobCodesToUnlock as $jobCode) {
                    $this->cronJobUnlocker->unlockByJobCode($jobCode);
                    $this->logger->info(sprintf('Unlocking cron jobs with job_code #%s.', $jobCode));
                }
            } else {
                $this->cronJobUnlocker->unlockAll();
                $this->logger->info('Unlocking all cron jobs..');
            }

            $this->logger->info('Unlocking completed.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
