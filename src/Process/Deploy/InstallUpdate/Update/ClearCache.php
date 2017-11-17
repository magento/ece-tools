<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ClearCache.
 *
 * @deprecated This functionality will be moved to post-deploy hook.
 * @see \Magento\MagentoCloud\Process\PostDeploy\CleanCache
 */
class ClearCache implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ShellInterface
     */
    private $shell;

    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ShellInterface $shell
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->shell = $shell;
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

        $command = 'php ./bin/magento cache:flush' . $this->environment->getVerbosityLevel();

        $this->shell->execute($command);
    }
}
