<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DeployStaticContent implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var ConfigFileStructure
     */
    private $configFileStructureValidator;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @param LoggerInterface $logger
     * @param BuildInterface $stageConfig
     * @param Environment $environment
     * @param ProcessInterface $process
     * @param ConfigFileStructure $configFileStructureValidator
     * @param FlagManager $flagManager
     */
    public function __construct(
        LoggerInterface $logger,
        BuildInterface $stageConfig,
        Environment $environment,
        ProcessInterface $process,
        ConfigFileStructure $configFileStructureValidator,
        FlagManager $flagManager
    ) {
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
        $this->environment = $environment;
        $this->process = $process;
        $this->configFileStructureValidator = $configFileStructureValidator;
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->flagManager->delete(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);

        if ($this->stageConfig->get(BuildInterface::VAR_SKIP_SCD)) {
            $this->logger->notice('Skipping static content deploy');

            return;
        }

        $validationResult = $this->configFileStructureValidator->validate();

        if ($validationResult instanceof Error) {
            $this->logger->info('Skipping static content deploy. ' . $validationResult->getError());

            return;
        }

        $this->process->execute();

        $this->flagManager->set(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
    }
}
