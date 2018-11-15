<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;
use Magento\MagentoCloud\Process\ProcessException;
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
     * @var State
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
     * @param State $state
     * @param Install $installProcess
     * @param Update $updateProcess
     */
    public function __construct(
        LoggerInterface $logger,
        State $state,
        Install $installProcess,
        Update $updateProcess
    ) {
        $this->logger = $logger;
        $this->deployConfig = $state;
        $this->installProcess = $installProcess;
        $this->updateProcess = $updateProcess;
    }

    public function execute()
    {
        try {
            if (!$this->deployConfig->isInstalled()) {
                $this->logger->notice('Starting install.');
                $this->installProcess->execute();
                $this->logger->notice('End of install.');
            } else {
                $this->logger->notice('Starting update.');
                $this->updateProcess->execute();
                $this->logger->notice('End of update.');
            }
        } catch (GenericException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
