<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Package\Manager as PackageManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ApplyPatches implements ProcessInterface
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
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param PackageManager $packageManager
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        PackageManager $packageManager
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->packageManager = $packageManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Applying patches.');

        if (!$this->packageManager->has('magento/ece-patches')) {
            $this->logger->warning('Package with patches was not found.');

            return;
        }

        $this->shell->execute('php ./vendor/bin/m2-apply-patches');
    }
}
