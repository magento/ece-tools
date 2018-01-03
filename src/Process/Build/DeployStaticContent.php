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
use Magento\MagentoCloud\Filesystem\FlagFile\StaticContentDeployFlag;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
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
     * @var FlagFilePool
     */
    private $flagFilePool;

    /**
     * @param LoggerInterface $logger
     * @param BuildInterface $stageConfig
     * @param Environment $environment
     * @param ProcessInterface $process
     * @param ConfigFileStructure $configFileStructureValidator
     * @param FlagFilePool $flagFilePool
     */
    public function __construct(
        LoggerInterface $logger,
        BuildInterface $stageConfig,
        Environment $environment,
        ProcessInterface $process,
        ConfigFileStructure $configFileStructureValidator,
        FlagFilePool $flagFilePool
    ) {
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
        $this->environment = $environment;
        $this->process = $process;
        $this->configFileStructureValidator = $configFileStructureValidator;
        $this->flagFilePool = $flagFilePool;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $scdFlag = $this->flagFilePool->getFlag(StaticContentDeployFlag::KEY);
        $scdFlag->delete();

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
        $scdFlag->set();
    }
}
