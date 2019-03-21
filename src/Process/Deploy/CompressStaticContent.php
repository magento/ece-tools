<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;

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
     * @param LoggerInterface $logger
     * @param StaticContentCompressor $staticContentCompressor
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     * @param GlobalConfig $globalConfig
     */
    public function __construct(
        LoggerInterface $logger,
        StaticContentCompressor $staticContentCompressor,
        FlagManager $flagManager,
        DeployInterface $stageConfig,
        GlobalConfig $globalConfig
    ) {
        $this->logger = $logger;
        $this->staticContentCompressor = $staticContentCompressor;
        $this->flagManager = $flagManager;
        $this->stageConfig = $stageConfig;
        $this->globalConfig = $globalConfig;
    }

    /**
     * Execute the deploy-time static content compression process.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($this->globalConfig->get(DeployInterface::VAR_SCD_ON_DEMAND)) {
            $this->logger->notice('Skipping static content compression. SCD on demand is enabled.');

            return;
        }

        if (!$this->stageConfig->get(DeployInterface::VAR_SKIP_SCD)
            && !$this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
        ) {
            $this->staticContentCompressor->process(
                $this->stageConfig->get(DeployInterface::VAR_SCD_COMPRESSION_LEVEL),
                $this->stageConfig->get(DeployInterface::VAR_SCD_COMPRESSION_TIMEOUT),
                $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)
            );
        } else {
            $this->logger->info(
                'Static content deployment was performed during the build phase or disabled. Skipping deploy phase'
                . ' static content compression.'
            );
        }
    }
}
