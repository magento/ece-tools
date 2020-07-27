<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Urls\Database;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Urls\Environment as EnvironmentUrl;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Urls implements StepInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var Database
     */
    private $databaseUrl;

    /**
     * @var EnvironmentUrl
     */
    private $environmentUrl;

    /**
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param DeployInterface $stageConfig
     * @param Database $databaseUrl
     * @param EnvironmentUrl $environmentUrl
     */
    public function __construct(
        Environment $environment,
        LoggerInterface $logger,
        DeployInterface $stageConfig,
        Database $databaseUrl,
        EnvironmentUrl $environmentUrl
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->stageConfig = $stageConfig;
        $this->databaseUrl = $databaseUrl;
        $this->environmentUrl = $environmentUrl;
    }

    /**
     * Runs update url processes.
     * Always run if FORCE_UPDATE_URLS set to true
     * Skip url updates if master branch is detected or UPDATE_URLS set to false
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        try {
            if (!$this->stageConfig->get(DeployInterface::VAR_FORCE_UPDATE_URLS)) {
                if ($this->environment->isMasterBranch()) {
                    $this->logger->info(
                        'Skipping URL updates because we are deploying to a Production or Staging environment.'
                        . ' You can override this behavior by setting the FORCE_URL_UPDATES variable to true.'
                    );
                    return;
                }

                if (!$this->stageConfig->get(DeployInterface::VAR_UPDATE_URLS)) {
                    $this->logger->info('Skipping URL updates because the URL_UPDATES variable is set to false.');
                    return;
                }
            }

            $this->logger->info('Updating secure and unsecure URLs');

            $this->databaseUrl->execute();
            $this->environmentUrl->execute();
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
