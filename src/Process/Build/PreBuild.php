<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\FlagFile\StaticContentDeployFlag;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Package\Manager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PreBuild implements ProcessInterface
{
    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Manager
     */
    private $packageManager;

    /**
     * @var FlagFilePool
     */
    private $flagFilePool;

    /**
     * PreBuild constructor.
     * @param BuildInterface $stageConfig
     * @param LoggerInterface $logger
     * @param Manager $packageManager
     * @param FlagFilePool $flagFilePool
     */
    public function __construct(
        BuildInterface $stageConfig,
        LoggerInterface $logger,
        Manager $packageManager,
        FlagFilePool $flagFilePool
    ) {
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
        $this->packageManager = $packageManager;
        $this->flagFilePool = $flagFilePool;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $verbosityLevel = $this->stageConfig->get(BuildInterface::VAR_VERBOSE_COMMANDS);

        $this->logger->info('Verbosity level is ' . ($verbosityLevel ?: 'not set'));
        $this->flagFilePool->getFlag(StaticContentDeployFlag::KEY)->delete();
        $this->logger->info('Starting build. ' . $this->packageManager->getPrettyInfo());
    }
}
