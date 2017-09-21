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
    private $componentInfo;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @param LoggerInterface $logger
     * @param ProcessInterface $process
     * @param PackageManager $componentInfo
     */
    public function __construct(
        LoggerInterface $logger,
        ProcessInterface $process,
        PackageManager $componentInfo
    ) {
        $this->logger = $logger;
        $this->process = $process;
        $this->componentInfo = $componentInfo;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Starting predeploy. ' . $this->componentInfo->get());
        $this->process->execute();
    }
}
