<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCleaner;

/**
 * @inheritdoc
 */
class DeployStaticContent implements StepInterface
{
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
     * @var StepInterface[]
     */
    private $steps;

    /**
     * @param FlagManager $flagManager
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     * @param GlobalConfig $globalConfig
     * @param StaticContentCleaner $staticContentCleaner
     * @param StepInterface[] $steps
     */
    public function __construct(
        FlagManager $flagManager,
        LoggerInterface $logger,
        DeployInterface $stageConfig,
        GlobalConfig $globalConfig,
        StaticContentCleaner $staticContentCleaner,
        array $steps
    ) {
        $this->flagManager = $flagManager;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
        $this->globalConfig = $globalConfig;
        $this->staticContentCleaner = $staticContentCleaner;
        $this->steps = $steps;
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
        try {
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

            foreach ($this->steps as $step) {
                $step->execute();
            }

            $this->logger->notice('End of generating fresh static content');
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
