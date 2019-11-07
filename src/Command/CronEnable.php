<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Step\PostDeploy\EnableCron;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for enabling Magento cron processes
 */
class CronEnable extends Command
{
    public const NAME = 'cron:enable';

    /**
     * @var EnableCron
     */
    private $enableCron;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param EnableCron $enableCron
     * @param LoggerInterface $logger
     */
    public function __construct(EnableCron $enableCron, LoggerInterface $logger)
    {
        $this->enableCron = $enableCron;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Enable Magento cron processes');

        parent::configure();
    }

    /**
     * Runs process which finds all running Magento cron processes and kills them
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->enableCron->execute();
        } catch (GenericException $e) {
            $this->logger->critical($e->getMessage());

            throw $e;
        }
    }
}
