<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Logger;

use Magento\MagentoCloud\Environment;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

class Logger extends \Monolog\Logger implements \Psr\Log\LoggerInterface
{
    const DEPLOY_LOG = Environment::MAGENTO_ROOT . 'var/log/cloud_deploy.log';

    public function __construct()
    {
        $formatter = new LineFormatter();
        $formatter->allowInlineLineBreaks(true);

        $file = (new StreamHandler(static::DEPLOY_LOG))->setFormatter($formatter);
        $stdOutHandler = (new StreamHandler('php://stdout'))->setFormatter($formatter);

        parent::__construct('default', [$file, $stdOutHandler]);
    }
}
