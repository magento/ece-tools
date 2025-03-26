<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Stage\Deploy as DeployConfig;

/**
 * @inheritdoc
 */
class DisableGoogleAnalytics implements StepInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @param ConnectionInterface $connection
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param DeployConfig $deployConfig
     */
    public function __construct(
        ConnectionInterface $connection,
        LoggerInterface $logger,
        Environment $environment,
        DeployConfig $deployConfig
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->deployConfig = $deployConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            if (!$this->environment->isMasterBranch() &&
                !$this->deployConfig->get(DeployInterface::VAR_ENABLE_GOOGLE_ANALYTICS)
            ) {
                $this->logger->info('Disabling Google Analytics');
                $this->connection->affectingQuery(sprintf(
                    "UPDATE `%s` SET `value` = 0 WHERE `path` = 'google/analytics/active'",
                    $this->connection->getTableName('core_config_data')
                ));
            }
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
