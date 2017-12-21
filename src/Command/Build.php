<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\App\Command\Wrapper;
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
            ->setDescription('Builds application');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->wrapper->execute(function () {
            $this->logger->info('Starting build.');
            $this->process->execute();
            $this->logger->info('Building completed.');
        });
    }
}
