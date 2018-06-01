<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Package\Manager as PackageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for build hook. Responsible for preparing the codebase before it's moved to the server.
 */
class Build extends Command
{
    const NAME = 'build';

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
            ->setDescription('Builds application');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->logger->notice('Starting build. ' . $this->packageManager->getPrettyInfo());
            $this->process->execute();
            $this->logger->notice('Building completed.');
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }
    }
}
