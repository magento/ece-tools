<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Filesystem\Flag\StaticContentDeployInBuild;
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
     * @var StageConfigInterface
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
     * @param StageConfigInterface $stageConfig
     * @param Environment $environment
     * @param BuildConfig $buildConfig
     * @param ProcessInterface $process
     * @param ConfigFileStructure $configFileStructureValidator
     * @param FlagManager $flagManager
     */
    public function __construct(
        LoggerInterface $logger,
        StageConfigInterface $stageConfig,
        Environment $environment,
        BuildConfig $buildConfig,
        ProcessInterface $process,
        ConfigFileStructure $configFileStructureValidator,
        FlagManager $flagManager
    ) {
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
        $this->environment = $environment;
        $this->buildConfig = $buildConfig;
        $this->process = $process;
        $this->configFileStructureValidator = $configFileStructureValidator;
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->flagManager->delete(StaticContentDeployInBuild::KEY);

        if ($this->stageConfig->get(StageConfigInterface::VAR_SKIP_SCD)) {
            $this->logger->notice('Skipping static content deploy');

            return;
        }

        $validationResult = $this->configFileStructureValidator->validate();

        if ($validationResult instanceof Error) {
            $this->logger->info('Skipping static content deploy. ' . $validationResult->getError());

            return;
        }

        $this->process->execute();

        $this->flagManager->set(StaticContentDeployInBuild::KEY);
    }
}
