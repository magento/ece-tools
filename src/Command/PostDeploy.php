<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;

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
            ->setDescription('Performs after deploy operations.');

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
            if ($this->flagManager->exists(FlagManager::FLAG_DEPLOY_HOOK_IS_FAILED)) {
                $this->logger->warning('Post-deploy is skipped because deploy was failed.');

                return 0;
            }

            $this->logger->notice('Starting post-deploy.');
            $this->process->execute();
            $this->logger->notice('Post-deploy is complete.');
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage());

            throw $e;
        }
    }
}
