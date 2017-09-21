<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\PackageManager;
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
     * @param PackageManager $componentInfo
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        PackageManager $componentInfo
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->packageManager = $componentInfo;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Applying patches.');

        try {
            if ($this->packageManager->hasMagentoVersion('2.2')) {
                $this->shell->execute('php vendor/bin/m2-apply-patches');
            }
        } catch (\Exception $exception) {
            $this->logger->warning('Patching was failed. Skipping.');
        }
    }
}
