<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
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
     * @var FlagManager
     */
    private $flagManager;

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
     * @param FlagManager $flagManager
     * @param StepInterface[] $installSteps
     * @param StepInterface[] $updateSteps
     */
    public function __construct(
        LoggerInterface $logger,
        State $state,
        FlagManager $flagManager,
        array $installSteps,
        array $updateSteps
    ) {
        $this->logger = $logger;
        $this->deployConfig = $state;
        $this->flagManager = $flagManager;
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
                if ($this->flagManager->exists(FlagManager::FLAG_ENV_FILE_ABSENCE)) {
                    $this->logger->warning(
                        'Magento state indicated as installed'
                        . ' but configuration file app/etc/env.php was empty or did not exist.'
                        . ' Required data will be restored from environment configurations'
                        . ' and from .magento.env.yaml file.',
                        ['errorCode' => Error::WARN_ENV_PHP_MISSED]
                    );
                }
                $this->logger->notice('Starting update.');

                foreach ($this->updateSteps as $step) {
                    $step->execute();
                }

                $this->logger->notice('End of update.');
            }
            $this->flagManager->delete(FlagManager::FLAG_ENV_FILE_ABSENCE);
        } catch (GenericException $exception) {
            throw new StepException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
