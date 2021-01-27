<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Cron\JobUnlocker;
use Magento\MagentoCloud\Package\MagentoVersion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * CLI command for unlocking cron jobs that stuck in "running" state.
 *
 * @api
 */
class CronUnlock extends Command
{
    public const NAME = 'cron:unlock';
    public const OPTION_JOB_CODE = 'job-code';
    public const UNLOCK_MESSAGE = 'The job is terminated by cron:unlock command';

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
    protected function configure(): void
    {
        $this->setName(static::NAME)
            ->setDescription('Unlock cron jobs that stuck in "running" state.')
            ->addOption(
                self::OPTION_JOB_CODE,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Cron job code to unlock.'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
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

        return Cli::SUCCESS;
    }
}
