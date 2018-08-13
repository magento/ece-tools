<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build\DeployStaticContent;

use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ExecBinMagento;
use Magento\MagentoCloud\StaticContent\Build\Option;
use Magento\MagentoCloud\StaticContent\CommandFactory;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Generate implements ProcessInterface
{
    /**
     * @var ExecBinMagento
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
     * @param ExecBinMagento $shell
     * @param LoggerInterface $logger
     * @param CommandFactory $commandFactory
     * @param Option $buildOption
     * @param BuildInterface $buildConfig
     */
    public function __construct(
        ExecBinMagento $shell,
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
     * {@inheritdoc}
     *
     * @throws \RuntimeException
     */
    public function execute()
    {
        try {
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

            $argCollection = $this->commandFactory->matrix(
                $this->buildOption,
                $this->buildConfig->get(BuildInterface::VAR_SCD_MATRIX)
            );

            foreach ($argCollection as $args) {
                $this->shell->execute('setup:static-content:deploy', $args);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), 5);
        }
    }
}
