<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\App\Logger\Pool;

/**
 * @inheritdoc
 */
class Logger extends \Monolog\Logger
{
    /**
     * Path to the deployment log.
     */
    const DEPLOY_LOG_PATH = 'var/log/cloud.log';

    /**
     * Path to the build phase log.
     */
    const BACKUP_BUILD_PHASE_LOG_PATH = 'init/var/log/cloud.log';

    /**
     * Path to the log dir
     */
    const LOG_DIR = 'var/log';

    /**
     * Path to the file with handlers configurations
     */
    const CONFIG_HANDLERS_LOG = '.log.handlers.yml';

    /**
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct('default', $pool->getHandlers());
    }
}
