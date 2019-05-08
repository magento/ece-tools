<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param Environment $environment
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        ProcessInterface $process,
        LoggerInterface $logger,
        DeployInterface $stageConfig
    ) {
        $this->environment = $environment;
        $this->process = $process;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
    }

    /**
     * Runs update url processes.
     * Always run if FORCE_UPDATE_URLS set to true
     * Skip url updates if master branch is detected or UPDATE_URLS set to false
     *
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->stageConfig->get(DeployInterface::VAR_FORCE_UPDATE_URLS)) {
            if ($this->environment->isMasterBranch()) {
                $this->logger->info(
                    'Skipping URL updates because we are deploying to a Production or Staging environment.'
                    . ' You can override this behavior by setting the FORCE_URL_UPDATES variable to true.'
                );
                return;
            }

            if (!$this->stageConfig->get(DeployInterface::VAR_UPDATE_URLS)) {
                $this->logger->info('Skipping URL updates because the URL_UPDATES variable is set to false.');
                return;
            }
        }

        $this->logger->info('Updating secure and unsecure URLs');

        $this->process->execute();
    }
}
