<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Package\MagentoVersion;
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

    const OPTION_JOB_CODE = 'job-code';

    const UNLOCK_MESSAGE = 'The job is terminated by cron:unlock command';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JobUnlocker
     */
    private $jobUnlocker;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param JobUnlocker $jobUnlocker
     * @param LoggerInterface $logger
     * @param MagentoVersion $version
     */
    public function __construct(JobUnlocker $jobUnlocker, LoggerInterface $logger, MagentoVersion $version)
    {
        $this->jobUnlocker = $jobUnlocker;
        $this->logger = $logger;
        $this->magentoVersion = $version;

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
                    $this->jobUnlocker->unlockByJobCode($jobCode, self::UNLOCK_MESSAGE);
                    $this->logger->info(sprintf('Unlocking cron jobs with code #%s.', $jobCode));
                }
            } else {
                $this->jobUnlocker->unlockAll(self::UNLOCK_MESSAGE);
                $this->logger->info('Unlocking all cron jobs.');
            }

            $this->logger->info('Unlocking completed.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
