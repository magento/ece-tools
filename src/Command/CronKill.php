<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for killing Magento cron processes
 */
class CronKill extends Command
{
    const NAME = 'cron:kill';

    /**
     * @var StepInterface
     */
    private $step;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param StepInterface $step
     * @param LoggerInterface $logger
     */
    public function __construct(
        StepInterface $step,
        LoggerInterface $logger
    ) {
        $this->step = $step;
        $this->logger = $logger;

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
     * Runs process which finds all running Magento cron processes and kills them
     *
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->step->execute();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
