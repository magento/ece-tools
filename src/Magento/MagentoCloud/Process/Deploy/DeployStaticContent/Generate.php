<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\StaticContent\Command;
use Magento\MagentoCloud\StaticContent\Deploy\Option;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Generate implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Command
     */
    private $scdCommand;
    /**
     * @var Option
     */
    private $deployOption;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param File $file
     * @param DirectoryList $directoryList
     * @param Command $scdCommand
     * @param Option $deployOption
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        Environment $environment,
        File $file,
        DirectoryList $directoryList,
        Command $scdCommand,
        Option $deployOption
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->scdCommand = $scdCommand;
        $this->deployOption = $deployOption;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->file->touch($this->directoryList->getMagentoRoot() . '/pub/static/deployed_version.txt');
        $this->logger->info('Enabling Maintenance mode');
        $this->shell->execute("php ./bin/magento maintenance:enable {$this->environment->getVerbosityLevel()}");
        $this->logger->info('Extracting locales');

        $logMessage = count($this->deployOption->getLocales()) ?
            'Generating static content for locales: ' . implode(' ', $this->deployOption->getLocales()) :
            'Generating static content';

        $this->logger->info($logMessage);

        $command = $this->scdCommand->create($this->deployOption);

        $this->shell->execute($command);

        $this->shell->execute("php ./bin/magento maintenance:disable {$this->environment->getVerbosityLevel()}");
        $this->logger->info('Maintenance mode is disabled.');
    }
}
