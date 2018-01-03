<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CleanCache.
 *
 * @deprecated This functionality will be moved to post-deploy hook.
 * @see \Magento\MagentoCloud\Process\PostDeploy\CleanCache
 */
class CleanCache implements ProcessInterface
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
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        DeployInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->stageConfig = $stageConfig;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated This functionality will be moved to post-deploy hook.
     * @see \Magento\MagentoCloud\Process\PostDeploy\CleanCache
     */
    public function execute()
    {
        $this->logger->info('Clearing application cache.');

        $command = 'php ./bin/magento cache:flush ' . $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);

        $this->shell->execute($command);
    }
}
