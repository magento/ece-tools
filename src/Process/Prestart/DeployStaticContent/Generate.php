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
use Magento\MagentoCloud\StaticContent\Prestart\Option;
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
    private $prestartOption;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param File $file
     * @param DirectoryList $directoryList
     * @param CommandFactory $commandFactory
     * @param Option $prestartOption
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        Environment $environment,
        File $file,
        DirectoryList $directoryList,
        CommandFactory $commandFactory,
        Option $prestartOption
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->commandFactory = $commandFactory;
        $this->prestartOption = $prestartOption;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->file->touch($this->directoryList->getMagentoRoot() . '/pub/static/deployed_version.txt');
        $this->logger->info('Extracting locales');

        $locales = $this->prestartOption->getLocales();
        $logMessage = count($locales) ?
            'Generating static content for locales: ' . implode(' ', $locales) :
            'Generating static content';

        $this->logger->info($logMessage);

        $threadCount= $this->prestartOption->getThreadCount();
        $parallelCommands = $this->commandFactory->createParallel($this->prestartOption);

        $this->shell->execute(sprintf(
            "printf '%s' | xargs -I CMD -P %d bash -c CMD",
            $parallelCommands,
            $threadCount
        ));
    }
}
