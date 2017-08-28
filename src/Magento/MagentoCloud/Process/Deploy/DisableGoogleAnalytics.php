<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DisableGoogleAnalytics implements ProcessInterface
{
    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Adapter $adapter
     * @param LoggerInterface $logger
     * @param Environment $environment
     */
    public function __construct(Adapter $adapter, LoggerInterface $logger, Environment $environment)
    {
        $this->adapter = $adapter;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->environment->isMasterBranch()) {
            $this->logger->info('Disabling Google Analytics');
            $this->adapter->execute("update core_config_data set value = 0 where path = 'google/analytics/active';");
        }
    }
}
