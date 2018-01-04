<?php
/**
 * Copyright © Magento. All rights reserved.
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
 * CLI command for prestart hook.
 */
class Prestart extends Command
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
            ->setDescription('Final setup before starting services');

        parent::configure();
    }

    /**
     * Run final operations before starting up services: SCD to local storage if available
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->wrapper->execute(function () {
            $this->logger->info('Starting prestart.');
            $this->process->execute();
            $this->logger->info('Prestart completed.');
        }, $output);
    }
}
