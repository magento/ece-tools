<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Prestart;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
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
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param StaticContentCompressor $staticContentCompressor
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        StaticContentCompressor $staticContentCompressor,
        FlagManager $flagManager,
        DeployInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->staticContentCompressor = $staticContentCompressor;
        $this->flagManager = $flagManager;
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
            && $this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_PENDING)
        ) {
            $this->staticContentCompressor->process(
                $this->stageConfig->get(DeployInterface::VAR_SCD_COMPRESSION_LEVEL),
                $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)
            );
        } else {
            $this->logger->info(
                "Static content deployment was performed during the build phase or disabled. Skipping prestart phase"
                . " static content compression."
            );
        }
    }
}
