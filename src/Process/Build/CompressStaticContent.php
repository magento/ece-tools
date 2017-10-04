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
     * @var string
     */
    private static $timeoutCommand = "/usr/bin/timeout -k 30 300 /bin/bash -c ";

    /**
     * @var string
     */
    private static $compressionCommand
        = "find pub/static -type f -name '*.js' -or -name '*.css' -or -name '*.svg'"
        . " | xargs -n100 -P16 gzip -1 --keep";

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
        DirectoryList $directoryList
    ) {
        $this->logger        = $logger;
        $this->shell         = $shell;
        $this->file          = $file;
        $this->buildConfig   = $buildConfig;
        $this->environment   = $environment;
        $this->directoryList = $directoryList;
    }

    /**
     * {@inheritdoc
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

        $compressionCommand = $this->getCompressionCommand(true);

        $startTime = microtime(true);
        $this->shell->execute($compressionCommand);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $this->logger->info(
            "Static content compression took $duration seconds.",
            [
                'command' => $compressionCommand,
            ]);
    }

    /**
     * @return string
     */
    private function getCompressionCommand($verbose = false) {
        $compressionCommand = self::$compressionCommand;

        if ($verbose) {
            $compressionCommand .= " -lv";
        }

        $compressionCommand
            = self::$timeoutCommand . '"'
            . $compressionCommand . '"';

        return $compressionCommand;
    }
}
