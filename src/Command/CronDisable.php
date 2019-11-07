<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Step\Deploy\DisableCron;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for disabling Magento cron processes
 */
class CronDisable extends Command
{
    public const NAME = 'cron:disable';

    /**
     * @var DisableCron
     */
    private $disableCron;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DisableCron $disableCron
     * @param LoggerInterface $logger
     */
    public function __construct(DisableCron $disableCron, LoggerInterface $logger)
    {
        $this->disableCron = $disableCron;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Disable all Magento cron processes');

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
            $this->disableCron->execute();
        } catch (GenericException $e) {
            $this->logger->critical($e->getMessage());

            throw $e;
        }
    }
}
