<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;

/**
 * CLI command for build hook. Responsible for preparing the codebase before it's moved to the server.
 */
class Build extends Command
{
    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ProcessInterface $process,
        BuildConfig $buildConfig,
        Environment $environment,
        LoggerInterface $logger
    ) {
        $this->process = $process;
        $this->buildConfig = $buildConfig;
        $this->environment = $environment;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('build')
            ->setDescription('Builds application');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $buildVerbosityLevel = $this->buildConfig->getVerbosityLevel();
            $this->logger->info('Verbosity level is ' . $buildVerbosityLevel ?: 'not set');

            $this->environment->setStaticDeployInBuild(false);
            $this->logger->info($this->environment->startingMessage("build"));

            $this->process->execute();
        } catch (\Exception $exception) {
            $output->writeln($exception->getMessage());

            return $exception->getCode();
        }

        return 0;
    }
}
