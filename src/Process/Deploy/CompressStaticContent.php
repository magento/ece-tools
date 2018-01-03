<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\FlagFile\StaticContentDeployPendingFlag;
use Magento\MagentoCloud\Filesystem\FlagFilePool;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;

/**
 * Compress static content at deploy time.
 */
class CompressStaticContent implements ProcessInterface
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
     * @var StaticContentCompressor
     */
    private $staticContentCompressor;

    /**
     * @var FlagFilePool
     */
    private $flagFilePool;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param StaticContentCompressor $staticContentCompressor
     * @param FlagFilePool $flagFilePool
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        StaticContentCompressor $staticContentCompressor,
        FlagFilePool $flagFilePool,
        DeployInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->staticContentCompressor = $staticContentCompressor;
        $this->flagFilePool = $flagFilePool;
        $this->stageConfig = $stageConfig;
    }

    /**
     * Execute the deploy-time static content compression process.
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->stageConfig->get(DeployInterface::VAR_SKIP_SCD)
            && $this->environment->isDeployStaticContent()
        ) {
            if ($this->flagFilePool->getFlag(StaticContentDeployPendingFlag::KEY)->exists()) {
                $this->logger->info('Postpone static content compression until prestart');

                return;
            }
            $this->staticContentCompressor->process(
                $this->stageConfig->get(DeployInterface::VAR_SCD_COMPRESSION_LEVEL),
                $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)
            );
        } else {
            $this->logger->info(
                "Static content deployment was performed during the build phase or disabled. Skipping deploy phase"
                . " static content compression."
            );
        }
    }
}
