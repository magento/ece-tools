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
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;

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
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     * @param FlagManager $flagManager
     */
    public function __construct(
        ProcessInterface $process,
        LoggerInterface $logger,
        FlagManager $flagManager
    ) {
        $this->process = $process;
        $this->logger = $logger;
        $this->flagManager = $flagManager;

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
            $this->flagManager->delete(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED);
            $this->process->execute();
        } catch (\Exception $exception) {
            $this->flagManager->set(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED);
            $this->logger->critical($exception->getMessage());

            throw $exception;
        }

        $this->logger->notice('Deployment completed.');
    }
}
