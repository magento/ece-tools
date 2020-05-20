<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\UtilityException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;

/**
 * Compress static content at deploy time.
 */
class CompressStaticContent implements StepInterface
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
        try {
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
        } catch (UtilityException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_UTILITY_NOT_FOUND, $e);
        } catch (ShellException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_SCD_COMPRESSION_FAILED, $e);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
