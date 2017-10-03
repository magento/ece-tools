<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
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
     * @var Environment
     */
    private $environment;

    /**
     * @var CommandFactory
     */
    private $commandFactory;

    /**
     * @var Option
     */
    private $buildOption;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param CommandFactory $commandFactory
     * @param Option $buildOption
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        Environment $environment,
        CommandFactory $commandFactory,
        Option $buildOption
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->commandFactory = $commandFactory;
        $this->buildOption = $buildOption;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $locales = $this->buildOption->getLocales();
            $excludeThemes = $this->buildOption->getExcludedThemes();
            $threadCount= $this->buildOption->getTreadCount();

            $logMessage = 'Generating static content for locales: ' . implode(' ', $locales);

            if (count($excludeThemes)) {
                $logMessage .= PHP_EOL . 'Excluding Themes: ' . implode(' ', $excludeThemes);
            }

            if ($threadCount) {
                $logMessage .= PHP_EOL . 'Using ' . $threadCount . ' Threads';
            }

            $this->logger->info($logMessage);

            $parallelCommands = $this->commandFactory->createParallel($this->buildOption);

            $this->shell->execute(sprintf(
                "printf '%s' | xargs -I CMD -P %d bash -c CMD",
                $parallelCommands,
                $threadCount
            ));

            $this->environment->setFlagStaticDeployInBuild();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 5);
        }
    }
}
