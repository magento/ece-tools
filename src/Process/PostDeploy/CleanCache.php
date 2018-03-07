<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Cleans all cache by tags.
 */
class CleanCache implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param ShellInterface $shell
     * @param DeployInterface $stageConfig
     * @param LoggerInterface $logger
     * @param Environment $environment
     */
    public function __construct(
        ShellInterface $shell,
        DeployInterface $stageConfig,
        LoggerInterface $logger,
        Environment $environment
    ) {
        $this->shell = $shell;
        $this->stageConfig = $stageConfig;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $applicationEnv = $this->environment->getApplication();

        if (!isset($applicationEnv['hooks']['post_deploy'])) {
            $this->logger->warning('Your application seems not using \'post_deploy\' hook.');
        }

        $this->logger->info('Clearing application cache.');

        $this->shell->execute(
            'php ./bin/magento cache:flush ' . $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)
        );
    }
}
