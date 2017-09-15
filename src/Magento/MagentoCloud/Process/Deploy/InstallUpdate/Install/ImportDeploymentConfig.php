<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

class ImportDeploymentConfig implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info("Importing deployment config");
        $this->shell->execute("php ./bin/magento app:config:import -n");
    }
}
