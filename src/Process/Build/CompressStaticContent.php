<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Util\StaticContentCompressor;

/**
 * @inheritdoc
 */
class CompressStaticContent implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var StaticContentCompressor
     */
    private $staticContentCompressor;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface  $shell
     * @param File            $file
     * @param BuildConfig     $buildConfig
     * @param Environment     $environment
     * @param DirectoryList   $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file,
        BuildConfig $buildConfig,
        Environment $environment,
        DirectoryList $directoryList,
        StaticContentCompressor $staticContentCompressor
    ) {
        $this->logger                  = $logger;
        $this->shell                   = $shell;
        $this->file                    = $file;
        $this->buildConfig             = $buildConfig;
        $this->environment             = $environment;
        $this->directoryList           = $directoryList;
        $this->staticContentCompressor = $staticContentCompressor;
    }

    /**
     * @return bool
     */
    public function execute()
    {
        // Only proceed if static content deployment has already run.
        if (!$this->environment->isStaticDeployInBuild()) {
            $this->logger->info(
                "Skipping build-time static content compression "
                . "because static content deployment hasn't happened yet.");

            return false;
        }

        $startTime = microtime(true);
        $this->staticContentCompressor->compressStaticContent(6);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $this->logger->info(
            "Static content compression took $duration seconds.");
        return true;
    }
}
