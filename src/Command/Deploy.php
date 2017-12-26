<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
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
     * @var Wrapper
     */
    private $wrapper;

    /**
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     * @param Wrapper $wrapper
     */
    public function __construct(
        ProcessInterface $process,
        LoggerInterface $logger,
        Wrapper $wrapper
    ) {
        $this->process = $process;
        $this->logger = $logger;
        $this->wrapper = $wrapper;

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
        return $this->wrapper->execute(function () {
            $this->logger->info('Starting deploy.');
            $this->process->execute();
            $this->logger->info('Deployment completed.');
        });
    }
}
