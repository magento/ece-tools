<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\Command\Wrapper;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for dumping SCD related config.
 */
class ConfigDump extends Command
{
    const NAME = 'config:dump';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var Wrapper
     */
    private $wrapper;

    /**
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     * @param Wrapper $wrapper
     */
    public function __construct(ProcessInterface $process, LoggerInterface $logger, Wrapper $wrapper)
    {
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
            ->setAliases(['dump'])
            ->setDescription('Dump static content');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->wrapper->execute(function () {
            $this->logger->info('Starting dump.');
            $this->process->execute();
            $this->logger->info('Dump completed.');
        }, $output);
    }
}
