<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Util\ForkManager;
use Magento\MagentoCloud\Util\ForkManager\SingletonFactory as ForkManagerSingletonFactory;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Waits for forked child processes to exit.
 * We do this so that the Deploy command doesn't exit before its children which can cause undefined behaviour.
 */
class WaitForForkedChildrenToExit implements ProcessInterface
{
    /**
     * @var ForkManagerSingletonFactory
     */
    private $logger;

    /**
     * @var ForkManagerSingletonFactory
     */
    private $forkManagerSingletonFactory;

    /**
     * @param ForkManagerSingletonFactory $forkManagerSingletonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ForkManagerSingletonFactory $forkManagerSingletonFactory,
        LoggerInterface $logger
    ) {
        $this->forkManagerSingletonFactory = $forkManagerSingletonFactory;
        $this->logger = $logger;
    }

    /**
     * Waits for children processes to exit
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->forkManagerSingletonFactory->create()->waitForChildren();
    }
}
