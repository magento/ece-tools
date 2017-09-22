<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class InstallUpdate implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @var Install
     */
    private $installProcess;
    /**
     * @var Update
     */
    private $updateProcess;

    /**
     * @param LoggerInterface $logger
     * @param DeployConfig $deployConfig
     * @param Install $installProcess
     * @param Update $updateProcess
     */
    public function __construct(
        LoggerInterface $logger,
        DeployConfig $deployConfig,
        Install $installProcess,
        Update $updateProcess
    ) {
        $this->logger = $logger;
        $this->deployConfig = $deployConfig;
        $this->installProcess = $installProcess;
        $this->updateProcess = $updateProcess;
    }

    public function execute()
    {
        if ($this->deployConfig->isInstalling()) {
            $this->logger->info('Starting install.');
            $this->installProcess->execute();
        } else {
            $this->logger->info('Starting update.');
            $this->updateProcess->execute();
        }
    }
}
