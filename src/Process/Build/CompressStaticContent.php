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
class CompileDi implements ProcessInterface
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

    private static $compressionCommand = 'echo "Hello, world!"';

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
        $compressionCommand = self::$compressionCommand;

        $startTime = microtime(true);
        $this->shell->execute($compressionCommand);
        $endTime = microtime(true);

        $duration = $startTime - $endTime;
        $this->logger->info("Static content compression took $duration seconds.",
            ['command' => $compressionCommand]);
    }
}
