<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Package\Manager;

/**
 * CLI command for deploy hook. Responsible for installing/updating/configuring Magento
 */
class PreStart extends Command
{
    const NAME = 'prestart';

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     * @param Manager $manager
     */
    public function __construct(
        ProcessInterface $process,
        LoggerInterface $logger,
        Manager $manager
    ) {
        $this->process = $process;
        $this->logger = $logger;
        $this->manager = $manager;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Prepare environments for deployment');

        parent::configure();
    }

    /**
     * Deploy application: copy writable directories back, install or update Magento data.
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->logger->info('Starting pre-start. ' . $this->manager->getPrettyInfo());
            $this->process->execute();
            $this->logger->info('Completed pre-start.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
