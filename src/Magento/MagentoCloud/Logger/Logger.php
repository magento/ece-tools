<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * @inheritdoc
 */
class Logger extends \Monolog\Logger implements \Psr\Log\LoggerInterface
{
    /**
     * @inheritdoc
     */
    public function __construct()
    {
        $formatter = new LineFormatter();
        $formatter->allowInlineLineBreaks(true);

        $deployLogPath = MAGENTO_ROOT . 'var/log/cloud_build.log';
        $fileHandler = (new StreamHandler($deployLogPath))->setFormatter($formatter);
        $stdOutHandler = (new StreamHandler('php://stdout'))->setFormatter($formatter);

        parent::__construct('default', [$fileHandler, $stdOutHandler]);
    }
}
