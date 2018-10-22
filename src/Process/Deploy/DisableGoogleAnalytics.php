<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\GlobalSection as GlobalConfig;

/**
 * @inheritdoc
 */
class DisableGoogleAnalytics implements ProcessInterface
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
     * @var GlobalConfig
     */
    private $globalConfig;

    /**
     * @param ConnectionInterface $connection
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param GlobalConfig $globalConfig
     */
    public function __construct(
        ConnectionInterface $connection,
        LoggerInterface $logger,
        Environment $environment,
        GlobalConfig $globalConfig
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->globalConfig = $globalConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->environment->isMasterBranch() ||
            $this->globalConfig->get(DeployInterface::VAR_ENABLE_GOOLGE_ANALYTICS)
        ) {
            $this->logger->info('Disabling Google Analytics');
            $this->connection->affectingQuery(
                "UPDATE `core_config_data` SET `value` = 0 WHERE `path` = 'google/analytics/active'"
            );
        }
    }
}
