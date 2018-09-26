<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Uses for enabling and disabling magento maintenance mode on deploy phase
 */
class MaintenanceModeSwitcher
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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        DeployInterface $stageConfig
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
    }

    /**
     * Enables maintenance mode
     *
     * @return void
     * @throws ShellException If shell command was executed with error
     */
    public function enable()
    {
        $this->logger->notice('Enabling Maintenance mode');
        $this->shell->execute(sprintf(
            'php ./bin/magento maintenance:enable --ansi --no-interaction %s',
            $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)
        ));
    }

    /**
     * Disable maintenance mode
     *
     * @return void
     * @throws ShellException If shell command was executed with error
     */
    public function disable()
    {
        $this->shell->execute(sprintf(
            'php ./bin/magento maintenance:disable --ansi --no-interaction %s',
            $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)
        ));
        $this->logger->notice('Maintenance mode is disabled.');
    }
}
