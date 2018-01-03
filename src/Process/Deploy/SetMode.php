<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Writer as DeployConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetMode implements ProcessInterface
{
    /**
     * @var DeployConfigWriter
     */
    private $deployConfigWriter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var Environment
     */
    private $env;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param Environment $env
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param DeployConfigWriter $deployConfigWriter
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Environment $env,
        LoggerInterface $logger,
        ShellInterface $shell,
        DeployConfigWriter $deployConfigWriter,
        DeployInterface $stageConfig
    ) {
        $this->env = $env;
        $this->logger = $logger;
        $this->shell = $shell;
        $this->deployConfigWriter = $deployConfigWriter;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $mode = $this->env->getApplicationMode();
        $this->logger->info(sprintf("Set Magento application mode to '%s'", $mode));

        if ($mode === Environment::MAGENTO_PRODUCTION_MODE) {
            $this->deployConfigWriter->update(['MAGE_MODE' => 'production']);
        } else {
            $this->shell->execute(sprintf(
                "php ./bin/magento deploy:mode:set %s %s",
                Environment::MAGENTO_DEVELOPER_MODE,
                $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)
            ));
        }
    }
}
