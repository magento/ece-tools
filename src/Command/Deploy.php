<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Util\DeployPreparer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Process\ProcessInterface;

/**
 * CLI command for deploy hook. Responsible for installing/updating/configuring Magento
 */
class Deploy extends Command
{
    const NAME = 'deploy';

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DeployPreparer
     */
    private $deployPreparer;

    /**
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     * @param DeployPreparer $deployPreparer
     */
    public function __construct(
        ProcessInterface $process,
        LoggerInterface $logger,
        DeployPreparer $deployPreparer
    ) {
        $this->process = $process;
        $this->logger = $logger;
        $this->deployPreparer = $deployPreparer;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Deploys application');

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
            $this->deployPreparer->prepare();
            $this->logger->info('Starting deploy.');
            $this->process->execute();
            $this->logger->info('Deployment completed.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
