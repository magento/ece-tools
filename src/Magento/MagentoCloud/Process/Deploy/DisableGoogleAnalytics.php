<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

class DisableGoogleAnalytics implements ProcessInterface
{
    const GIT_MASTER_BRANCH_RE = '/^master(?:-[a-z0-9]+)?$/i';

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Adapter $adapter
     * @param LoggerInterface $logger
     */
    public function __construct(Adapter $adapter, LoggerInterface $logger)
    {
        $this->adapter = $adapter;
        $this->logger = $logger;
    }

    public function execute()
    {
        if (!$this->isMasterBranch()) {
            $this->logger->info('Disabling Google Analytics');
            $this->adapter->execute("update core_config_data set value = 0 where path = 'google/analytics/active';");
        }
    }

    /**
     * If current deploy is about master branch
     *
     * @return boolean
     */
    private function isMasterBranch()
    {
        if (isset($_ENV["MAGENTO_CLOUD_ENVIRONMENT"])
            && preg_match(self::GIT_MASTER_BRANCH_RE, $_ENV["MAGENTO_CLOUD_ENVIRONMENT"])
        ) {
            return true;
        }

        return false;
    }
}
