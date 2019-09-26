<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class InstallUpdate implements StepInterface
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
     * @var StepInterface[]
     */
    private $installSteps;

    /**
     * @var StepInterface[]
     */
    private $updateSteps;

    /**
     * @param LoggerInterface $logger
     * @param State $state
     * @param StepInterface[] $installSteps
     * @param StepInterface[] $updateSteps
     */
    public function __construct(
        LoggerInterface $logger,
        State $state,
        array $installSteps,
        array $updateSteps
    ) {
        $this->logger = $logger;
        $this->deployConfig = $state;
        $this->installSteps = $installSteps;
        $this->updateSteps = $updateSteps;
    }

    public function execute()
    {
        try {
            if (!$this->deployConfig->isInstalled()) {
                $this->logger->notice('Starting install.');

                foreach ($this->installSteps as $step) {
                    $step->execute();
                }

                $this->logger->notice('End of install.');
            } else {
                $this->logger->notice('Starting update.');

                foreach ($this->updateSteps as $step) {
                    $step->execute();
                }

                $this->logger->notice('End of update.');
            }
        } catch (GenericException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
