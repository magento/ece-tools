<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Filesystem\FlagFile\Flag\StaticContentDeployInBuild;
use Magento\MagentoCloud\Filesystem\FlagFile\Manager as FlagFileManager;
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
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var ConfigFileStructure
     */
    private $configFileStructureValidator;

    /**
     * @var FlagFileManager
     */
    private $flagFileManager;

    /**
     * DeployStaticContent constructor.
     * @param LoggerInterface $logger
     * @param BuildConfig $buildConfig
     * @param ProcessInterface $process
     * @param ConfigFileStructure $configFileStructureValidator
     * @param FlagFileManager $flagFileManager
     */
    public function __construct(
        LoggerInterface $logger,
        BuildConfig $buildConfig,
        ProcessInterface $process,
        ConfigFileStructure $configFileStructureValidator,
        FlagFileManager $flagFileManager
    ) {
        $this->logger = $logger;
        $this->buildConfig = $buildConfig;
        $this->process = $process;
        $this->configFileStructureValidator = $configFileStructureValidator;
        $this->flagFileManager = $flagFileManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->flagFileManager->delete(StaticContentDeployInBuild::KEY);

        if ($this->buildConfig->get(BuildConfig::OPT_SKIP_SCD)) {
            $this->logger->notice('Skipping static content deploy');

            return;
        }

        $validationResult = $this->configFileStructureValidator->validate();

        if ($validationResult instanceof Error) {
            $this->logger->info('Skipping static content deploy. ' . $validationResult->getError());

            return;
        }

        $this->process->execute();

        $this->flagFileManager->set(StaticContentDeployInBuild::KEY);
    }
}
