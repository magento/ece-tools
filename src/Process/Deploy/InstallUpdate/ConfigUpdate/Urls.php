<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Urls implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Environment $environment
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     */
    public function __construct(
        Environment $environment,
        ProcessInterface $process,
        LoggerInterface $logger
    ) {
        $this->environment = $environment;
        $this->process = $process;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->environment->isMasterBranch()
            || !$this->environment->isUpdateUrlsEnabled() ) {
            $this->logger->info('Skipping URL updates');

            return;
        }

        $this->logger->info('Updating secure and unsecure URLs');

        $this->process->execute();
    }
}
