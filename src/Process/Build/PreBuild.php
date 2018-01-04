<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
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
     * @var FlagManager
     */
    private $flagManager;

    /**
     * PreBuild constructor.
     * @param BuildInterface $stageConfig
     * @param LoggerInterface $logger
     * @param Manager $packageManager
     * @param FlagManager $flagManager
     */
    public function __construct(
        BuildInterface $stageConfig,
        LoggerInterface $logger,
        Manager $packageManager,
        FlagManager $flagManager
    ) {
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
        $this->packageManager = $packageManager;
        $this->flagManager = $flagManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $verbosityLevel = $this->stageConfig->get(BuildInterface::VAR_VERBOSE_COMMANDS);

        $this->logger->info('Verbosity level is ' . ($verbosityLevel ?: 'not set'));
        $this->flagManager->delete(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->logger->info('Starting build. ' . $this->packageManager->getPrettyInfo());
    }
}
