<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Build\DeployStaticContent;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\StaticContent\Build\Option;
use Magento\MagentoCloud\StaticContent\CommandFactory;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Generate implements StepInterface
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
    public function execute(): void
    {
        $locales = $this->buildOption->getLocales();
        $threadCount = $this->buildOption->getThreadCount();

        $logMessage = 'Generating static content for locales: ' . implode(' ', $locales);

        if ($threadCount) {
            $logMessage .= PHP_EOL . 'Using ' . $threadCount . ' Threads';
        }

        $this->logger->info($logMessage);

        try {
            $commands = $this->commandFactory->matrix(
                $this->buildOption,
                $this->buildConfig->get(BuildInterface::VAR_SCD_MATRIX)
            );

            foreach ($commands as $command) {
                $this->shell->execute($command);
            }
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::BUILD_SCD_FAILED, $e);
        } catch (ConfigException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
