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
 * Performs post-deploy operations, such us:
 * - Cleaning cache
 */
class PostDeploy extends Command
{
    const NAME = 'post-deploy';

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Wrapper $wrapper
     */
    private $wrapper;

    /**
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
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
            ->setDescription('Performs after deploy operations.');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->wrapper->execute(function () {
            $this->logger->info('Starting post-deploy.');
            $this->process->execute();
            $this->logger->info('Post-deploy is complete.');
        }, $output);
    }
}
