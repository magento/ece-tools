<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Package\Manager as PackageManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for build hook. Responsible for preparing the codebase before it's moved to the server.
 */
class CronKill extends Command
{
    const NAME = 'cron:kill';

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     * @param PackageManager $packageManager
     */
    public function __construct(
        ProcessInterface $process,
        LoggerInterface $logger,
        PackageManager $packageManager
    ) {
        $this->process = $process;
        $this->logger = $logger;
        $this->packageManager = $packageManager;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Kill all Magento cron processes');

        parent::configure();
    }

    /**
     * This method is a proxy for calling build:generate and build:transfer commands.
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->process->execute();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
