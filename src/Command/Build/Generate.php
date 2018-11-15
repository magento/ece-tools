<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Build;

use Magento\MagentoCloud\Package\Manager as PackageManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command that used as part of build hook.
 * Responsible for patches applying, validating configuration, preparing the codebase, etc.
 */
class Generate extends Command
{
    const NAME = 'build:generate';

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
            ->setDescription('Generates all necessary files for build stage');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->logger->notice('Starting generate command. ' . $this->packageManager->getPrettyInfo());
            $this->process->execute();
            $this->logger->notice('Generate command completed.');
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage());

            throw $e;
        }
    }
}
