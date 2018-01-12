<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Prestart\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\StaticContent\CommandFactory;
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
     * @var CommandFactory
     */
    private $commandFactory;

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
     * @param CommandFactory $commandFactory
     * @param Option $deployOption
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        Environment $environment,
        File $file,
        DirectoryList $directoryList,
        CommandFactory $commandFactory,
        Option $deployOption
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->commandFactory = $commandFactory;
        $this->deployOption = $deployOption;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->file->touch($this->directoryList->getMagentoRoot() . '/pub/static/deployed_version.txt');
        $this->logger->info('Extracting locales');

        $locales = $this->deployOption->getLocales();
        $logMessage = count($locales) ?
            'Generating static content for locales: ' . implode(' ', $locales) :
            'Generating static content';

        $this->logger->info($logMessage);

        $command = $this->commandFactory->create($this->deployOption);
        $this->shell->execute($command);
    }
}
