<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Util\PackageManager;
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
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @param BuildConfig $buildConfig
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param PackageManager $packageManager
     */
    public function __construct(
        BuildConfig $buildConfig,
        Environment $environment,
        LoggerInterface $logger,
        PackageManager $packageManager
    ) {
        $this->buildConfig = $buildConfig;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->packageManager = $packageManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $verbosityLevel = $this->buildConfig->getVerbosityLevel();

        $this->logger->info('Verbosity level is ' . ($verbosityLevel ?: 'not set'));
        $this->environment->removeFlagStaticContentInBuild();
        $this->logger->info('Starting build. ' . $this->packageManager->get());
    }
}
