<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Psr\Log\LoggerInterface;

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
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param LoggerInterface $logger
     * @param StaticContentCompressor $staticContentCompressor
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     * @param GlobalConfig $globalConfig
     * @param Environment $environment
     */
    public function __construct(
        LoggerInterface $logger,
        StaticContentCompressor $staticContentCompressor,
        FlagManager $flagManager,
        DeployInterface $stageConfig,
        GlobalConfig $globalConfig,
        Environment $environment
    ) {
        $this->logger = $logger;
        $this->staticContentCompressor = $staticContentCompressor;
        $this->flagManager = $flagManager;
        $this->stageConfig = $stageConfig;
        $this->globalConfig = $globalConfig;
        $this->environment = $environment;
    }

    /**
     * Execute the deploy-time static content compression process.
     *
     * @return void
     */
    public function execute()
    {
        if ($this->globalConfig->get(DeployInterface::VAR_SCD_ON_DEMAND) ||
            $this->environment->getVariable(DeployInterface::VAR_SCD_ON_DEMAND) == Environment::VAL_ENABLED
        ) {
            $this->logger->notice('Skipping static content compression. SCD on demand is enabled.');

            return;
        }

        if (!$this->stageConfig->get(DeployInterface::VAR_SKIP_SCD)
            && !$this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
        ) {
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
