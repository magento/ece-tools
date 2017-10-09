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
        = "find pub/static -type f -size +300c"
        . " '(' -name '*.js' -or -name '*.css' -or -name '*.svg'"
        . " -or -name '*.html' -or -name '*.htm' ')'"
        . " | xargs -n100 -P16 gzip --keep";

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

        $compressionCommand = $this->getCompressionCommand();

        $startTime = microtime(true);
        $this->shell->execute($compressionCommand);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $this->logger->info(
            "Static content compression took $duration seconds.",
            [
                'command' => $compressionCommand,
            ]);

        return true;
    }

    /**
     * @param int  $compressionLevel
     * @param bool $verbose
     *
     * @return string
     */
    private function getCompressionCommand($compressionLevel = 1, $verbose = false) {
        if (!is_int($compressionLevel) || $compressionLevel < 1 || $compressionLevel > 9) {
            $this->logger->info("Compression level was set to \"$compressionLevel\" but this is invalid. Using default compression level of \"1\".");
            $compressionLevel = 1;
        }

        $compressionCommand = self::$compressionCommand;

        if ($verbose) {
            $compressionCommand .= " -v";
        }

        $compressionCommand .= " -$compressionLevel";

        $compressionCommand
            = self::$timeoutCommand . '"'
            . $compressionCommand . '"';

        return $compressionCommand;
    }
}
