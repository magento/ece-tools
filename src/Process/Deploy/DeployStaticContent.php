<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCleaner;

/**
 * @inheritdoc
 */
class DeployStaticContent implements ProcessInterface
{
    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @var StaticContentCleaner
     */
    private $staticContentCleaner;

    /**
     * @param ProcessInterface $process
     * @param FlagManager $flagManager
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     * @param GlobalConfig $globalConfig
     * @param StaticContentCleaner $staticContentCleaner
     */
    public function __construct(
        ProcessInterface $process,
        FlagManager $flagManager,
        LoggerInterface $logger,
        DeployInterface $stageConfig,
        GlobalConfig $globalConfig,
        StaticContentCleaner $staticContentCleaner
    ) {
        $this->process = $process;
        $this->flagManager = $flagManager;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
        $this->globalConfig = $globalConfig;
        $this->staticContentCleaner = $staticContentCleaner;
    }

    /**
     * This function deploys the static content.
     * Moved this from processMagentoMode() to its own function because we changed the order to have
     * processMagentoMode called before the install.  Static content deployment still needs to happen after install.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($this->globalConfig->get(DeployInterface::VAR_SCD_ON_DEMAND)) {
            $this->logger->notice('Skipping static content deploy. SCD on demand is enabled.');
            $this->staticContentCleaner->clean();

            return;
        }

        if ($this->stageConfig->get(DeployInterface::VAR_SKIP_SCD)
            || $this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
        ) {
            return;
        }

        if ($this->stageConfig->get(DeployInterface::VAR_CLEAN_STATIC_FILES)) {
            $this->staticContentCleaner->clean();
        }

        $this->logger->notice('Generating fresh static content');
        $this->process->execute();
        $this->logger->notice('End of generating fresh static content');
    }
}
