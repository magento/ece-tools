<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Filesystem\FileList;

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
     * @var FileList
     */
    private $fileList;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param BuildConfig $buildConfig
     * @param FileList $fileList
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        BuildConfig $buildConfig,
        FileList $fileList
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->buildConfig = $buildConfig;
        $this->fileList = $fileList;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function execute()
    {
        $verbosityLevel = $this->buildConfig->getVerbosityLevel();

        $this->logger->info('Running DI compilation');
        $tempfilename = tempnam();
        $configfilename = $this->fileList->getConfig();
        /* Note: Making a backup before we enable all modules */
        if (! copy($configfilename, $tempfilename)) {
            throw( new \RuntimeException("Couldn't create temp file."));
        }
        /* Note: going back to enabling all modules and di compiling all of them means that we might waste
          several minutes in build time if there are several modules that are wanting to be disabled.
        */
        $this->shell->execute('php bin/magento module:enable --all');
        $this->shell->execute("php ./bin/magento setup:di:compile {$verbosityLevel}");
        /* Note: copying it back to how the config.php file was set up before enable all */
        if (! copy($tempfilename, $configfilename)) {
            throw( new \RuntimeException("Couldn't copy back config.php"));
        }
        unlink($tempfilename);
    }
}
