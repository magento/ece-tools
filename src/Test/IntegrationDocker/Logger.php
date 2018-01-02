<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\IntegrationDocker;

/**
 * @inheritdoc
 */
class Logger extends \Monolog\Logger
{
    public function __construct()
    {
        parent::__construct('test', [], []);
    }
}
