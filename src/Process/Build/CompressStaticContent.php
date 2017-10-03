<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Process\Build;

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
     * @var string
     */
    private static $timeoutCommand = "timeout -k 30 300 bash -c ";

    /**
     * @var string
     */
    private static $compressionCommand
        = "find pub/static -type f -name '*.js' -or -name '*.css' -or -name '*.svg'"
        . " | xargs -P2 gzip --keep";

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface  $shell
     * @param File            $file
     * @param BuildConfig     $buildConfig
     * @param DirectoryList   $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file,
        BuildConfig $buildConfig,
        DirectoryList $directoryList
    ) {
        $this->logger        = $logger;
        $this->shell         = $shell;
        $this->file          = $file;
        $this->buildConfig   = $buildConfig;
        $this->directoryList = $directoryList;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function execute()
    {
        $compressionCommand
            = self::$timeoutCommand . '"'
            . self::$compressionCommand . '"';

        $startTime = microtime(true);
        $this->shell->execute($compressionCommand);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $this->logger->info("Static content compression took $duration seconds.",
            ['command' => $compressionCommand]);
    }
}
