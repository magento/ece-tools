<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\ComponentInfo;
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
     * @var ComponentInfo
     */
    private $componentInfo;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @param LoggerInterface $logger
     * @param ProcessInterface $process
     * @param ComponentInfo $componentInfo
     */
    public function __construct(
        LoggerInterface $logger,
        ProcessInterface $process,
        ComponentInfo $componentInfo
    ) {
        $this->logger = $logger;
        $this->componentInfo = $componentInfo;
        $this->process = $process;
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
