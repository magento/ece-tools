<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Logger;

use Magento\MagentoCloud\Config\Environment;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

class Logger extends \Monolog\Logger implements \Psr\Log\LoggerInterface
{
    public function __construct()
    {
        $formatter = new LineFormatter();
        $formatter->allowInlineLineBreaks(true);

        $deployLogPath = Environment::MAGENTO_ROOT . 'var/log/cloud_deploy.log';
        $fileHandler = (new StreamHandler($deployLogPath))->setFormatter($formatter);
        $stdOutHandler = (new StreamHandler('php://stdout'))->setFormatter($formatter);

        parent::__construct('default', [$fileHandler, $stdOutHandler]);
    }
}
