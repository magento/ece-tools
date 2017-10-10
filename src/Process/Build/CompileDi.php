<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
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
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param BuildConfig $buildConfig
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        BuildConfig $buildConfig,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->buildConfig = $buildConfig;
        $this->directoryList = $directoryList;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function execute()
    {
        $verbosityLevel = $this->buildConfig->getVerbosityLevel();

        $this->logger->info('Running DI compilation');
        $this->shell->execute("php ./bin/magento setup:di:compile {$verbosityLevel}");
    }
}
