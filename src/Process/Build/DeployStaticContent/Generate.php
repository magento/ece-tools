<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build\DeployStaticContent;

use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\StaticContent\Build\Option;
use Magento\MagentoCloud\StaticContent\CommandFactory;
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
     * @var CommandFactory
     */
    private $commandFactory;

    /**
     * @var Option
     */
    private $buildOption;

    /**
     * @var BuildInterface
     */
    private $buildConfig;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param CommandFactory $commandFactory
     * @param Option $buildOption
     * @param BuildInterface $buildConfig
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        CommandFactory $commandFactory,
        Option $buildOption,
        BuildInterface $buildConfig
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->commandFactory = $commandFactory;
        $this->buildOption = $buildOption;
        $this->buildConfig = $buildConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $locales = $this->buildOption->getLocales();
        $excludeThemes = $this->buildOption->getExcludedThemes();
        $threadCount = $this->buildOption->getThreadCount();

        $logMessage = 'Generating static content for locales: ' . implode(' ', $locales);

        if (count($excludeThemes)) {
            $logMessage .= PHP_EOL . 'Excluding Themes: ' . implode(' ', $excludeThemes);
        }

        if ($threadCount) {
            $logMessage .= PHP_EOL . 'Using ' . $threadCount . ' Threads';
        }

        $this->logger->info($logMessage);

        $commands = $this->commandFactory->matrix(
            $this->buildOption,
            $this->buildConfig->get(BuildInterface::VAR_SCD_MATRIX)
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
