<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Monolog\Handler\NullHandler;

/**
 * Logger class for disabling logging, contains only null handler.
 */
class NullLogger extends \Monolog\Logger
{
    public function __construct(NullHandler $handler)
    {
        parent::__construct('null', [$handler]);
    }
}
