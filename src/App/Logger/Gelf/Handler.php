<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger\Gelf;

use Monolog\Handler\GelfHandler;

class Handler extends GelfHandler
{
    protected function write(array $record)
    {
        try {
            parent::write($record);
        } catch (\RuntimeException $e) {
            fwrite(STDOUT, 'Can\'t send message to graylog: ' . $e->getMessage());
        }
    }
}
