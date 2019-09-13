<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\State;
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
     * @var ProcessInterface[]
     */
    private $installProcesses;

    /**
     * @var ProcessInterface[]
     */
    private $updateProcesses;

    /**
     * @param LoggerInterface $logger
     * @param State $state
     * @param ProcessInterface[] $installProcesses
     * @param ProcessInterface[] $updateProcesses
     */
    public function __construct(
        LoggerInterface $logger,
        State $state,
        array $installProcesses,
        array $updateProcesses
    ) {
        $this->logger = $logger;
        $this->deployConfig = $state;
        $this->installProcesses = $installProcesses;
        $this->updateProcesses = $updateProcesses;
    }

    public function execute()
    {
        try {
            if (!$this->deployConfig->isInstalled()) {
                $this->logger->notice('Starting install.');

                foreach ($this->installProcesses as $process) {
                    $process->execute();
                }

                $this->logger->notice('End of install.');
            } else {
                $this->logger->notice('Starting update.');

                foreach ($this->updateProcesses as $process) {
                    $process->execute();
                }

                $this->logger->notice('End of update.');
            }
        } catch (GenericException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
