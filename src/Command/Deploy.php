<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

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
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProcessInterface $process,
        LoggerInterface $logger
    ) {
        $this->process = $process;
        $this->logger = $logger;

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
            /**
             * This is the beginning of the deploy phase.
             * But we cannot log information about starting it on this place because of specific work with log file
             * after build phase.
             * Please, see Magento\MagentoCloud\Process\Deploy\PreDeploy::execute()
             */
            $this->process->execute();
            $this->logger->info('Deployment completed.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
