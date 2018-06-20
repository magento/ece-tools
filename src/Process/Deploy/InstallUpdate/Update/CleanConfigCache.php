<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * @inheritdoc
 */
class CleanConfigCache implements ProcessInterface
{
    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param DeployInterface $stageConfig
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     */
    public function __construct(
        DeployInterface $stageConfig,
        LoggerInterface $logger,
        ShellInterface $shell
    ) {
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
        $this->shell = $shell;
    }

    public function execute()
    {
        $verbosityLevel = $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);
        $this->logger->info('Clean configuration cache');
        $this->shell->execute('php ./bin/magento cache:flush config '.$verbosityLevel);
    }
}
