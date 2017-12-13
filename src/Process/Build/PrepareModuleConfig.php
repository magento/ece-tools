<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Shared as SharedConfig;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PrepareModuleConfig implements ProcessInterface
{
    /**
     * @var SharedConfig
     */
    private $sharedConfig;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SharedConfig $sharedConfig
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     */
    public function __construct(
        SharedConfig $sharedConfig,
        ShellInterface $shell,
        LoggerInterface $logger
    ) {
        $this->sharedConfig = $sharedConfig;
        $this->shell = $shell;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Reconciling installed modules with shared config.');
        $moduleConfig = $this->sharedConfig->get('modules');

        if (empty($moduleConfig)) {
            $this->logger->info('Shared config file is missing module section. Updating with all installed modules.');
            $this->shell->execute('php bin/magento module:enable --all');
            $this->sharedConfig->clearCache();
            return;
        }

        $oldconfig = $this->sharedConfig->read();
        $this->shell->execute('php bin/magento module:enable --all');
        $this->sharedConfig->clearCache();
        $this->sharedConfig->update($oldconfig);
    }
}
