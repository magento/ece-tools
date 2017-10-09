<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\BackgroundDirectoryCleaner;
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
     * @var Manager
     */
    private $packageManager;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var BackgroundDirectoryCleaner
     */
    private $cleaner;

    /**
     * @param BuildConfig $buildConfig
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param Manager $packageManager
     * @param DirectoryList $directoryList
     * @param File $file
     * @param BackgroundDirectoryCleaner $cleaner
     */
    public function __construct(
        BuildConfig $buildConfig,
        Environment $environment,
        LoggerInterface $logger,
        Manager $packageManager,
        DirectoryList $directoryList,
        File $file,
        BackgroundDirectoryCleaner $cleaner
    ) {
        $this->buildConfig = $buildConfig;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->packageManager = $packageManager;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->cleaner = $cleaner;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $verbosityLevel = $this->buildConfig->getVerbosityLevel();
        $directories = $this->environment->getRestorableDirectories();
        $cloudFlagsDir = $this->directoryList->getMagentoRoot() . '/' . $directories['cloud_flags'];

        $this->logger->info('Verbosity level is ' . ($verbosityLevel ?: 'not set'));

        $this->cleaner->backgroundDeleteDirectory($cloudFlagsDir);
        $this->file->createDirectory($cloudFlagsDir, Environment::DEFAULT_DIRECTORY_MODE);
        $this->environment->removeFlagStaticContentInBuild();
        $this->logger->info('Starting build. ' . $this->packageManager->getPrettyInfo());
    }
}
