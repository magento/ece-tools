<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\FlagFile\Flag\StaticContentDeployInBuild;
use Magento\MagentoCloud\Filesystem\FlagFile\Manager as FlagFileManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Package\Manager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PreBuild implements ProcessInterface
{
    /**
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Manager
     */
    private $packageManager;

    /**
     * @var FlagFileManager
     */
    private $flagFileManager;

    /**
     * PreBuild constructor.
     * @param BuildConfig $buildConfig
     * @param LoggerInterface $logger
     * @param Manager $packageManager
     * @param FlagFileManager $flagFileManager
     */
    public function __construct(
        BuildConfig $buildConfig,
        LoggerInterface $logger,
        Manager $packageManager,
        FlagFileManager $flagFileManager
    ) {
        $this->buildConfig = $buildConfig;
        $this->logger = $logger;
        $this->packageManager = $packageManager;
        $this->flagFileManager = $flagFileManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $verbosityLevel = $this->buildConfig->getVerbosityLevel();

        $this->logger->info('Verbosity level is ' . ($verbosityLevel ?: 'not set'));
        $this->flagFileManager->delete(StaticContentDeployInBuild::KEY);
        $this->logger->info('Starting build. ' . $this->packageManager->getPrettyInfo());
    }
}
