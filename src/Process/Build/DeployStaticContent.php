<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator\Build\ConfigFileStructure;
use Magento\MagentoCloud\Config\Validator\Result\Error;
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
     * @var Environment
     */
    private $environment;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var ConfigFileStructure
     */
    private $configFileStructureValidator;

    /**
     * @param LoggerInterface $logger
     * @param BuildConfig $buildConfig
     * @param Environment $environment
     * @param ProcessInterface $process
     * @param ConfigFileStructure $configFileStructureValidator
     */
    public function __construct(
        LoggerInterface $logger,
        BuildConfig $buildConfig,
        Environment $environment,
        ProcessInterface $process,
        ConfigFileStructure $configFileStructureValidator
    ) {
        $this->logger = $logger;
        $this->buildConfig = $buildConfig;
        $this->environment = $environment;
        $this->process = $process;
        $this->configFileStructureValidator = $configFileStructureValidator;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->environment->removeFlagStaticContentInBuild();

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
    }
}
