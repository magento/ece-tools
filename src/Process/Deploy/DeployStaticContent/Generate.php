<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\DeployStaticContent;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     * @param CommandFactory $commandFactory
     * @param Option $deployOption
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList,
        CommandFactory $commandFactory,
        Option $deployOption,
        DeployInterface $stageConfig
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->commandFactory = $commandFactory;
        $this->deployOption = $deployOption;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->file->touch($this->directoryList->getMagentoRoot() . '/pub/static/deployed_version.txt')) {
            throw new ProcessException('Cannot update deployed version.');
        }

        $this->logger->info('Extracting locales');

        $logMessage = count($this->deployOption->getLocales()) ?
            'Generating static content for locales: ' . implode(' ', $this->deployOption->getLocales()) :
            'Generating static content';

        $this->logger->info($logMessage);

        $commands = $this->commandFactory->matrix(
            $this->deployOption,
            $this->stageConfig->get(DeployInterface::VAR_SCD_MATRIX)
        );

        try {
            foreach ($commands as $command) {
                $this->shell->execute($command);
            }
        } catch (ShellException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
