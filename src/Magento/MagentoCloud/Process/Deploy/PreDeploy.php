<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\PackageManager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PreDeploy implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @param LoggerInterface $logger
     * @param ProcessInterface $process
     * @param PackageManager $packageManager
     */
    public function __construct(
        LoggerInterface $logger,
        ProcessInterface $process,
        PackageManager $packageManager
    ) {
        $this->logger = $logger;
        $this->process = $process;
        $this->packageManager = $packageManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Starting predeploy. ' . $this->packageManager->get());
        $this->process->execute();
    }
}
