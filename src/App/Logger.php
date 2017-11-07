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
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct('default', $pool->getHandlers());
    }
}
