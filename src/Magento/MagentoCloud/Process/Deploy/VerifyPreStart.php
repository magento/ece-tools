<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class VerifyPreStart implements ProcessInterface
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
     * @param LoggerInterface $logger
     * @param Environment $environment
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->environment->hasFlag(Environment::PRE_START_FLAG)) {
            $this->logger->info("Error: pre-start flag still exists. This means pre-start operation did not complete"
                . " successfully. Aborting deployment. Flag location: "
                . $this->environment->getFullPath(Environment::PRE_START_FLAG));
            $this->environment->setFlag(Environment::MAINTENANCE_FLAG);
            throw new \RuntimeException("PreStart flag still exists!");
        }
        $this->environment->clearFlag(Environment::DEPLOY_READY_FLAG);
    }
}
