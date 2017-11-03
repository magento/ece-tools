<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Util\ArrayManager;
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
     * @var File
     */
    private $file;

    /**
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @param LoggerInterface $logger
     * @param BuildConfig $buildConfig
     * @param File $file
     * @param Environment $environment
     * @param FileList $fileList
     * @param ArrayManager $arrayManager
     * @param ProcessInterface $process
     */
    public function __construct(
        LoggerInterface $logger,
        BuildConfig $buildConfig,
        File $file,
        Environment $environment,
        FileList $fileList,
        ArrayManager $arrayManager,
        ProcessInterface $process
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->buildConfig = $buildConfig;
        $this->environment = $environment;
        $this->fileList = $fileList;
        $this->arrayManager = $arrayManager;
        $this->process = $process;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $configFile = $this->fileList->getConfig();

        if (!$this->file->isExists($configFile) || $this->buildConfig->get(BuildConfig::OPT_SKIP_SCD)) {
            $this->logger->notice('Skipping static content deploy');
            $this->environment->removeFlagStaticContentInBuild();

            return;
        }

        $config = require $configFile;
        $flattenedConfig = $this->arrayManager->flatten($config);
        $websites = $this->arrayManager->filter($flattenedConfig, 'scopes/websites', false);
        $stores = $this->arrayManager->filter($flattenedConfig, 'scopes/stores', false);

        if (count($stores) === 0 && count($websites) === 0) {
            $this->logger->info('Skipping static content deploy. No stores/website/locales found in config.php');
            $this->environment->removeFlagStaticContentInBuild();

            return;
        }

        $this->process->execute();
    }
}
